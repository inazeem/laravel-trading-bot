<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);

echo "=== UPDATING RISK PERCENTAGE ===\n";
echo "Current risk: {$bot->risk_percentage}%\n";

// Increase risk percentage to get larger position sizes
$bot->risk_percentage = 10;
$bot->save();

echo "Updated risk to: {$bot->risk_percentage}%\n";

// Test the new calculation
$currentPrice = 245.0;
$usdtBalance = 49.79943298;
$riskPercentage = $bot->risk_percentage;
$leverage = $bot->leverage;

$riskAmount = $usdtBalance * ($riskPercentage / 100);
$positionValue = $riskAmount * $leverage;
$positionSize = $positionValue / $currentPrice;

echo "\n=== NEW CALCULATION ===\n";
echo "Risk Amount: {$riskAmount} USDT\n";
echo "Position Value: {$positionValue} USDT\n";
echo "Position Size: {$positionSize}\n";

$precision = 1;
$roundedQuantity = round($positionSize, $precision);
echo "Rounded to precision {$precision}: {$roundedQuantity}\n";

if ($roundedQuantity > 0) {
    echo "✅ Position size is now valid!\n";
} else {
    echo "❌ Still too small, need higher risk percentage\n";
}
