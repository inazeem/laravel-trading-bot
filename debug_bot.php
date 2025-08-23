<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING BOT ISSUES ===\n";

// Check current open trades
$openTrades = \App\Models\FuturesTrade::where('status', 'open')->get();
echo "Open trades in database: " . $openTrades->count() . "\n";

foreach ($openTrades as $trade) {
    echo "Trade ID: {$trade->id}, Status: {$trade->status}, Side: {$trade->side}, Order ID: {$trade->order_id}\n";
}

// Check bot configuration
$bot = \App\Models\FuturesTradingBot::find(5);
echo "\nBot config:\n";
echo "Risk: {$bot->risk_percentage}%\n";
echo "Max position: {$bot->max_position_size}\n";
echo "Leverage: {$bot->leverage}x\n";
echo "Min order value: {$bot->min_order_value}\n";

// Check if bot is in cooldown
$lastClosed = $bot->last_position_closed_at;
if ($lastClosed) {
    $cooldownEnd = $lastClosed->addMinutes(30);
    $inCooldown = now()->lt($cooldownEnd);
    echo "Last position closed: {$lastClosed}\n";
    echo "In cooldown: " . ($inCooldown ? 'YES' : 'NO') . "\n";
} else {
    echo "No cooldown period\n";
}

// Check exchange positions
echo "\n=== CHECKING EXCHANGE POSITIONS ===\n";
$apiKey = $bot->apiKey;
$exchangeService = new \App\Services\ExchangeService($apiKey);

try {
    $positions = $exchangeService->getFuturesPositions($bot->symbol);
    echo "Exchange positions: " . json_encode($positions, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error getting positions: " . $e->getMessage() . "\n";
}

// Check current price
$currentPrice = $exchangeService->getCurrentPrice($bot->symbol);
echo "Current price: {$currentPrice}\n";

// Check balance
try {
    $balance = $exchangeService->getBalance();
    echo "Balance: " . json_encode($balance, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error getting balance: " . $e->getMessage() . "\n";
}



