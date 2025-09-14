<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRECISION AND ORDER DEBUG LOGS ===\n";

$logs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 7)
    ->latest()
    ->take(50)
    ->get();

foreach($logs as $log) {
    if(strpos($log->message, 'PRECISION') !== false || 
       strpos($log->message, 'ORDER DEBUG') !== false ||
       strpos($log->message, 'Invalid quantity') !== false) {
        echo "{$log->created_at->format('Y-m-d H:i:s')} [{$log->level}] {$log->message}\n";
    }
}

echo "\n=== TESTING PRECISION CALCULATION ===\n";
$testQuantity = 0.034984329827123;
echo "Test quantity: {$testQuantity}\n";

for ($precision = 0; $precision <= 3; $precision++) {
    $rounded = round($testQuantity, $precision);
    echo "Precision {$precision}: {$rounded}\n";
}

// Test the actual precision mapping
echo "\n=== TESTING PRECISION MAPPING ===\n";
$precisionMap = [
    'SUIUSDT' => 1,
    'BTCUSDT' => 3,
    'ETHUSDT' => 2,
    'ADAUSDT' => 0,
    'DOGEUSDT' => 0,
    'SOLUSDT' => 1,
    'AVAXUSDT' => 1,
];

$symbol = 'SOLUSDT';
$precision = $precisionMap[$symbol] ?? 1;
$rounded = round($testQuantity, $precision);

echo "Symbol: {$symbol}\n";
echo "Precision: {$precision}\n";
echo "Rounded quantity: {$rounded}\n";

if ($rounded <= 0) {
    echo "❌ PROBLEM: Quantity becomes zero with precision {$precision}!\n";
} else {
    echo "✅ Quantity is valid with precision {$precision}\n";
}
