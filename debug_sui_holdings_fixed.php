<?php

/**
 * Debug SUI Holdings Issue - Fixed Version
 * 
 * This script tests the fix for why 203 SUI coins are not showing in the spot trading bot
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

echo "🔍 Debug SUI Holdings Issue - Fixed Version\n";
echo "==========================================\n\n";

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
    echo "   User ID: {$bot->user_id}\n";
    echo "   API Key ID: {$bot->api_key_id}\n\n";
    
    // Check if bot has API key
    $apiKey = $bot->apiKey;
    if (!$apiKey) {
        echo "❌ Bot has no API key associated\n";
        exit;
    }
    
    echo "🔑 API Key Details:\n";
    echo "   Exchange: {$apiKey->exchange}\n";
    echo "   Has Trade Permission: " . ($apiKey->hasPermission('trade') ? 'Yes' : 'No') . "\n";
    echo "   Is Active: " . ($apiKey->is_active ? 'Yes' : 'No') . "\n\n";
    
    // Step 1: Test Exchange Balance with API Key
    echo "📊 Step 1: Test Exchange Balance with API Key\n";
    echo "--------------------------------------------\n";
    
    $exchangeService = new ExchangeService($apiKey);
    
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
            if (count($balances) > 0) {
                echo "   Available currencies: " . implode(', ', array_unique(array_column($balances, 'currency'))) . "\n";
            }
            echo "\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Error fetching exchange balance: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    }
    
    // Step 2: Test Asset Synchronization with API Key
    echo "🔄 Step 2: Test Asset Synchronization with API Key\n";
    echo "------------------------------------------------\n";
    
    $assetHoldingsService = new AssetHoldingsService();
    
    try {
        $assetHoldingsService->syncAssetsWithExchange($bot->user_id, $apiKey);
        echo "✅ Asset synchronization completed\n\n";
        
        // Check SUI holdings after sync
        $asset = Asset::where('symbol', 'SUI')->first();
        if ($asset) {
            $userHolding = UserAssetHolding::where('user_id', $bot->user_id)
                ->where('asset_id', $asset->id)
                ->first();
            
            if ($userHolding) {
                echo "📊 SUI holding after sync:\n";
                echo "   Quantity: {$userHolding->quantity}\n";
                echo "   Average Price: {$userHolding->average_buy_price}\n";
                echo "   Total Invested: {$userHolding->total_invested}\n\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "❌ Error during asset synchronization: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    }
    
    // Step 3: Test Controller Logic
    echo "🎮 Step 3: Test Controller Logic\n";
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
    
    // Step 4: Test USDT Balance
    echo "💰 Step 4: Test USDT Balance\n";
    echo "---------------------------\n";
    
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
    
    // Step 5: Test Bot Card Display Data
    echo "🎨 Step 5: Test Bot Card Display Data\n";
    echo "-----------------------------------\n";
    
    // Simulate controller logic
    $assetHolding = $assetHoldingsService->getCurrentHoldings($bot->user_id, $assetSymbol);
    $assetQuantity = $assetHolding ? $assetHolding->quantity : 0;
    $assetAveragePrice = $assetHolding ? $assetHolding->average_buy_price : 0;
    
    echo "   Asset Holdings Display: " . number_format($assetQuantity, 6) . " {$assetSymbol}\n";
    if ($assetAveragePrice > 0) {
        echo "   Average Price Display: $" . number_format($assetAveragePrice, 4) . "\n";
    } else {
        echo "   Average Price Display: No holdings\n";
    }
    echo "   USDT Balance Display: $" . number_format($usdtBalance, 2) . "\n\n";
    
    // Summary
    echo "📋 Summary\n";
    echo "----------\n";
    
    if ($suiBalance && $suiBalance['available'] > 0) {
        echo "✅ SUI balance found in exchange: {$suiBalance['available']}\n";
    } else {
        echo "❌ No SUI balance found in exchange\n";
    }
    
    if ($assetHolding && $assetHolding->quantity > 0) {
        echo "✅ SUI holdings in database: {$assetHolding->quantity}\n";
    } else {
        echo "❌ No SUI holdings in database\n";
    }
    
    if ($usdtBalance > 0) {
        echo "✅ USDT balance available: {$usdtBalance}\n";
    } else {
        echo "❌ No USDT balance available\n";
    }
    
    echo "\n🎯 Expected Result:\n";
    echo "If your 203 SUI coins are in your Binance account, they should now appear in the bot card.\n";
    echo "The fix ensures that the ExchangeService uses the correct API key to fetch balances.\n";
    
} catch (Exception $e) {
    echo "❌ Error during debugging: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
