<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Services\ExchangeService;
use App\Services\SmartMoneyConceptsService;
use App\Services\FuturesTradingBotService;

echo "=== MULTI-TIMEFRAME SMC ANALYSIS ===\n\n";

// Get the active bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "No active bot found!\n";
    exit;
}

echo "Bot: {$bot->name} ({$bot->symbol})\n";
echo "Timeframes: " . implode(', ', $bot->timeframes) . "\n\n";
echo "MTF Enabled: " . (config('micro_trading.mtf_confirmation.enable') ? 'yes' : 'no') . "\n";

// Create exchange service
$exchangeService = new ExchangeService($bot->apiKey);

// Get current price
$currentPrice = $exchangeService->getCurrentPrice($bot->symbol);
echo "Current Price: {$currentPrice}\n\n";

// Analyze each timeframe
foreach ($bot->timeframes as $timeframe) {
    echo "=== ANALYZING {$timeframe} TIMEFRAME ===\n";
    
    // Get candle limit for this timeframe
    $candleLimit = 32; // Default for micro trading
    if ($timeframe === '1h') {
        $candleLimit = 24; // 1 day of hourly data
    } elseif ($timeframe === '5m') {
        $candleLimit = 60; // 5 hours of 5m data
    }
    
    echo "Fetching {$candleLimit} candles for {$timeframe}...\n";
    $candles = $exchangeService->getCandles($bot->symbol, $timeframe, $candleLimit);
    echo "Candles fetched: " . count($candles) . "\n";
    
    // Analyze SMC
    $smc = new SmartMoneyConceptsService($candles);
    $signals = $smc->generateSignals($currentPrice);
    
    echo "Signals generated: " . count($signals) . "\n";
    
    $bullishSignals = 0;
    $bearishSignals = 0;
    
    foreach ($signals as $signal) {
        if ($signal['direction'] === 'bullish') $bullishSignals++;
        if ($signal['direction'] === 'bearish') $bearishSignals++;
        
        echo "  - {$signal['type']} ({$signal['direction']}) - Strength: {$signal['strength']}\n";
    }
    
    echo "Signal distribution: {$bullishSignals} bullish, {$bearishSignals} bearish\n\n";
    
    // Get market trend
    $candleCount = 10;
    $recentCandles = array_slice($candles, -$candleCount);
    $firstPrice = $recentCandles[0]['close'];
    $lastPrice = end($recentCandles)['close'];
    
    $change = (($lastPrice - $firstPrice) / $firstPrice) * 100;
    $direction = $change > 0 ? 'bullish' : ($change < 0 ? 'bearish' : 'neutral');
    
    echo "Market trend: {$direction} ({$change}%)\n";
    echo "Price range: {$firstPrice} -> {$lastPrice}\n\n";
}

// Quick dry-run of the bot to exercise the new confirmations
echo "=== BOT DRY RUN ===\n";
$service = new FuturesTradingBotService($bot);
$service->run();

echo "\n=== SUMMARY ===\n";
echo "Multiple timeframe analysis and bot dry-run completed.\n";

echo "=== NEXT STEPS ===\n";
echo "1. The bot should now analyze all 3 timeframes\n";
echo "2. Run the bot again to see multi-timeframe signals\n";
echo "3. Monitor for more balanced bullish/bearish signals\n\n";

echo "=== END ANALYSIS ===\n";
