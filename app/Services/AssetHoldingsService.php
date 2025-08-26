<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\UserAssetHolding;
use App\Models\Trade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AssetHoldingsService
{
    /**
     * Update asset holdings after a trade
     */
    public function updateHoldingsAfterTrade(Trade $trade): void
    {
        try {
            $assetSymbol = $this->extractAssetSymbol($trade->symbol);
            $asset = Asset::where('symbol', $assetSymbol)->first();
            
            if (!$asset) {
                Log::warning("Asset not found for symbol: {$assetSymbol}");
                return;
            }
            
            // Get or create user asset holding
            $userHolding = UserAssetHolding::firstOrCreate(
                [
                    'user_id' => $trade->tradingBot->user_id,
                    'asset_id' => $asset->id,
                ],
                [
                    'quantity' => 0,
                    'average_buy_price' => 0,
                    'total_invested' => 0,
                ]
            );
            
            if ($trade->side === 'buy') {
                $this->updateHoldingsForBuy($userHolding, $trade);
            } else {
                $this->updateHoldingsForSell($userHolding, $trade);
            }
            
            Log::info("✅ [HOLDINGS] Updated holdings for {$assetSymbol}: {$userHolding->quantity} @ {$userHolding->average_buy_price}");
            
        } catch (\Exception $e) {
            Log::error("❌ [HOLDINGS] Error updating holdings: " . $e->getMessage());
        }
    }
    
    /**
     * Update holdings for a buy trade
     */
    private function updateHoldingsForBuy(UserAssetHolding $holding, Trade $trade): void
    {
        $newQuantity = $holding->quantity + $trade->quantity;
        $newTotalInvested = $holding->total_invested + ($trade->quantity * $trade->price);
        
        // Calculate new average buy price
        $newAveragePrice = $newQuantity > 0 ? $newTotalInvested / $newQuantity : 0;
        
        $holding->update([
            'quantity' => $newQuantity,
            'average_buy_price' => $newAveragePrice,
            'total_invested' => $newTotalInvested,
        ]);
        
        Log::info("📈 [HOLDINGS BUY] Added {$trade->quantity} at {$trade->price}, new total: {$newQuantity} @ {$newAveragePrice}");
    }
    
    /**
     * Update holdings for a sell trade
     */
    private function updateHoldingsForSell(UserAssetHolding $holding, Trade $trade): void
    {
        $newQuantity = $holding->quantity - $trade->quantity;
        
        // Ensure we don't go negative
        if ($newQuantity < 0) {
            Log::warning("⚠️ [HOLDINGS SELL] Attempted to sell more than available: {$trade->quantity} > {$holding->quantity}");
            $newQuantity = 0;
        }
        
        // Calculate new total invested (proportional reduction)
        $proportionSold = $trade->quantity / $holding->quantity;
        $costOfSold = $holding->total_invested * $proportionSold;
        $newTotalInvested = $holding->total_invested - $costOfSold;
        
        // Average buy price remains the same for remaining holdings
        $newAveragePrice = $newQuantity > 0 ? $newTotalInvested / $newQuantity : 0;
        
        $holding->update([
            'quantity' => $newQuantity,
            'average_buy_price' => $newAveragePrice,
            'total_invested' => $newTotalInvested,
        ]);
        
        Log::info("📉 [HOLDINGS SELL] Sold {$trade->quantity} at {$trade->price}, new total: {$newQuantity} @ {$newAveragePrice}");
    }
    
    /**
     * Get current holdings for a user and asset
     */
    public function getCurrentHoldings(int $userId, string $assetSymbol): ?UserAssetHolding
    {
        $asset = Asset::where('symbol', $assetSymbol)->first();
        
        if (!$asset) {
            return null;
        }
        
        return UserAssetHolding::where('user_id', $userId)
            ->where('asset_id', $asset->id)
            ->first();
    }
    
    /**
     * Calculate 10% of current holdings
     */
    public function calculateTenPercentOfHoldings(int $userId, string $assetSymbol): float
    {
        $holding = $this->getCurrentHoldings($userId, $assetSymbol);
        
        if (!$holding || $holding->quantity <= 0) {
            return 0;
        }
        
        return $holding->quantity * 0.10;
    }
    
    /**
     * Check if user has sufficient holdings for a sell order
     */
    public function hasSufficientHoldings(int $userId, string $assetSymbol, float $quantity): bool
    {
        $holding = $this->getCurrentHoldings($userId, $assetSymbol);
        
        if (!$holding) {
            return false;
        }
        
        return $holding->quantity >= $quantity;
    }
    
    /**
     * Extract asset symbol from trading pair
     */
    private function extractAssetSymbol(string $tradingPair): string
    {
        return explode('-', $tradingPair)[0];
    }
    
    /**
     * Get holdings summary for a user
     */
    public function getHoldingsSummary(int $userId): array
    {
        $holdings = UserAssetHolding::where('user_id', $userId)
            ->with('asset')
            ->where('quantity', '>', 0)
            ->get();
        
        $summary = [];
        
        foreach ($holdings as $holding) {
            $summary[] = [
                'symbol' => $holding->asset->symbol,
                'quantity' => $holding->quantity,
                'average_price' => $holding->average_buy_price,
                'total_invested' => $holding->total_invested,
                'current_value' => $holding->current_value,
                'profit_loss' => $holding->profit_loss,
                'profit_loss_percentage' => $holding->profit_loss_percentage,
            ];
        }
        
        return $summary;
    }
    
    /**
     * Update asset current prices (should be called periodically)
     */
    public function updateAssetPrices(): void
    {
        try {
            $assets = Asset::where('is_active', true)->get();
            
            foreach ($assets as $asset) {
                // This would typically call an external API to get current prices
                // For now, we'll just log that this should be implemented
                Log::info("📊 [ASSET PRICE] Should update price for {$asset->symbol}");
            }
            
        } catch (\Exception $e) {
            Log::error("❌ [ASSET PRICE] Error updating asset prices: " . $e->getMessage());
        }
    }

    /**
     * Sync assets with the exchange to get the latest holdings and balances
     */
    public function syncAssetsWithExchange(int $userId, $apiKey = null): void
    {
        try {
            Log::info("🔄 [ASSET SYNC] Starting asset synchronization for user ID: {$userId}");
            
            // Get exchange balances
            $exchangeService = new ExchangeService($apiKey);
            $balances = $exchangeService->getBalance();
            
            if (empty($balances)) {
                Log::warning("⚠️ [ASSET SYNC] No balances received from exchange");
                return;
            }
            
            Log::info("📊 [ASSET SYNC] Received " . count($balances) . " balance entries from exchange");
            
            foreach ($balances as $balance) {
                $currency = $balance['currency'] ?? $balance['asset'] ?? null;
                $available = (float) ($balance['available'] ?? $balance['free'] ?? 0);
                $total = (float) ($balance['total'] ?? $balance['balance'] ?? 0);
                
                // For Binance, we need to check if the asset has any balance (free or locked)
                $hasBalance = $available > 0 || (isset($balance['locked']) && (float)$balance['locked'] > 0);
                
                if (!$currency || !$hasBalance) {
                    continue; // Skip zero balances
                }
                
                Log::info("📈 [ASSET SYNC] Processing {$currency}: Available={$available}, Total={$total}, Locked=" . ($balance['locked'] ?? 0));
                
                // Get or create asset
                $asset = Asset::firstOrCreate(
                    ['symbol' => $currency],
                    [
                        'name' => $currency,
                        'current_price' => 0, // Will be updated separately
                        'type' => 'crypto',
                        'is_active' => true
                    ]
                );
                
                // Get or create user asset holding
                $userHolding = UserAssetHolding::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'asset_id' => $asset->id,
                    ],
                    [
                        'quantity' => 0,
                        'average_buy_price' => 0,
                        'total_invested' => 0,
                    ]
                );
                
                // Update with current exchange balance (use available balance for trading)
                $oldQuantity = $userHolding->quantity;
                $userHolding->update([
                    'quantity' => $available,
                ]);
                
                Log::info("✅ [ASSET SYNC] Updated {$currency}: {$oldQuantity} → {$available} (Available for trading)");
            }
            
            Log::info("✅ [ASSET SYNC] Asset synchronization completed successfully");
            
        } catch (\Exception $e) {
            Log::error("❌ [ASSET SYNC] Error during asset synchronization: " . $e->getMessage());
            Log::error("🔍 [STACK] " . $e->getTraceAsString());
        }
    }
}
