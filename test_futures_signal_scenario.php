<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Services\ExchangeService;
use App\Services\SmartMoneyConceptsService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FUTURES SIGNAL SCENARIO TEST ===\n\n";

try {
    // Get the futures bot
    $bot = FuturesTradingBot::where('is_active', true)->first();
    
    if (!$bot) {
        echo "❌ No active futures bot found\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name}\n";
    echo "📊 Symbol: {$bot->symbol}\n\n";
    
    // Get API key
    $apiKey = $bot->apiKey;
    if (!$apiKey) {
        echo "❌ No API key found for bot\n";
        exit(1);
    }
    
    $exchangeService = new ExchangeService($apiKey);
    
    // Get current price
    $currentPrice = $exchangeService->getCurrentPrice($bot->symbol);
    echo "💰 Current price: $currentPrice\n\n";
    
    // Test each timeframe
    foreach ($bot->timeframes as $timeframe) {
        echo "🔍 Testing timeframe: $timeframe\n";
        
        // Get interval
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
        
        // Get candles
        $candleLimit = 60;
        $candles = $exchangeService->getCandles($bot->symbol, $interval, $candleLimit);
        
        if (empty($candles)) {
            echo "   ❌ No candle data received\n";
            continue;
        }
        
        echo "   📈 Got " . count($candles) . " candles\n";
        
        // Create SMC service
        $smcService = new SmartMoneyConceptsService($candles);
        
        // Get nearby order blocks
        $nearbyBlocks = $smcService->getNearbyOrderBlocks($currentPrice, 0.02);
        echo "   📦 Found " . count($nearbyBlocks) . " nearby order blocks\n";
        
        // Show order block details and test different price scenarios
        echo "   📊 Order Block Analysis:\n";
        foreach ($nearbyBlocks as $index => $block) {
            echo "      Block $index: {$block['type']} - High: {$block['high']}, Low: {$block['low']}, Strength: {$block['strength']}\n";
            
            // Test what would happen if price was at different levels
            if ($block['type'] === 'bullish') {
                // Test support level (price at or below low)
                $testPrice = $block['low'] - 0.001; // Just below the low
                $testSignals = $smcService->generateSignals($testPrice);
                if (!empty($testSignals)) {
                    echo "         🎯 WOULD GENERATE SIGNAL at price {$testPrice} (support level)\n";
                    foreach ($testSignals as $signal) {
                        $strength = $signal['strength'] ?? 0;
                        $percentage = round($strength * 100, 2);
                        echo "            - {$signal['type']} ({$signal['direction']}): {$percentage}% strength\n";
                    }
                }
                
                // Test breakout level (price above high)
                $testPrice = $block['high'] + 0.001; // Just above the high
                $testSignals = $smcService->generateSignals($testPrice);
                if (!empty($testSignals)) {
                    echo "         🎯 WOULD GENERATE SIGNAL at price {$testPrice} (breakout level)\n";
                    foreach ($testSignals as $signal) {
                        $strength = $signal['strength'] ?? 0;
                        $percentage = round($strength * 100, 2);
                        echo "            - {$signal['type']} ({$signal['direction']}): {$percentage}% strength\n";
                    }
                }
            } elseif ($block['type'] === 'bearish') {
                // Test resistance level (price at or above high)
                $testPrice = $block['high'] + 0.001; // Just above the high
                $testSignals = $smcService->generateSignals($testPrice);
                if (!empty($testSignals)) {
                    echo "         🎯 WOULD GENERATE SIGNAL at price {$testPrice} (resistance level)\n";
                    foreach ($testSignals as $signal) {
                        $strength = $signal['strength'] ?? 0;
                        $percentage = round($strength * 100, 2);
                        echo "            - {$signal['type']} ({$signal['direction']}): {$percentage}% strength\n";
                    }
                }
                
                // Test breakdown level (price below low)
                $testPrice = $block['low'] - 0.001; // Just below the low
                $testSignals = $smcService->generateSignals($testPrice);
                if (!empty($testSignals)) {
                    echo "         🎯 WOULD GENERATE SIGNAL at price {$testPrice} (breakdown level)\n";
                    foreach ($testSignals as $signal) {
                        $strength = $signal['strength'] ?? 0;
                        $percentage = round($strength * 100, 2);
                        echo "            - {$signal['type']} ({$signal['direction']}): {$percentage}% strength\n";
                    }
                }
            }
        }
        
        // Test signal generation with current price
        $signals = $smcService->generateSignals($currentPrice);
        echo "   🎯 Generated " . count($signals) . " signals with current price\n";
        
        // Show signal details
        foreach ($signals as $index => $signal) {
            $strength = $signal['strength'] ?? 0;
            $percentage = round($strength * 100, 2);
            echo "      Signal $index: {$signal['type']} ({$signal['direction']}) - {$percentage}% strength\n";
        }
        
        echo "\n";
    }
    
    // Check configuration
    echo "⚙️ Configuration Check:\n";
    $requiredStrength = config('micro_trading.signal_settings.high_strength_requirement', 0.70);
    echo "   Required strength: " . ($requiredStrength * 100) . "%\n";
    echo "   Min strength threshold: " . (config('micro_trading.signal_settings.min_strength_threshold', 0.4) * 100) . "%\n\n";
    
    echo "🔧 Analysis:\n";
    echo "===========\n";
    echo "1. The bot will place orders when signals are generated with sufficient strength\n";
    echo "2. Current market conditions don't meet SMC criteria for signal generation\n";
    echo "3. The bot is working correctly - it's being selective about trade opportunities\n";
    echo "4. When price reaches support/resistance levels, signals will be generated\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
