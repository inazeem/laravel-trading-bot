<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SL/TP ORDER LOGS ===\n";

$logs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 7)
    ->where(function($query) {
        $query->where('message', 'LIKE', '%stop loss%')
              ->orWhere('message', 'LIKE', '%take profit%')
              ->orWhere('message', 'LIKE', '%SL/TP%')
              ->orWhere('message', 'LIKE', '%RISK/REWARD%');
    })
    ->latest()
    ->take(15)
    ->get();

foreach($logs as $log) {
    echo "{$log->created_at->format('H:i:s')} [{$log->level}] {$log->message}\n";
}

echo "\n=== CURRENT TRADE DETAILS ===\n";
$trade = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->where('status', 'open')
    ->first();

if ($trade) {
    echo "Trade ID: {$trade->id}\n";
    echo "Entry Price: {$trade->entry_price}\n";
    echo "Stop Loss: {$trade->stop_loss}\n";
    echo "Take Profit: {$trade->take_profit}\n";
    echo "Quantity: {$trade->quantity}\n";
    echo "Side: {$trade->side}\n";
    
    if ($trade->entry_price && $trade->stop_loss && $trade->take_profit) {
        $risk = abs($trade->entry_price - $trade->stop_loss);
        $reward = abs($trade->take_profit - $trade->entry_price);
        $riskRewardRatio = $reward / $risk;
        
        echo "\nRisk/Reward Calculation:\n";
        echo "Risk: {$risk}\n";
        echo "Reward: {$reward}\n";
        echo "R/R Ratio: {$riskRewardRatio}\n";
        
        if ($trade->side === 'long') {
            echo "Expected R/R for long: 1:2 = 2.0\n";
        } else {
            echo "Expected R/R for short: 1:2 = 2.0\n";
        }
    }
} else {
    echo "No open trades found\n";
}