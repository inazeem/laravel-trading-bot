<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CURRENT STATUS CHECK ===\n";

// Check bot configuration
$bot = \App\Models\FuturesTradingBot::find(5);
echo "Bot configuration:\n";
echo "Order type: {$bot->order_type}\n";
echo "Min order value: {$bot->min_order_value}\n";
echo "Leverage: {$bot->leverage}\n";

// Check open trades
$openTrades = \App\Models\FuturesTrade::where('status', 'open')->get();
echo "\nOpen trades: {$openTrades->count()}\n";

foreach ($openTrades as $trade) {
    echo "\nTrade ID: {$trade->id}\n";
    echo "Side: {$trade->side}\n";
    echo "Quantity: {$trade->quantity}\n";
    echo "Entry Price: {$trade->entry_price}\n";
    echo "Stop Loss: {$trade->stop_loss}\n";
    echo "Take Profit: {$trade->take_profit}\n";
    echo "Main Order ID: {$trade->order_id}\n";
    echo "Stop Loss Order ID: {$trade->stop_loss_order_id}\n";
    echo "Take Profit Order ID: {$trade->take_profit_order_id}\n";
}

// Check recent logs
echo "\n=== RECENT LOGS ===\n";
$recentLogs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 5)
    ->latest()
    ->take(10)
    ->get();

foreach ($recentLogs as $log) {
    echo "{$log->level} - {$log->message} - {$log->created_at->format('H:i:s')}\n";
}

