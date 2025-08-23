<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING LOGS FOR NEW TRADE ===\n";

// Get the latest logs
$recentLogs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 5)
    ->latest()
    ->take(50)
    ->get();

foreach ($recentLogs as $log) {
    if (strpos($log->message, 'order') !== false || 
        strpos($log->message, 'SL') !== false || 
        strpos($log->message, 'TP') !== false ||
        strpos($log->message, 'stop') !== false ||
        strpos($log->message, 'profit') !== false ||
        strpos($log->message, 'placing') !== false) {
        echo "{$log->level} - {$log->message} - {$log->created_at->format('H:i:s')}\n";
    }
}

echo "\n=== ALL RECENT LOGS ===\n";
$allLogs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 5)
    ->latest()
    ->take(20)
    ->get();

foreach ($allLogs as $log) {
    echo "{$log->level} - {$log->message} - {$log->created_at->format('H:i:s')}\n";
}



