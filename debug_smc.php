<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Services\ExchangeService;
use App\Services\SmartMoneyConceptsService;

echo "=== SMC DEBUG ANALYSIS ===\n\n";

// Get the active bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "No active bot found!\n";
    exit;
}

echo "Bot: {$bot->name} ({$bot->symbol})\n";
echo "Timeframes: " . implode(', ', $bot->timeframes) . "\n\n";

// Create exchange service
$exchangeService = new ExchangeService($bot->apiKey);

// Get current price
$currentPrice = $exchangeService->getCurrentPrice($bot->symbol);
echo "Current Price: {$currentPrice}\n\n";

// Get candlestick data
$timeframe = $bot->timeframes[0];
$interval = $timeframe;
$candleLimit = 32; // Using micro trading optimized limit

echo "Fetching {$candleLimit} candles for {$timeframe} timeframe...\n";
$candles = $exchangeService->getCandles($bot->symbol, $interval, $candleLimit);

echo "Candles fetched: " . count($candles) . "\n\n";

// Analyze SMC
$smc = new SmartMoneyConceptsService($candles);

// Generate signals to trigger analysis
$signals = $smc->generateSignals($currentPrice);

// Get market trend from recent candles manually
$candleCount = 10;
$recentCandles = array_slice($candles, -$candleCount);
$firstPrice = $recentCandles[0]['close'];
$lastPrice = end($recentCandles)['close'];

$change = (($lastPrice - $firstPrice) / $firstPrice) * 100;
$direction = $change > 0 ? 'bullish' : ($change < 0 ? 'bearish' : 'neutral');
$strength = abs($change) / 10; // Normalize to 0-1 (10% = 1.0 strength)

echo "=== MARKET TREND ===\n";
echo "Direction: {$direction}\n";
echo "Strength: " . min(1.0, $strength) . "\n";
echo "Change %: {$change}%\n";
echo "First Price: {$firstPrice}\n";
echo "Last Price: {$lastPrice}\n\n";

// Get order blocks manually by analyzing candles
echo "=== ORDER BLOCKS ANALYSIS ===\n";
echo "Analyzing swing points and order blocks...\n\n";

// Simple swing point detection
$swingHighs = [];
$swingLows = [];
$length = 3;

for ($i = $length; $i < count($candles) - $length; $i++) {
    $candle = $candles[$i];
    
    // Check for swing high
    $isSwingHigh = true;
    for ($j = $i - $length; $j <= $i + $length; $j++) {
        if ($j != $i && $candles[$j]['high'] >= $candle['high']) {
            $isSwingHigh = false;
            break;
        }
    }
    
    if ($isSwingHigh) {
        $swingHighs[] = [
            'index' => $i,
            'price' => $candle['high'],
            'time' => $candle['timestamp']
        ];
    }
    
    // Check for swing low
    $isSwingLow = true;
    for ($j = $i - $length; $j <= $i + $length; $j++) {
        if ($j != $i && $candles[$j]['low'] <= $candle['low']) {
            $isSwingLow = false;
            break;
        }
    }
    
    if ($isSwingLow) {
        $swingLows[] = [
            'index' => $i,
            'price' => $candle['low'],
            'time' => $candle['timestamp']
        ];
    }
}

echo "Swing Highs: " . count($swingHighs) . "\n";
echo "Swing Lows: " . count($swingLows) . "\n\n";

// Create order blocks from swing points
$swingPoints = array_merge($swingHighs, $swingLows);
usort($swingPoints, fn($a, $b) => $a['index'] - $b['index']);

$orderBlocks = [];
$bullishBlocks = 0;
$bearishBlocks = 0;

