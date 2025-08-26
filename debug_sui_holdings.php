<?php

/**
 * Debug SUI Holdings Issue
 * 
 * This script investigates why 203 SUI coins are not showing in the spot trading bot
 */

require_once 'vendor/autoload.php';

use App\Services\TradingBotService;
use App\Services\AssetHoldingsService;
use App\Services\ExchangeService;
use App\Models\TradingBot;
use App\Models\Asset;
use App\Models\UserAssetHolding;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debug SUI Holdings Issue\n";
echo "==========================\n\n";

try {
    // Get the first spot trading bot
    $bot = TradingBot::first();
    
    if (!$bot) {
        echo "❌ No spot trading bots found in the system.\n";
        exit;
    }
    
    echo "🤖 Bot Details:\n";
    echo "   Name: {$bot->name}\n";
    echo "   Symbol: {$bot->symbol}\n";
    echo "   Exchange: {$bot->exchange}\n";
    echo "   User ID: {$bot->user_id}\n\n";
    
    // Step 1: Check Exchange Balance
    echo "📊 Step 1: Check Exchange Balance\n";
    echo "--------------------------------\n";
    
    $exchangeService = new ExchangeService();
    
    try {
        $balances = $exchangeService->getBalance();
        echo "✅ Exchange balance fetched successfully\n";
        echo "   Total balance entries: " . count($balances) . "\n\n";
        
        // Look for SUI balance
        $suiBalance = null;
        $usdtBalance = null;
        
        foreach ($balances as $balance) {
            $currency = $balance['currency'] ?? $balance['asset'] ?? null;
            $available = (float) ($balance['available'] ?? $balance['free'] ?? 0);
            $total = (float) ($balance['total'] ?? $balance['balance'] ?? 0);
            
            if ($currency === 'SUI') {
                $suiBalance = $balance;
                echo "🎯 Found SUI balance:\n";
                echo "   Available: {$available}\n";
                echo "   Total: {$total}\n";
                echo "   Raw data: " . json_encode($balance) . "\n\n";
            }
            
            if ($currency === 'USDT') {
                $usdtBalance = $balance;
                echo "💰 Found USDT balance:\n";
                echo "   Available: {$available}\n";
                echo "   Total: {$total}\n\n";
            }
        }
        
        if (!$suiBalance) {
            echo "❌ No SUI balance found in exchange response\n";
            echo "   Available currencies: " . implode(', ', array_unique(array_column($balances, 'currency'))) . "\n\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Error fetching exchange balance: " . $e->getMessage() . "\n\n";
    }
    
    // Step 2: Check Asset Database
    echo "📋 Step 2: Check Asset Database\n";
    echo "-------------------------------\n";
    
    $asset = Asset::where('symbol', 'SUI')->first();
    if ($asset) {
        echo "✅ SUI asset found in database:\n";
        echo "   ID: {$asset->id}\n";
        echo "   Symbol: {$asset->symbol}\n";
        echo "   Name: {$asset->name}\n";
        echo "   Current Price: {$asset->current_price}\n";
        echo "   Active: " . ($asset->is_active ? 'Yes' : 'No') . "\n\n";
    } else {
        echo "❌ SUI asset not found in database\n";
        echo "   Creating SUI asset...\n";
        
        $asset = Asset::create([
            'symbol' => 'SUI',
            'name' => 'Sui',
            'current_price' => 0,
            'type' => 'crypto',
            'is_active' => true
        ]);
        
        echo "✅ SUI asset created with ID: {$asset->id}\n\n";
    }
    
    // Step 3: Check User Asset Holdings
    echo "👤 Step 3: Check User Asset Holdings\n";
    echo "-----------------------------------\n";
    
    $userHolding = UserAssetHolding::where('user_id', $bot->user_id)
        ->where('asset_id', $asset->id)
        ->first();
    
    if ($userHolding) {
        echo "✅ User SUI holding found:\n";
        echo "   Quantity: {$userHolding->quantity}\n";
        echo "   Average Price: {$userHolding->average_buy_price}\n";
        echo "   Total Invested: {$userHolding->total_invested}\n";
        echo "   Updated At: {$userHolding->updated_at}\n\n";
    } else {
        echo "❌ No user SUI holding found\n";
        echo "   Creating user holding...\n";
        
        $userHolding = UserAssetHolding::create([
            'user_id' => $bot->user_id,
            'asset_id' => $asset->id,
            'quantity' => 0,
            'average_buy_price' => 0,
            'total_invested' => 0
        ]);
        
        echo "✅ User SUI holding created with ID: {$userHolding->id}\n\n";
    }
    
    // Step 4: Manual Asset Sync
    echo "🔄 Step 4: Manual Asset Synchronization\n";
    echo "--------------------------------------\n";
    
    $assetHoldingsService = new AssetHoldingsService();
    
    try {
        $assetHoldingsService->syncAssetsWithExchange($bot->user_id);
        echo "✅ Asset synchronization completed\n\n";
        
        // Check holdings after sync
        $updatedHolding = UserAssetHolding::where('user_id', $bot->user_id)
            ->where('asset_id', $asset->id)
            ->first();
        
        if ($updatedHolding) {
            echo "📊 Updated SUI holding after sync:\n";
            echo "   Quantity: {$updatedHolding->quantity}\n";
            echo "   Average Price: {$updatedHolding->average_buy_price}\n";
            echo "   Total Invested: {$updatedHolding->total_invested}\n\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Error during asset synchronization: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    }
    
    // Step 5: Test Controller Logic
    echo "🎮 Step 5: Test Controller Logic\n";
    echo "-------------------------------\n";
    
    $assetSymbol = explode('-', $bot->symbol)[0]; // Should be 'SUI'
    echo "   Asset symbol from trading pair: {$assetSymbol}\n";
    
    $testHolding = $assetHoldingsService->getCurrentHoldings($bot->user_id, $assetSymbol);
    if ($testHolding) {
        echo "✅ Controller logic test - SUI holding found:\n";
        echo "   Quantity: {$testHolding->quantity}\n";
        echo "   Average Price: {$testHolding->average_buy_price}\n\n";
    } else {
        echo "❌ Controller logic test - No SUI holding found\n\n";
    }
    
    // Step 6: Manual Balance Check
    echo "💰 Step 6: Manual USDT Balance Check\n";
    echo "-----------------------------------\n";
    
    try {
        $balances = $exchangeService->getBalance();
        $usdtBalance = 0;
        foreach ($balances as $balance) {
            $currency = $balance['currency'] ?? $balance['asset'] ?? null;
            if ($currency === 'USDT') {
                $usdtBalance = (float) ($balance['available'] ?? $balance['free'] ?? 0);
                break;
            }
        }
        echo "   USDT Balance: {$usdtBalance}\n\n";
    } catch (\Exception $e) {
        echo "❌ Error checking USDT balance: " . $e->getMessage() . "\n\n";
    }
    
    // Summary
    echo "📋 Summary\n";
    echo "----------\n";
    
    if ($suiBalance && $suiBalance['available'] > 0) {
        echo "✅ SUI balance found in exchange: {$suiBalance['available']}\n";
    } else {
        echo "❌ No SUI balance found in exchange\n";
    }
    
    if ($userHolding && $userHolding->quantity > 0) {
        echo "✅ SUI holdings in database: {$userHolding->quantity}\n";
    } else {
        echo "❌ No SUI holdings in database\n";
    }
    
    if ($usdtBalance > 0) {
        echo "✅ USDT balance available: {$usdtBalance}\n";
    } else {
        echo "❌ No USDT balance available\n";
    }
    
    echo "\n🎯 Next Steps:\n";
    echo "1. Check if SUI balance appears in exchange response\n";
    echo "2. Verify asset synchronization is working\n";
    echo "3. Check if holdings are being updated correctly\n";
    echo "4. Test the bot card display after fixes\n";
    
} catch (Exception $e) {
    echo "❌ Error during debugging: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
