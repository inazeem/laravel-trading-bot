<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING SL/TP LOGS ===\n";

// Check recent logs for SL/TP placement
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
    ->take(20)
    ->get();

echo "Found " . $recentLogs->count() . " relevant logs:\n\n";

foreach ($recentLogs as $log) {
    echo "{$log->level} - {$log->message} - {$log->created_at->format('H:i:s')}\n";
}

// Check for any error logs
echo "\n=== CHECKING FOR ERRORS ===\n";
$errorLogs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 5)
    ->where('level', 'error')
    ->latest()
    ->take(10)
    ->get();

echo "Found " . $errorLogs->count() . " error logs:\n\n";

foreach ($errorLogs as $log) {
    echo "ERROR - {$log->message} - {$log->created_at->format('H:i:s')}\n";
}
