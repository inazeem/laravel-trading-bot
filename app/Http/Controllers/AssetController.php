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
        $holdings = Auth::user()->assetHoldings()
            ->with('asset')
            ->where('quantity', '>', 0)
            ->get()
            ->filter(function ($holding) {
                return $holding->current_value > 10; // Only show holdings worth more than $10
            })
            ->sortByDesc('current_value');

        $totalPortfolioValue = $holdings->sum('current_value');
        $totalInvested = $holdings->sum('total_invested');
        $totalProfitLoss = $totalPortfolioValue - $totalInvested;

        return view('assets.portfolio', compact('holdings', 'totalPortfolioValue', 'totalInvested', 'totalProfitLoss'));
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
}
