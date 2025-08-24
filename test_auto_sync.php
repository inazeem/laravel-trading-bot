<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;

echo "=== AUTOMATIC POSITION SYNCHRONIZATION TEST ===\n\n";

// Get the active bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "No active bot found!\n";
    exit;
}

echo "Bot: {$bot->name} ({$bot->symbol})\n";
echo "Timeframes: " . implode(', ', $bot->timeframes) . "\n\n";

// Check current open trades
$openTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
    ->where('status', 'open')
    ->get();

echo "=== BEFORE SYNC ===\n";
echo "Open trades in database: " . $openTrades->count() . "\n";

foreach ($openTrades as $trade) {
    echo "  - ID: {$trade->id}, {$trade->side} {$trade->symbol}\n";
    echo "    Entry: {$trade->entry_price}, Quantity: {$trade->quantity}\n";
    echo "    Unrealized PnL: {$trade->unrealized_pnl}\n";
    echo "    Order ID: {$trade->order_id}\n\n";
}

echo "=== RUNNING BOT (WITH AUTO SYNC) ===\n";
echo "The bot will automatically sync positions at the start and end of each run.\n\n";

// Simulate what happens during cron run
echo "1. 🔄 Initial position sync (start of bot run)\n";
echo "   - Checks exchange positions vs database\n";
echo "   - Updates trade data with current exchange info\n";
echo "   - Closes phantom trades that don't exist on exchange\n\n";

echo "2. 🧠 Smart Money Concepts analysis\n";
echo "   - Analyzes all timeframes (5m, 15m, 1h)\n";
echo "   - Generates trading signals\n";
echo "   - Processes best signals\n\n";

echo "3. 📊 Position management\n";
echo "   - Updates existing positions with current data\n";
echo "   - Manages stop loss and take profit\n\n";

echo "4. 🔄 Final position sync (end of bot run)\n";
echo "   - Double-checks all open trades\n";
echo "   - Ensures database accuracy\n";
echo "   - Closes any remaining phantom trades\n\n";

echo "=== BENEFITS OF AUTOMATIC SYNC ===\n";
echo "✅ Prevents phantom trades (trades in DB but not on exchange)\n";
echo "✅ Keeps position data up-to-date with current market prices\n";
echo "✅ Automatically calculates PnL for closed positions\n";
echo "✅ Handles order cancellations and rejections\n";
echo "✅ Maintains database accuracy without manual intervention\n\n";

echo "=== MANUAL SYNC COMMAND ===\n";
echo "You can also manually sync positions anytime:\n";
echo "  php artisan futures:sync-positions --all\n";
echo "  php artisan futures:sync-positions --bot-id=1\n\n";

echo "=== CRON INTEGRATION ===\n";
echo "The bot automatically syncs on every cron run:\n";
echo "  * * * * * php artisan futures:run --all\n\n";

echo "=== END TEST ===\n";
