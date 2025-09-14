<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUANTITY PRECISION DEBUG ===\n";

// Simulate the exact calculation from the bot
$currentPrice = 245.0;
$usdtBalance = 49.79943298;
$riskPercentage = 5;
$leverage = 5;
$maxPositionSize = 0.5;

echo "Input values:\n";
echo "  Current Price: {$currentPrice}\n";
echo "  USDT Balance: {$usdtBalance}\n";
echo "  Risk Percentage: {$riskPercentage}%\n";
echo "  Leverage: {$leverage}x\n";
echo "  Max Position Size: {$maxPositionSize}\n\n";

// Calculate position size exactly like the bot does
$riskAmount = $usdtBalance * ($riskPercentage / 100);
$positionValue = $riskAmount * $leverage;
$positionSize = $positionValue / $currentPrice;

echo "Calculations:\n";
echo "  Risk Amount: {$riskAmount} USDT\n";
echo "  Position Value: {$positionValue} USDT\n";
echo "  Initial Position Size: {$positionSize}\n";

// Apply max position size limit
$positionSize = min($positionSize, $maxPositionSize);
echo "  After max limit: {$positionSize}\n";

// Check minimum requirements
$minNotionalValue = 5.5; // 5 + 0.5
$requiredMinPosition = $minNotionalValue / $currentPrice;
$finalNotionalValue = $positionSize * $currentPrice;

echo "\nMinimum checks:\n";
echo "  Min Notional Value: {$minNotionalValue} USDT\n";
echo "  Required Min Position: {$requiredMinPosition}\n";
echo "  Final Notional Value: {$finalNotionalValue} USDT\n";

if ($finalNotionalValue < $minNotionalValue) {
    echo "  ❌ PROBLEM: Final notional value below minimum!\n";
} else {
    echo "  ✅ Final notional value meets minimum\n";
}

// Test precision rounding
echo "\nPrecision testing:\n";
$precision = 1; // SOLUSDT precision
$roundedQuantity = round($positionSize, $precision);
echo "  SOLUSDT Precision: {$precision}\n";
echo "  Original Quantity: {$positionSize}\n";
echo "  Rounded Quantity: {$roundedQuantity}\n";

if ($roundedQuantity <= 0) {
    echo "  ❌ PROBLEM: Rounded quantity is zero or negative!\n";
} else {
    echo "  ✅ Rounded quantity is valid\n";
}

// Test with different precision values
echo "\nTesting different precisions:\n";
for ($p = 0; $p <= 3; $p++) {
    $testRounded = round($positionSize, $p);
    echo "  Precision {$p}: {$testRounded}\n";
}

echo "\n=== RECOMMENDATION ===\n";
if ($roundedQuantity <= 0) {
    echo "❌ The issue is that precision rounding is making the quantity zero!\n";
    echo "   Solution: Increase the position size or adjust precision handling.\n";
    
    // Suggest minimum viable position size
    $minViableSize = $minNotionalValue / $currentPrice;
    echo "   Minimum viable position size: {$minViableSize}\n";
    echo "   Rounded to precision 1: " . round($minViableSize, 1) . "\n";
}
