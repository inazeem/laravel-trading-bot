<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\UserAssetHolding;
use App\Models\ApiKey;
use App\Services\ExchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::active()->orderBy('symbol')->get();
        $userHoldings = Auth::user()->assetHoldings()->with('asset')->get();
        $apiKeys = Auth::user()->apiKeys()->where('is_active', true)->get();
        
        return view('assets.index', compact('assets', 'userHoldings', 'apiKeys'));
    }

    public function portfolio()
    {
        // Get local holdings from database
        $localHoldings = Auth::user()->assetHoldings()
            ->with('asset')
            ->where('quantity', '>', 0)
            ->get()
            ->filter(function ($holding) {
                return $holding->current_value > 10; // Only show holdings worth more than $10
            });

        // Get all active API keys
        $apiKeys = Auth::user()->apiKeys()->where('is_active', true)->get();
        
        // Fetch assets from all exchanges
        $exchangeAssets = collect();
        $exchangeBalances = collect();
        
        foreach ($apiKeys as $apiKey) {
            try {
                \Log::info("Fetching balance from exchange", [
                    'exchange' => $apiKey->exchange,
                    'api_key_name' => $apiKey->name
                ]);
                
                $exchangeService = new ExchangeService($apiKey);
                $balances = $exchangeService->getBalance();
                
                \Log::info("Raw balance response", [
                    'exchange' => $apiKey->exchange,
                    'balances' => $balances
                ]);
                
                if (is_array($balances)) {
                    foreach ($balances as $balance) {
                        // Skip zero balances and small amounts
                        if (isset($balance['available']) && $balance['available'] > 0) {
                            $balance['exchange'] = $apiKey->exchange;
                            $balance['api_key_name'] = $apiKey->name;
                            $exchangeBalances->push($balance);
                            
                            \Log::info("Added balance", [
                                'currency' => $balance['currency'] ?? 'unknown',
                                'available' => $balance['available'],
                                'exchange' => $apiKey->exchange
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching balance from {$apiKey->exchange}: " . $e->getMessage());
                continue;
            }
        }
        
        \Log::info("Total exchange balances found", ['count' => $exchangeBalances->count()]);

        // Combine local holdings with exchange assets
        $allHoldings = collect();
        
        // Add local holdings
        foreach ($localHoldings as $holding) {
            $allHoldings->push([
                'type' => 'local',
                'symbol' => $holding->asset->symbol,
                'name' => $holding->asset->name,
                'quantity' => $holding->quantity,
                'current_price' => $holding->asset->current_price,
                'current_value' => $holding->current_value,
                'total_invested' => $holding->total_invested,
                'profit_loss' => $holding->profit_loss,
                'profit_loss_percentage' => $holding->profit_loss_percentage,
                'exchange' => 'Local Database',
                'api_key_name' => 'Manual Entry'
            ]);
        }
        
        // Add exchange assets
        foreach ($exchangeBalances as $balance) {
            $currency = $balance['currency'] ?? $balance['asset'] ?? 'Unknown';
            $available = $balance['available'] ?? $balance['free'] ?? 0;
            $total = $balance['total'] ?? $balance['balance'] ?? $available;
            
            // Skip very small amounts (less than $1 equivalent)
            if ($available <= 0.001) {
                continue;
            }
            
            // Get current price for the asset
            $currentPrice = 0;
            $assetName = $currency;
            
            // Try to get asset info from database
            $asset = Asset::where('symbol', $currency)->orWhere('symbol', $currency . '-USDT')->first();
            if ($asset) {
                $currentPrice = $asset->current_price;
                $assetName = $asset->name;
            } else {
                // For USDT, use 1:1 ratio
                if ($currency === 'USDT') {
                    $currentPrice = 1;
                }
            }
            
            $currentValue = $available * $currentPrice;
            
            // Only include if worth more than $1
            if ($currentValue >= 1) {
                $allHoldings->push([
                    'type' => 'exchange',
                    'symbol' => $currency,
                    'name' => $assetName,
                    'quantity' => $available,
                    'current_price' => $currentPrice,
                    'current_value' => $currentValue,
                    'total_invested' => 0, // We don't have this info from exchange
                    'profit_loss' => 0, // We don't have this info from exchange
                    'profit_loss_percentage' => 0, // We don't have this info from exchange
                    'exchange' => ucfirst($balance['exchange']),
                    'api_key_name' => $balance['api_key_name']
                ]);
            }
        }
        
        // Sort by current value (highest first)
        $holdings = $allHoldings->sortByDesc('current_value');
        
        // Calculate totals
        $totalPortfolioValue = $holdings->sum('current_value');
        $totalInvested = $holdings->sum('total_invested');
        $totalProfitLoss = $totalPortfolioValue - $totalInvested;
        
        // Group by exchange for summary
        $exchangeSummary = $holdings->groupBy('exchange')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_value' => $group->sum('current_value'),
                'total_invested' => $group->sum('total_invested'),
                'profit_loss' => $group->sum('profit_loss')
            ];
        });

        return view('assets.portfolio', compact(
            'holdings', 
            'totalPortfolioValue', 
            'totalInvested', 
            'totalProfitLoss',
            'exchangeSummary',
            'apiKeys'
        ));
    }

    public function buy(Request $request)
    {
        $request->validate([
            'api_key_id' => 'required|exists:api_keys,id',
            'asset_id' => 'required|exists:assets,id',
            'quantity' => 'required|numeric|min:0.00000001',
            'price_per_unit' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500'
        ]);

        $apiKey = ApiKey::where('id', $request->api_key_id)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        $asset = Asset::findOrFail($request->asset_id);
        $quantity = $request->quantity;
        $pricePerUnit = $request->price_per_unit;
        $totalAmount = $quantity * $pricePerUnit;

        try {
            DB::beginTransaction();

            // Initialize exchange service with API key
            $exchangeService = new ExchangeService($apiKey);

            // Place buy order on exchange
            $orderResult = $exchangeService->placeBuyOrder($asset->symbol, $quantity, $pricePerUnit);

            // Create transaction record
            $transaction = AssetTransaction::create([
                'user_id' => Auth::id(),
                'asset_id' => $asset->id,
                'type' => 'buy',
                'quantity' => $quantity,
                'price_per_unit' => $pricePerUnit,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'notes' => $request->notes,
                'exchange_order_id' => $orderResult['data']['orderId'] ?? null,
                'exchange_response' => json_encode($orderResult)
            ]);

            // Update or create user holding
            $holding = UserAssetHolding::firstOrNew([
                'user_id' => Auth::id(),
                'asset_id' => $asset->id
            ]);

            if ($holding->exists) {
                // Update existing holding
                $newTotalQuantity = $holding->quantity + $quantity;
                $newTotalInvested = $holding->total_invested + $totalAmount;
                $newAveragePrice = $newTotalInvested / $newTotalQuantity;

                $holding->update([
                    'quantity' => $newTotalQuantity,
                    'average_buy_price' => $newAveragePrice,
                    'total_invested' => $newTotalInvested
                ]);
            } else {
                // Create new holding
                $holding->fill([
                    'quantity' => $quantity,
                    'average_buy_price' => $pricePerUnit,
                    'total_invested' => $totalAmount
                ]);
                $holding->save();
            }

            DB::commit();

            return redirect()->route('assets.portfolio')
                ->with('success', "Successfully bought {$quantity} {$asset->symbol} for $" . number_format($totalAmount, 2) . " via {$apiKey->exchange}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Transaction failed: ' . $e->getMessage());
        }
    }

    public function sell(Request $request)
    {
        $request->validate([
            'api_key_id' => 'required|exists:api_keys,id',
            'asset_id' => 'required|exists:assets,id',
            'quantity' => 'required|numeric|min:0.00000001',
            'price_per_unit' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500'
        ]);

        $apiKey = ApiKey::where('id', $request->api_key_id)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        $asset = Asset::findOrFail($request->asset_id);
        $quantity = $request->quantity;
        $pricePerUnit = $request->price_per_unit;
        $totalAmount = $quantity * $pricePerUnit;

        // Check if user has enough quantity
        $holding = UserAssetHolding::where('user_id', Auth::id())
            ->where('asset_id', $asset->id)
            ->first();

        if (!$holding || $holding->quantity < $quantity) {
            return back()->with('error', 'Insufficient quantity to sell');
        }

        try {
            DB::beginTransaction();

            // Initialize exchange service with API key
            $exchangeService = new ExchangeService($apiKey);

            // Place sell order on exchange
            $orderResult = $exchangeService->placeSellOrder($asset->symbol, $quantity, $pricePerUnit);

            // Create transaction record
            $transaction = AssetTransaction::create([
                'user_id' => Auth::id(),
                'asset_id' => $asset->id,
                'type' => 'sell',
                'quantity' => $quantity,
                'price_per_unit' => $pricePerUnit,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'notes' => $request->notes,
                'exchange_order_id' => $orderResult['data']['orderId'] ?? null,
                'exchange_response' => json_encode($orderResult)
            ]);

            // Update holding
            $remainingQuantity = $holding->quantity - $quantity;
            
            if ($remainingQuantity > 0) {
                // Update existing holding
                $holding->update([
                    'quantity' => $remainingQuantity,
                    'total_invested' => ($holding->total_invested / $holding->quantity) * $remainingQuantity
                ]);
            } else {
                // Delete holding if quantity becomes 0
                $holding->delete();
            }

            DB::commit();

            return redirect()->route('assets.portfolio')
                ->with('success', "Successfully sold {$quantity} {$asset->symbol} for $" . number_format($totalAmount, 2) . " via {$apiKey->exchange}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Transaction failed: ' . $e->getMessage());
        }
    }

    public function transactions()
    {
        $transactions = Auth::user()->assetTransactions()
            ->with('asset')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('assets.transactions', compact('transactions'));
    }

    public function show(Asset $asset)
    {
        $userHolding = Auth::user()->assetHoldings()
            ->where('asset_id', $asset->id)
            ->first();

        $recentTransactions = AssetTransaction::where('asset_id', $asset->id)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $apiKeys = Auth::user()->apiKeys()->where('is_active', true)->get();

        return view('assets.show', compact('asset', 'userHolding', 'recentTransactions', 'apiKeys'));
    }

    public function syncAssets(Request $request)
    {
        $request->validate([
            'api_key_id' => 'required|exists:api_keys,id'
        ]);

        $apiKey = ApiKey::where('id', $request->api_key_id)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $exchangeService = new ExchangeService($apiKey);
            $syncedCount = $exchangeService->syncAssets();

            return back()->with('success', "Successfully synced {$syncedCount} assets from {$apiKey->exchange}");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync assets: ' . $e->getMessage());
        }
    }

    public function updatePrices(Request $request)
    {
        $request->validate([
            'api_key_id' => 'required|exists:api_keys,id'
        ]);

        $apiKey = ApiKey::where('id', $request->api_key_id)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $exchangeService = new ExchangeService($apiKey);
            $updatedCount = $exchangeService->updatePrices();

            return back()->with('success', "Successfully updated prices for {$updatedCount} assets from {$apiKey->exchange}");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update prices: ' . $e->getMessage());
        }
    }

    public function getBalance(Request $request)
    {
        $request->validate([
            'api_key_id' => 'required|exists:api_keys,id'
        ]);

        $apiKey = ApiKey::where('id', $request->api_key_id)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $exchangeService = new ExchangeService($apiKey);
            $balance = $exchangeService->getAccountBalance();

            return response()->json([
                'success' => true,
                'balance' => $balance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refreshPortfolio()
    {
        try {
            $apiKeys = Auth::user()->apiKeys()->where('is_active', true)->get();
            $updatedCount = 0;
            $errors = [];

            foreach ($apiKeys as $apiKey) {
                try {
                    $exchangeService = new ExchangeService($apiKey);
                    $balances = $exchangeService->getBalance();
                    
                    if (is_array($balances)) {
                        foreach ($balances as $balance) {
                            if (isset($balance['available']) && $balance['available'] > 0) {
                                $currency = $balance['currency'] ?? $balance['asset'] ?? 'Unknown';
                                
                                // Try to find or create asset
                                $asset = Asset::where('symbol', $currency)
                                    ->orWhere('symbol', $currency . '-USDT')
                                    ->first();
                                
                                if (!$asset) {
                                    // Create basic asset record
                                    $asset = Asset::create([
                                        'symbol' => $currency,
                                        'name' => $currency,
                                        'type' => 'crypto',
                                        'current_price' => $currency === 'USDT' ? 1 : 0,
                                        'is_active' => true
                                    ]);
                                }
                                
                                $updatedCount++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error updating {$apiKey->exchange}: " . $e->getMessage();
                    \Log::error("Error refreshing portfolio for {$apiKey->exchange}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Portfolio refreshed successfully. Updated {$updatedCount} assets.",
                'updated_count' => $updatedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh portfolio: ' . $e->getMessage()
            ], 500);
        }
    }
}
