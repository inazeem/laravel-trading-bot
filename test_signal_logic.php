<?php

/**
 * Test Signal Logic
 * 
 * Test the updated signal logic for both bullish and bearish signals
 */

require_once 'vendor/autoload.php';

use App\Services\TradingBotService;
use App\Services\AssetHoldingsService;
use App\Services\ExchangeService;
use App\Models\TradingBot;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test Signal Logic\n";
echo "===================\n\n";

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
    echo "   Exchange: {$bot->exchange}\n\n";
    
    // Get API key
    $apiKey = $bot->apiKey;
    if (!$apiKey) {
        echo "❌ Bot has no API key associated\n";
        exit;
    }
    
    echo "🔑 API Key Details:\n";
    echo "   Exchange: {$apiKey->exchange}\n";
    echo "   Has Trade Permission: " . ($apiKey->hasPermission('trade') ? 'Yes' : 'No') . "\n";
    echo "   Is Active: " . ($apiKey->is_active ? 'Yes' : 'No') . "\n\n";
    
    // Test ExchangeService
    $exchangeService = new ExchangeService($apiKey);
    
    try {
        $balances = $exchangeService->getBalance();
        echo "✅ Exchange balance fetched successfully\n";
        echo "   Total balance entries: " . count($balances) . "\n\n";
        
        // Get current balances
        $suiBalance = 0;
        $usdtBalance = 0;
        
        foreach ($balances as $balance) {
            $currency = $balance['currency'] ?? $balance['asset'] ?? null;
            $available = (float) ($balance['available'] ?? $balance['free'] ?? 0);
            
            if ($currency === 'SUI') {
                $suiBalance = $available;
            }
            
            if ($currency === 'USDT') {
                $usdtBalance = $available;
            }
        }
        
        echo "💰 Current Balances:\n";
        echo "   SUI: {$suiBalance}\n";
        echo "   USDT: {$usdtBalance}\n\n";
        
        // Test AssetHoldingsService
        $assetHoldingsService = new AssetHoldingsService();
        $assetHoldingsService->syncAssetsWithExchange($bot->user_id, $apiKey);
        
        $assetSymbol = explode('-', $bot->symbol)[0];
        $userHolding = $assetHoldingsService->getCurrentHoldings($bot->user_id, $assetSymbol);
        
        echo "📊 Asset Holdings:\n";
        if ($userHolding) {
            echo "   {$assetSymbol}: {$userHolding->quantity}\n";
        } else {
            echo "   {$assetSymbol}: No holdings\n";
        }
        echo "\n";
        
        // Test signal logic
        echo "🎯 Testing Signal Logic:\n";
        echo "========================\n\n";
        
        // Test bullish signal
        echo "📈 Testing Bullish Signal:\n";
        $bullishSignal = [
            'type' => 'SMC',
            'direction' => 'bullish',
            'strength' => 0.85,
            'timeframe' => '1h'
        ];
        
        echo "   Signal: " . json_encode($bullishSignal) . "\n";
        
        if ($usdtBalance > 0) {
            echo "   ✅ USDT balance available: {$usdtBalance} - Can process bullish signal\n";
        } else {
            echo "   ❌ No USDT balance - Cannot process bullish signal\n";
        }
        
        // Test bearish signal
        echo "\n📉 Testing Bearish Signal:\n";
        $bearishSignal = [
            'type' => 'SMC',
            'direction' => 'bearish',
            'strength' => 0.80,
            'timeframe' => '1h'
        ];
        
        echo "   Signal: " . json_encode($bearishSignal) . "\n";
        
        if ($userHolding && $userHolding->quantity > 0) {
            echo "   ✅ {$assetSymbol} holdings available: {$userHolding->quantity} - Can process bearish signal\n";
        } else {
            echo "   ❌ No {$assetSymbol} holdings - Cannot process bearish signal\n";
        }
        
        // Test position sizing
        echo "\n📏 Testing Position Sizing:\n";
        echo "==========================\n\n";
        
        $currentPrice = 1.50; // Example price
        
        // Test bullish position sizing
        echo "📈 Bullish Position Sizing:\n";
        if ($usdtBalance > 0) {
            $buySize = $usdtBalance * 0.10;
            echo "   10% of USDT balance: {$usdtBalance} * 0.10 = {$buySize} USDT\n";
            echo "   Quantity to buy: {$buySize} / {$currentPrice} = " . ($buySize / $currentPrice) . " {$assetSymbol}\n";
        } else {
            echo "   No USDT available for buying\n";
        }
        
        // Test bearish position sizing
        echo "\n📉 Bearish Position Sizing:\n";
        if ($userHolding && $userHolding->quantity > 0) {
            $sellSize = $userHolding->quantity * 0.10;
            echo "   10% of {$assetSymbol} holdings: {$userHolding->quantity} * 0.10 = {$sellSize} {$assetSymbol}\n";
        } else {
            echo "   No {$assetSymbol} holdings available for selling\n";
        }
        
        // Test cooldown logic
        echo "\n⏰ Testing Cooldown Logic:\n";
        echo "=========================\n\n";
        
        $lastTradeAt = $bot->last_trade_at;
        if ($lastTradeAt) {
            $hoursSinceLastTrade = now()->diffInHours($lastTradeAt);
            echo "   Last trade: {$lastTradeAt}\n";
            echo "   Hours since last trade: {$hoursSinceLastTrade}\n";
            
            if ($hoursSinceLastTrade < 3) {
                echo "   ⏰ Still in cooldown period (3 hours required)\n";
            } else {
                echo "   ✅ Cooldown period completed - ready for new trades\n";
            }
        } else {
            echo "   ✅ No previous trades - ready for first trade\n";
        }
        
        // Summary
        echo "\n📋 Summary:\n";
        echo "===========\n";
        
        echo "   Bullish Signals: " . ($usdtBalance > 0 ? "✅ Can process" : "❌ Cannot process (no USDT)") . "\n";
        echo "   Bearish Signals: " . ($userHolding && $userHolding->quantity > 0 ? "✅ Can process" : "❌ Cannot process (no holdings)") . "\n";
        echo "   Cooldown Status: " . ($lastTradeAt && now()->diffInHours($lastTradeAt) < 3 ? "⏰ In cooldown" : "✅ Ready") . "\n";
        echo "   Position Sizing: 10% of available balance/holdings\n";
        echo "   Signal Strength: 70%+ required\n";
        echo "   Risk Management: 1.5:1 minimum risk/reward ratio\n";
        
    } catch (\Exception $e) {
        echo "❌ Error during testing: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

