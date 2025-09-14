<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ERROR LOGS ===\n";

$logs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 7)
    ->where('level', 'error')
    ->latest()
    ->take(10)
    ->get();

foreach($logs as $log) {
    echo "{$log->created_at->format('H:i:s')} [{$log->level}] {$log->message}\n";
}

echo "\n=== ALL RECENT LOGS (last 20) ===\n";

$allLogs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 7)
    ->latest()
    ->take(20)
    ->get();

foreach($allLogs as $log) {
    echo "{$log->created_at->format('H:i:s')} [{$log->level}] {$log->message}\n";
}
