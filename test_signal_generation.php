<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Services\ExchangeService;
use App\Services\SmartMoneyConceptsService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SIGNAL GENERATION DEBUG ===\n\n";

try {
    // Get the futures bot
    $bot = FuturesTradingBot::where('is_active', true)->first();
    
    if (!$bot) {
        echo "❌ No active futures bot found\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name}\n";
    echo "📊 Symbol: {$bot->symbol}\n";
    echo "⏰ Timeframes: " . implode(', ', $bot->timeframes) . "\n\n";
    
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
        
        // Test signal generation
        $signals = $smcService->generateSignals($currentPrice);
        echo "   🎯 Generated " . count($signals) . " signals\n";
        
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
    
    echo "🔧 Recommendations:\n";
    echo "==================\n";
    echo "1. If signals are generated but below 70%, consider lowering the strength requirement\n";
    echo "2. If no signals are generated, the market conditions may not meet SMC criteria\n";
    echo "3. Check if order blocks are being identified correctly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