for ($i = 0; $i < count($swingPoints) - 1; $i++) {
    $current = $swingPoints[$i];
    $next = $swingPoints[$i + 1];
    
    // Find the highest high and lowest low between swing points
    $high = $current['price'];
    $low = $current['price'];
    
    for ($j = $current['index']; $j <= $next['index']; $j++) {
        $high = max($high, $candles[$j]['high']);
        $low = min($low, $candles[$j]['low']);
    }
    
    // Determine if it's a bullish or bearish order block
    $isBullish = $next['price'] > $current['price'];
    
    $orderBlocks[] = [
        'start_index' => $current['index'],
        'end_index' => $next['index'],
        'high' => $high,
        'low' => $low,
        'type' => $isBullish ? 'bullish' : 'bearish',
        'strength' => 0.5 // Default strength
    ];
    
    if ($isBullish) $bullishBlocks++;
    else $bearishBlocks++;
}
echo "=== ORDER BLOCKS ===\n";
echo "Total Order Blocks: " . count($orderBlocks) . "\n\n";

$bullishBlocks = 0;
$bearishBlocks = 0;

foreach ($orderBlocks as $i => $block) {
    $type = $block['type'];
    if ($type === 'bullish') $bullishBlocks++;
    if ($type === 'bearish') $bearishBlocks++;
    
    echo "Block {$i}: {$type}\n";
    echo "  High: {$block['high']}\n";
    echo "  Low: {$block['low']}\n";
    echo "  Strength: {$block['strength']}\n";
    echo "  Distance from current price: " . abs($currentPrice - (($block['high'] + $block['low']) / 2)) . "\n\n";
}

echo "Bullish Blocks: {$bullishBlocks}\n";
echo "Bearish Blocks: {$bearishBlocks}\n\n";

// Find nearby order blocks
$nearbyBlocks = [];
foreach ($orderBlocks as $block) {
    $blockMid = ($block['high'] + $block['low']) / 2;
    $distance = abs($currentPrice - $blockMid) / $blockMid;
    if ($distance <= 0.02) { // Within 2%
        $nearbyBlocks[] = $block;
    }
}

echo "=== NEARBY ORDER BLOCKS (within 2%) ===\n";
echo "Nearby Blocks: " . count($nearbyBlocks) . "\n\n";

foreach ($nearbyBlocks as $i => $block) {
    $blockMid = ($block['high'] + $block['low']) / 2;
    $distance = abs($currentPrice - $blockMid) / $blockMid * 100;
    
    echo "Nearby Block {$i}: {$block['type']}\n";
    echo "  High: {$block['high']}\n";
    echo "  Low: {$block['low']}\n";
    echo "  Mid: {$blockMid}\n";
    echo "  Distance: {$distance}%\n";
    echo "  Strength: {$block['strength']}\n\n";
}

echo "=== SIGNAL GENERATION ===\n";

echo "Total Signals Generated: " . count($signals) . "\n\n";

foreach ($signals as $i => $signal) {
    echo "Signal {$i}:\n";
    echo "  Type: {$signal['type']}\n";
    echo "  Direction: {$signal['direction']}\n";
    echo "  Strength: {$signal['strength']}\n";
    echo "  Level: {$signal['level']}\n";
    if (isset($signal['quality_factors'])) {
        echo "  Quality Factors:\n";
        foreach ($signal['quality_factors'] as $factor => $value) {
            echo "    {$factor}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
        }
    }
    echo "\n";
}

echo "=== ANALYSIS SUMMARY ===\n";
echo "Market Trend: {$trend['direction']} (strength: {$trend['strength']})\n";
echo "Order Block Distribution: {$bullishBlocks} bullish, {$bearishBlocks} bearish\n";
echo "Nearby Blocks: " . count($nearbyBlocks) . "\n";
echo "Signals Generated: " . count($signals) . "\n";

$bullishSignals = 0;
$bearishSignals = 0;
foreach ($signals as $signal) {
    if ($signal['direction'] === 'bullish') $bullishSignals++;
    if ($signal['direction'] === 'bearish') $bearishSignals++;
}

echo "Signal Distribution: {$bullishSignals} bullish, {$bearishSignals} bearish\n\n";

echo "=== END ANALYSIS ===\n";
