<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING MARKET ORDER RESULTS ===\n";

// Check latest trade
$latestTrade = \App\Models\FuturesTrade::where('futures_trading_bot_id', 5)
    ->latest()
    ->first();

if ($latestTrade) {
    echo "Latest Trade ID: {$latestTrade->id}\n";
    echo "Status: {$latestTrade->status}\n";
    echo "Side: {$latestTrade->side}\n";
    echo "Quantity: {$latestTrade->quantity}\n";
    echo "Entry Price: {$latestTrade->entry_price}\n";
    echo "Stop Loss: {$latestTrade->stop_loss}\n";
    echo "Take Profit: {$latestTrade->take_profit}\n";
    echo "Main Order ID: {$latestTrade->order_id}\n";
    echo "Stop Loss Order ID: {$latestTrade->stop_loss_order_id}\n";
    echo "Take Profit Order ID: {$latestTrade->take_profit_order_id}\n";
    
    if ($latestTrade->stop_loss_order_id && $latestTrade->take_profit_order_id) {
        echo "✅ SUCCESS: Both SL and TP orders were placed!\n";
    } else {
        echo "❌ ISSUE: SL or TP orders were not placed properly.\n";
    }
} else {
    echo "No trades found\n";
}

// Check bot configuration
$bot = \App\Models\FuturesTradingBot::find(5);
echo "\nBot configuration:\n";
echo "Order type: {$bot->order_type}\n";
echo "Limit buffer: {$bot->limit_order_buffer}\n";

// Check recent logs for SL/TP placement
echo "\n=== RECENT SL/TP LOGS ===\n";
$recentLogs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 5)
    ->where('message', 'like', '%SL%')
    ->orWhere('message', 'like', '%TP%')
    ->orWhere('message', 'like', '%stop%')
    ->orWhere('message', 'like', '%profit%')
    ->latest()
    ->take(10)
    ->get();

foreach ($recentLogs as $log) {
    echo "{$log->level} - {$log->message} - {$log->created_at->format('H:i:s')}\n";
}


