<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Models\FuturesSignal;
use App\Models\FuturesTrade;

echo "=== BOT STATUS CHECK ===\n\n";

// Check bots
echo "1. ACTIVE BOTS:\n";
$bots = FuturesTradingBot::where('is_active', true)->get();
foreach ($bots as $bot) {
    echo "   - {$bot->name} ({$bot->symbol})\n";
    echo "     Status: {$bot->status}\n";
    echo "     Last Run: {$bot->last_run_at}\n";
    echo "     Timeframes: " . implode(', ', $bot->timeframes) . "\n";
    echo "     Total Trades: {$bot->total_trades}\n";
    echo "     Win Rate: {$bot->win_rate}%\n";
    echo "     Total PnL: {$bot->total_pnl}\n\n";
}

// Check recent signals
echo "2. RECENT SIGNALS (Last 10):\n";
$signals = FuturesSignal::latest()->take(10)->get();
if ($signals->count() > 0) {
    foreach ($signals as $signal) {
        echo "   - {$signal->signal_type} ({$signal->direction})\n";
        echo "     Timeframe: {$signal->timeframe}\n";
        echo "     Strength: {$signal->strength}\n";
        echo "     Price: {$signal->price}\n";
        echo "     Created: {$signal->created_at}\n\n";
    }
} else {
    echo "   No signals found!\n\n";
}

// Check recent trades
echo "3. RECENT TRADES (Last 5):\n";
$trades = FuturesTrade::latest()->take(5)->get();
if ($trades->count() > 0) {
    foreach ($trades as $trade) {
        echo "   - {$trade->side} {$trade->symbol}\n";
        echo "     Entry: {$trade->entry_price}\n";
        echo "     Exit: {$trade->exit_price}\n";
        echo "     PnL: {$trade->realized_pnl}\n";
        echo "     Status: {$trade->status}\n";
        echo "     Opened: {$trade->opened_at}\n";
        echo "     Closed: {$trade->closed_at}\n\n";
    }
} else {
    echo "   No trades found!\n\n";
}

// Check signal generation today
echo "4. SIGNALS TODAY:\n";
$todaySignals = FuturesSignal::whereDate('created_at', today())->count();
echo "   Total signals today: {$todaySignals}\n";

// Check trades today
$todayTrades = FuturesTrade::whereDate('created_at', today())->count();
echo "   Total trades today: {$todayTrades}\n\n";

echo "=== END CHECK ===\n";
