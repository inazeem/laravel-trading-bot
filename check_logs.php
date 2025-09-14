<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== RECENT LOGS FOR FUTURES BOT ===\n";
$logs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 7)
    ->latest()
    ->take(20)
    ->get();

foreach($logs as $log) {
    echo "{$log->created_at->format('Y-m-d H:i:s')} [{$log->level}] {$log->message}\n";
}

echo "\n=== OPEN TRADES ===\n";
$openTrades = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->where('status', 'open')
    ->get();

echo "Open trades: " . $openTrades->count() . "\n";

foreach($openTrades as $trade) {
    echo "Trade ID: {$trade->id}, Side: {$trade->side}, Quantity: {$trade->quantity}, Entry: {$trade->entry_price}\n";
}