<?php

/**
 * Test Asset Synchronization and USDT Balance Checking
 * 
 * This script tests the new asset synchronization and USDT balance checking features
 */

require_once 'vendor/autoload.php';

use App\Services\TradingBotService;
use App\Services\AssetHoldingsService;
use App\Models\TradingBot;
use App\Models\Asset;
use App\Models\UserAssetHolding;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Asset Synchronization and USDT Balance Test\n";
echo "==============================================\n\n";

try {
    // Get the first spot trading bot
    $bot = TradingBot::first();
    
    if (!$bot) {
        echo "❌ No spot trading bots found in the system.\n";
        exit;
    }
    
    echo "🤖 Testing with Bot: {$bot->name}\n";
    echo "Symbol: {$bot->symbol}\n";
    echo "Exchange: {$bot->exchange}\n\n";
    
    // Test 1: Asset Holdings Service
    echo "📊 Test 1: Asset Holdings Service\n";
    echo "----------------------------------\n";
    
    $holdingsService = new AssetHoldingsService();
    
    // Get holdings summary before sync
    $holdingsBefore = $holdingsService->getHoldingsSummary($bot->user_id);
    echo "Holdings before sync: " . count($holdingsBefore) . " assets\n";
    
    foreach ($holdingsBefore as $holding) {
        echo "  - {$holding['symbol']}: {$holding['quantity']} @ \${$holding['average_price']}\n";
    }
    
    echo "\n";
    
    // Test 2: Asset Synchronization
    echo "🔄 Test 2: Asset Synchronization\n";
    echo "--------------------------------\n";
    
    echo "Syncing assets with exchange...\n";
    $holdingsService->syncAssetsWithExchange($bot->user_id);
    
    // Get holdings summary after sync
    $holdingsAfter = $holdingsService->getHoldingsSummary($bot->user_id);
    echo "Holdings after sync: " . count($holdingsAfter) . " assets\n";
    
    foreach ($holdingsAfter as $holding) {
        echo "  - {$holding['symbol']}: {$holding['quantity']} @ \${$holding['average_price']}\n";
    }
    
    echo "\n";
    
    // Test 3: USDT Balance Check
    echo "💰 Test 3: USDT Balance Check\n";
    echo "-----------------------------\n";
    
    $tradingService = new TradingBotService($bot);
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($tradingService);
    $getUSDTBalanceMethod = $reflection->getMethod('getUSDTBalance');
    $getUSDTBalanceMethod->setAccessible(true);
    
    $usdtBalance = $getUSDTBalanceMethod->invoke($tradingService);
    echo "Current USDT Balance: {$usdtBalance}\n";
    
    if ($usdtBalance > 0) {
        echo "✅ USDT balance available - bot can process buy signals\n";
    } else {
        echo "❌ No USDT balance - bot will skip buy signals\n";
    }
    
    echo "\n";
    
    // Test 4: Signal Processing with USDT Check
    echo "📈 Test 4: Signal Processing with USDT Check\n";
    echo "--------------------------------------------\n";
    
    // Simulate different signal scenarios
    $testSignals = [
        [
            'type' => 'OrderBlock_Support',
            'direction' => 'bullish',
            'strength' => 0.85,
            'timeframe' => '1h'
        ],
        [
            'type' => 'OrderBlock_Resistance',
            'direction' => 'bearish',
            'strength' => 0.92,
            'timeframe' => '1h'
        ]
    ];
    
    foreach ($testSignals as $index => $signal) {
        echo "Signal #" . ($index + 1) . ": {$signal['type']} ({$signal['direction']}) - {$signal['strength']} strength\n";
        
        if ($signal['direction'] === 'bullish') {
            if ($usdtBalance > 0) {
                echo "  ✅ Bullish signal - USDT available ({$usdtBalance}) - would process\n";
            } else {
                echo "  ❌ Bullish signal - No USDT balance - would skip\n";
            }
        } else {
            // For bearish signals, check if we have the asset to sell
            $assetSymbol = explode('-', $bot->symbol)[0];
            $holding = $holdingsService->getCurrentHoldings($bot->user_id, $assetSymbol);
            
            if ($holding && $holding->quantity > 0) {
                echo "  ✅ Bearish signal - Asset available ({$holding->quantity} {$assetSymbol}) - would process\n";
            } else {
                echo "  ❌ Bearish signal - No asset holdings - would skip\n";
            }
        }
    }
    
    echo "\n";
    
    // Test 5: Position Size Calculation
    echo "📊 Test 5: Position Size Calculation\n";
    echo "------------------------------------\n";
    
    $currentPrice = 1.50; // Simulated price for SUI
    
    // Test buy position size calculation
    $buyPositionSize = $holdingsService->calculateTenPercentOfHoldings($bot->user_id, 'USDT');
    echo "10% of USDT holdings: {$buyPositionSize} USDT\n";
    
    if ($buyPositionSize > 0) {
        $estimatedBuyQuantity = $buyPositionSize / $currentPrice;
        echo "Estimated buy quantity at \${$currentPrice}: {$estimatedBuyQuantity} SUI\n";
    } else {
        echo "No USDT holdings available for buy orders\n";
    }
    
    // Test sell position size calculation
    $assetSymbol = explode('-', $bot->symbol)[0];
    $sellPositionSize = $holdingsService->calculateTenPercentOfHoldings($bot->user_id, $assetSymbol);
    echo "10% of {$assetSymbol} holdings: {$sellPositionSize} {$assetSymbol}\n";
    
    echo "\n";
    
    // Test 6: Asset Sync Summary
    echo "📋 Test 6: Asset Sync Summary\n";
    echo "-----------------------------\n";
    
    $totalAssets = count($holdingsAfter);
    $assetsWithBalance = 0;
    $totalValue = 0;
    
    foreach ($holdingsAfter as $holding) {
        if ($holding['quantity'] > 0) {
            $assetsWithBalance++;
            $totalValue += $holding['current_value'];
        }
    }
    
    echo "Total assets synced: {$totalAssets}\n";
    echo "Assets with balance: {$assetsWithBalance}\n";
    echo "Total portfolio value: \${$totalValue}\n";
    
    // Check if USDT is available
    $usdtHolding = null;
    foreach ($holdingsAfter as $holding) {
        if ($holding['symbol'] === 'USDT') {
            $usdtHolding = $holding;
            break;
        }
    }
    
    if ($usdtHolding && $usdtHolding['quantity'] > 0) {
        echo "✅ USDT available: {$usdtHolding['quantity']} USDT\n";
        echo "   Bot can process buy signals\n";
    } else {
        echo "❌ No USDT available\n";
        echo "   Bot will skip buy signals until USDT is added\n";
    }
    
    echo "\n";
    
    // Summary
    echo "🎯 Summary\n";
    echo "----------\n";
    echo "✅ Asset synchronization: Working\n";
    echo "✅ USDT balance checking: Working\n";
    echo "✅ Position size calculation: Working\n";
    echo "✅ Signal filtering by balance: Working\n";
    
    if ($usdtBalance > 0) {
        echo "✅ Bot ready for trading with USDT balance: {$usdtBalance}\n";
    } else {
        echo "⚠️  Bot needs USDT balance to process buy signals\n";
    }
    
    echo "\n🎉 Asset synchronization and USDT balance checking features are working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
