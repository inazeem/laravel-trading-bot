<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);

echo "=== BOT CONFIGURATION ===\n";
echo "Bot ID: {$bot->id}\n";
echo "Name: {$bot->name}\n";
echo "Symbol: {$bot->symbol}\n";
echo "Risk Percentage: {$bot->risk_percentage}%\n";
echo "Max Position Size: {$bot->max_position_size}\n";
echo "Leverage: {$bot->leverage}x\n";
echo "Min Order Value: {$bot->min_order_value}\n";
echo "Position Side: {$bot->position_side}\n";

echo "\n=== BALANCE CHECK ===\n";
$exchangeService = new \App\Services\ExchangeService($bot->apiKey);
$balance = $exchangeService->getFuturesBalance();

echo "Futures Balance:\n";
foreach($balance as $bal) {
    $currency = $bal['currency'] ?? $bal['asset'] ?? 'unknown';
    $available = $bal['available'] ?? $bal['free'] ?? 0;
    echo "  {$currency}: {$available}\n";
}

echo "\n=== POSITION SIZE CALCULATION ===\n";
$currentPrice = 245.0; // Approximate SOL price
$usdtBalance = 0;

foreach($balance as $bal) {
    $currency = $bal['currency'] ?? $bal['asset'] ?? null;
    $available = $bal['available'] ?? $bal['free'] ?? 0;
    
    if ($currency === 'USDT' && $available > 0) {
        $usdtBalance = (float) $available;
        break;
    }
}

echo "USDT Balance: {$usdtBalance}\n";
echo "Current Price: {$currentPrice}\n";

if ($usdtBalance > 0) {
    $riskAmount = $usdtBalance * ($bot->risk_percentage / 100);
    $positionValue = $riskAmount * $bot->leverage;
    $positionSize = $positionValue / $currentPrice;
    
    echo "Risk Amount: {$riskAmount} USDT\n";
    echo "Position Value: {$positionValue} USDT\n";
    echo "Calculated Position Size: {$positionSize}\n";
    
    $minNotionalValue = ($bot->min_order_value ?? 5) + 0.5;
    $requiredMinPosition = $minNotionalValue / $currentPrice;
    
    echo "Min Notional Value: {$minNotionalValue} USDT\n";
    echo "Required Min Position: {$requiredMinPosition}\n";
    
    $maxPositionNotional = $bot->max_position_size * $currentPrice;
    echo "Max Position Notional: {$maxPositionNotional} USDT\n";
    
    if ($maxPositionNotional < $minNotionalValue) {
        echo "❌ PROBLEM: Max position creates notional value below exchange minimum!\n";
        echo "   Max Position: {$bot->max_position_size} * {$currentPrice} = {$maxPositionNotional} USDT\n";
        echo "   Required Minimum: {$minNotionalValue} USDT\n";
        echo "   Need to increase max_position_size to at least: " . ceil($requiredMinPosition * 1.1) . "\n";
    } else {
        echo "✅ Max position meets exchange minimum\n";
    }
    
    if ($positionSize <= 0) {
        echo "❌ PROBLEM: Position size calculated as zero or negative!\n";
    }
} else {
    echo "❌ PROBLEM: No USDT balance available!\n";
}
