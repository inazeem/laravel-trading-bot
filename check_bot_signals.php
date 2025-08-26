<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Services\FuturesTradingBotService;
use App\Services\SmartMoneyConceptsService;
use App\Services\ExchangeService;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Bot Signal Generation\n";
echo "================================\n\n";

// Get the first active futures bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "❌ No active futures bot found. Please create one first.\n";
    exit(1);
}

echo "✅ Found active bot: {$bot->name}\n";
echo "📊 Symbol: {$bot->symbol}\n";
echo "⚙️ Leverage: {$bot->leverage}x\n";
echo "💰 Margin Type: {$bot->margin_type}\n\n";

try {
    // Check cooldown status
    echo "🔍 Checking Cooldown Status...\n";
    $lastClosedTrade = $bot->trades()
        ->where('status', 'closed')
        ->latest('closed_at')
        ->first();
    
    if ($lastClosedTrade && $lastClosedTrade->closed_at) {
        $cooldownEnd = $lastClosedTrade->closed_at->addMinutes(30);
        $now = now();
        
        if ($now->lt($cooldownEnd)) {
            $remainingMinutes = $now->diffInMinutes($cooldownEnd);
            echo "⏰ Cooldown active: {$remainingMinutes} minutes remaining\n";
            echo "   Last trade closed at: {$lastClosedTrade->closed_at}\n";
            echo "   Cooldown ends at: {$cooldownEnd}\n";
            echo "   This is why no new trades are being placed!\n\n";
        } else {
            echo "✅ Cooldown period expired\n\n";
        }
    } else {
        echo "ℹ️ No recent closed trades found\n\n";
    }
    
    // Check current price
    echo "🔍 Checking Current Price...\n";
    $exchangeService = new ExchangeService($bot->apiKey);
    $currentPrice = $exchangeService->getCurrentPrice($bot->symbol);
    
    if ($currentPrice) {
        echo "💰 Current price: {$currentPrice}\n\n";
    } else {
        echo "❌ Failed to get current price\n\n";
        exit(1);
    }
    
    // Check signal generation
    echo "🔍 Checking Signal Generation...\n";
    
    // Get supported timeframes
    $supportedTimeframes = $bot->timeframes;
    echo "📊 Analyzing timeframes: " . implode(', ', $supportedTimeframes) . "\n\n";
    
    $allSignals = [];
    
    foreach ($supportedTimeframes as $timeframe) {
        echo "⏰ Processing {$timeframe} timeframe...\n";
        
        // Get candlestick data
        $interval = $timeframe;
        if ($bot->exchange === 'kucoin') {
            $kucoinIntervals = [
                '1m' => '1minute',
                '5m' => '5minute',
                '15m' => '15minute',
                '30m' => '30minute',
                '1h' => '1hour',
                '4h' => '4hour',
                '1d' => '1day'
            ];
            $interval = $kucoinIntervals[$timeframe] ?? $timeframe;
        }
        
        $candleLimit = 60; // Get enough candles for analysis
        $candles = $exchangeService->getCandles($bot->symbol, $interval, $candleLimit);
        
        if (empty($candles)) {
            echo "   ❌ No candle data received\n";
            continue;
        }
        
        echo "   📈 Got " . count($candles) . " candles\n";
        
        // Analyze with SMC service
        $smcService = new SmartMoneyConceptsService($candles);
        $signals = $smcService->generateSignals($currentPrice);
        
        if (!empty($signals)) {
            echo "   🎯 Generated " . count($signals) . " signals:\n";
            foreach ($signals as $signal) {
                $strength = $signal['strength'] ?? 0;
                $percentage = $strength * 100;
                echo "      - {$signal['type']} ({$signal['direction']}): {$percentage}% strength\n";
                $allSignals[] = $signal;
            }
        } else {
            echo "   ⚠️ No signals generated for this timeframe\n";
        }
        echo "\n";
    }
    
    // Check signal filtering
    echo "🔍 Checking Signal Filtering...\n";
    $requiredStrength = config('micro_trading.signal_settings.high_strength_requirement', 0.90);
    echo "🎯 Required strength: " . ($requiredStrength * 100) . "%\n\n";
    
    if (empty($allSignals)) {
        echo "❌ No signals generated at all - this is why no trades are being placed\n";
        echo "   Possible reasons:\n";
        echo "   - Market conditions don't meet SMC criteria\n";
        echo "   - Insufficient candle data\n";
        echo "   - SMC analysis not finding patterns\n";
    } else {
        echo "📊 Total signals generated: " . count($allSignals) . "\n";
        
        // Filter signals
        $filteredSignals = [];
        foreach ($allSignals as $signal) {
            $strength = $signal['strength'] ?? 0;
            if ($strength >= $requiredStrength) {
                $filteredSignals[] = $signal;
            }
        }
        
        echo "✅ Signals that pass " . ($requiredStrength * 100) . "% requirement: " . count($filteredSignals) . "\n";
        
        if (empty($filteredSignals)) {
            echo "❌ No signals meet the strength requirement - this is why no trades are being placed\n";
            echo "   Consider lowering the strength requirement in config/micro_trading.php\n";
        } else {
            echo "🎯 Signals that would trigger trades:\n";
            foreach ($filteredSignals as $signal) {
                $strength = $signal['strength'] ?? 0;
                $percentage = $strength * 100;
                echo "   - {$signal['type']} ({$signal['direction']}): {$percentage}% strength\n";
            }
        }
    }
    
    echo "\n🔧 Recommendations:\n";
    echo "==================\n";
    
    if ($lastClosedTrade && $now->lt($cooldownEnd)) {
        echo "1. ⏰ Wait for cooldown to expire (or manually reset)\n";
    }
    
    if (empty($allSignals)) {
        echo "2. 🔍 Check if SMC analysis is working properly\n";
        echo "3. 📊 Verify candle data is being received\n";
    } else if (empty($filteredSignals)) {
        echo "2. ⚙️ Lower the strength requirement in config/micro_trading.php\n";
        echo "   Current: " . ($requiredStrength * 100) . "%, Try: 70% or 80%\n";
    }
    
    echo "4. 📝 Check bot logs for more detailed information\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
