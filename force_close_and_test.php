<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FORCE CLOSING EXISTING TRADE AND TESTING SL/TP ===\n";

// Force close existing trade
$openTrades = \App\Models\FuturesTrade::where('status', 'open')->get();
echo "Found {$openTrades->count()} open trades to close\n";

foreach ($openTrades as $trade) {
    echo "Force closing trade ID: {$trade->id}\n";
    $trade->update([
        'status' => 'closed',
        'exit_price' => 3.7256,
        'closed_at' => now(),
        'realized_pnl' => 0,
        'pnl_percentage' => 0
    ]);
    echo "Trade {$trade->id} marked as closed\n";
}

// Clear cooldown and last position closed time
$bot = \App\Models\FuturesTradingBot::find(5);
$bot->update([
    'last_position_closed_at' => null
]);
echo "Cleared cooldown period\n";

// Wait a moment
sleep(2);

echo "\n=== TESTING BOT WITH MARKET ORDERS ===\n";
$output = shell_exec('php artisan futures:run --bot=5 2>&1');
echo $output;

echo "\n=== CHECKING RESULTS ===\n";
$newTrades = \App\Models\FuturesTrade::where('status', 'open')->get();
echo "New open trades: {$newTrades->count()}\n";

foreach ($newTrades as $trade) {
    echo "New Trade ID: {$trade->id}\n";
    echo "Side: {$trade->side}\n";
    echo "Quantity: {$trade->quantity}\n";
    echo "Entry Price: {$trade->entry_price}\n";
    echo "Stop Loss: {$trade->stop_loss}\n";
    echo "Take Profit: {$trade->take_profit}\n";
    echo "Main Order ID: {$trade->order_id}\n";
    echo "Stop Loss Order ID: {$trade->stop_loss_order_id}\n";
    echo "Take Profit Order ID: {$trade->take_profit_order_id}\n";
    
    if ($trade->stop_loss_order_id && $trade->take_profit_order_id) {
        echo "✅ SUCCESS: Both SL and TP orders were placed!\n";
    } else {
        echo "❌ ISSUE: SL or TP orders were not placed properly.\n";
    }
}

// Check recent logs for SL/TP placement
echo "\n=== RECENT SL/TP LOGS ===\n";
$recentLogs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 5)
    ->where(function($query) {
        $query->where('message', 'like', '%SL%')
              ->orWhere('message', 'like', '%TP%')
              ->orWhere('message', 'like', '%stop%')
              ->orWhere('message', 'like', '%profit%')
              ->orWhere('message', 'like', '%order%')
              ->orWhere('message', 'like', '%place%');
    })
    ->latest()
    ->take(10)
    ->get();

foreach ($recentLogs as $log) {
    echo "{$log->level} - {$log->message} - {$log->created_at->format('H:i:s')}\n";
}
