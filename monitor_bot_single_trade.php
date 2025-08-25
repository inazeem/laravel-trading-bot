<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MONITORING BOT FOR SINGLE TRADE ENFORCEMENT ===\n\n";

// Get the Binance futures bot
$futuresBot = FuturesTradingBot::where('exchange', 'binance')->first();

if (!$futuresBot) {
    echo "❌ No Binance futures bot found!\n";
    exit(1);
}

echo "✅ Monitoring Binance futures bot: {$futuresBot->name}\n";
echo "   - Symbol: {$futuresBot->symbol}\n\n";

// Check current open trades
echo "🔍 CURRENT OPEN TRADES:\n";
echo "======================\n";

$openTrades = $futuresBot->openTrades()->get();
echo "Open trades: {$openTrades->count()}\n";

if ($openTrades->count() == 0) {
    echo "✅ No open trades - bot is ready for new signals\n";
} elseif ($openTrades->count() == 1) {
    $trade = $openTrades->first();
    echo "✅ Single open trade found:\n";
    echo "   - Trade ID: {$trade->id}\n";
    echo "   - Side: {$trade->side}\n";
    echo "   - Quantity: {$trade->quantity}\n";
    echo "   - Entry Price: {$trade->entry_price}\n";
    echo "   - Created: {$trade->created_at->format('Y-m-d H:i:s')}\n";
    echo "   - PnL: {$trade->pnl}\n";
} else {
    echo "❌ MULTIPLE OPEN TRADES DETECTED!\n";
    foreach ($openTrades as $trade) {
        echo "   - Trade ID: {$trade->id}, Side: {$trade->side}, Quantity: {$trade->quantity}\n";
    }
    echo "   This indicates the fix is not working properly.\n";
}

echo "\n";

// Check recent activity
echo "📊 RECENT ACTIVITY (Last 10 minutes):\n";
echo "=====================================\n";

$recentTrades = $futuresBot->trades()
    ->where('created_at', '>=', now()->subMinutes(10))
    ->orderBy('created_at', 'desc')
    ->get();

echo "Recent trades: {$recentTrades->count()}\n\n";

if ($recentTrades->count() > 0) {
    foreach ($recentTrades as $trade) {
        echo "Trade ID: {$trade->id}\n";
        echo "  - Side: {$trade->side}\n";
        echo "  - Status: {$trade->status}\n";
        echo "  - Quantity: {$trade->quantity}\n";
        echo "  - Entry Price: {$trade->entry_price}\n";
        echo "  - Created: {$trade->created_at->format('Y-m-d H:i:s')}\n";
        echo "  - Order ID: " . ($trade->order_id ? $trade->order_id : 'Not set') . "\n\n";
    }
} else {
    echo "No recent trades found\n\n";
}

// Check recent signals
echo "📈 RECENT SIGNALS (Last 10 minutes):\n";
echo "====================================\n";

$recentSignals = $futuresBot->signals()
    ->where('created_at', '>=', now()->subMinutes(10))
    ->orderBy('created_at', 'desc')
    ->get();

echo "Recent signals: {$recentSignals->count()}\n\n";

if ($recentSignals->count() > 0) {
    foreach ($recentSignals as $signal) {
        echo "Signal ID: {$signal->id}\n";
        echo "  - Type: {$signal->signal_type}\n";
        echo "  - Direction: {$signal->direction}\n";
        echo "  - Strength: {$signal->strength}\n";
        echo "  - Timeframe: {$signal->timeframe}\n";
        echo "  - Created: {$signal->created_at->format('Y-m-d H:i:s')}\n\n";
    }
} else {
    echo "No recent signals found\n\n";
}

// Check bot logs for single position enforcement
echo "📋 RECENT BOT LOGS:\n";
echo "==================\n";

$recentLogs = $futuresBot->logs()
    ->where('created_at', '>=', now()->subMinutes(10))
    ->where(function($query) {
        $query->where('message', 'like', '%SINGLE POSITION%')
              ->orWhere('message', 'like', '%OPEN TRADE CHECK%')
              ->orWhere('message', 'like', '%NEW SIGNAL%');
    })
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

echo "Relevant logs: {$recentLogs->count()}\n\n";

foreach ($recentLogs as $log) {
    echo "Log: {$log->message}\n";
    echo "Time: {$log->created_at->format('Y-m-d H:i:s')}\n\n";
}

// Test the bot logic
echo "🧪 TESTING BOT LOGIC:\n";
echo "=====================\n";

try {
    echo "Running manual bot execution...\n";
    $output = shell_exec('php artisan futures:run --bot=' . $futuresBot->id . ' 2>&1');
    echo "Manual execution output:\n";
    echo $output . "\n";
    
    // Check if any new trades were created
    $newTrades = $futuresBot->trades()
        ->where('created_at', '>=', now()->subMinutes(1))
        ->count();
    
    echo "New trades created in last minute: {$newTrades}\n";
    
    if ($newTrades == 0) {
        echo "✅ Single position enforcement working correctly\n";
    } else {
        echo "❌ New trades created - single position enforcement may not be working\n";
    }
    
} catch (Exception $e) {
    echo "❌ Manual execution failed: " . $e->getMessage() . "\n";
}

// Final status and recommendations
echo "\n🎯 FINAL STATUS:\n";
echo "===============\n";

$finalOpenTrades = $futuresBot->openTrades()->count();
echo "Current open trades: {$finalOpenTrades}\n";

if ($finalOpenTrades <= 1) {
    echo "✅ Single position rule is being enforced\n";
} else {
    echo "❌ Multiple open trades detected - fix may need adjustment\n";
}

echo "\n📋 MONITORING RECOMMENDATIONS:\n";
echo "==============================\n";
echo "1. ✅ Run this script every few minutes to monitor the bot\n";
echo "2. ✅ Check that only one trade is placed per signal\n";
echo "3. ✅ Verify that trades are properly closed before new ones\n";
echo "4. ✅ Monitor the bot logs for single position enforcement messages\n";
echo "5. ✅ Set up alerts if multiple open trades are detected\n\n";

echo "🔧 COMMANDS TO MONITOR:\n";
echo "======================\n";
echo "1. Check open trades: php artisan tinker --execute=\"echo App\\Models\\FuturesTradingBot::where('exchange', 'binance')->first()->openTrades()->count();\"\n";
echo "2. Run bot manually: php artisan futures:run --bot={$futuresBot->id}\n";
echo "3. Check recent logs: tail -f storage/logs/futures-bot-scheduler.log\n";
echo "4. Monitor this script: php monitor_bot_single_trade.php\n\n";

echo "⚠️ ALERT CONDITIONS:\n";
echo "==================\n";
echo "❌ Multiple open trades detected\n";
echo "❌ Rapid-fire trades (multiple trades in 5 minutes)\n";
echo "❌ Trades being placed without proper signal processing\n";
echo "❌ Missing single position enforcement logs\n\n";

echo "=== MONITORING COMPLETE ===\n";

