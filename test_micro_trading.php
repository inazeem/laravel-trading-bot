<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;

echo "=== MICRO TRADING WITH SMC - FEATURE TEST ===\n\n";

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

echo "=== CURRENT OPEN TRADES ===\n";
echo "Open trades: " . $openTrades->count() . "\n\n";

foreach ($openTrades as $trade) {
    $tradeAge = now()->diffInHours($trade->opened_at);
    $maxDuration = config('micro_trading.signal_settings.max_trade_duration_hours', 2);
    $timeLeft = $maxDuration - $tradeAge;
    
    echo "  - ID: {$trade->id}, {$trade->side} {$trade->symbol}\n";
    echo "    Entry: {$trade->entry_price}, Quantity: {$trade->quantity}\n";
    echo "    Unrealized PnL: {$trade->unrealized_pnl}\n";
    echo "    Age: {$tradeAge}h, Time left: {$timeLeft}h\n";
    echo "    Order ID: {$trade->order_id}\n\n";
}

echo "=== MICRO TRADING FEATURES ===\n\n";

echo "1. ⏰ TIME-BASED EXIT (2 HOUR MAX)\n";
echo "   - Trades automatically close after 2 hours\n";
echo "   - Prevents overstaying in positions\n";
echo "   - Optimized for micro trading timeframes\n\n";

echo "2. 🎯 SMC SIGNAL PRIORITY\n";
echo "   - OrderBlock_Support/Resistance (highest priority)\n";
echo "   - OrderBlock_Breakout (medium priority)\n";
echo "   - BOS/CHoCH (lower priority)\n";
echo "   - Better signal quality for micro trading\n\n";

echo "3. ⚡ FAST RE-ENTRY\n";
echo "   - 10-minute cooldown (vs 30 minutes before)\n";
echo "   - Faster signal processing\n";
echo "   - More trading opportunities\n\n";

echo "4. 📊 TRADING SESSION MANAGEMENT\n";
echo "   - Max 5 trades per hour\n";
echo "   - 24-hour trading session\n";
echo "   - Single position preference\n\n";

echo "5. 🔍 ENHANCED SIGNAL FILTERING\n";
echo "   - Lower confluence requirements (1 timeframe)\n";
echo "   - High-strength single timeframe signals accepted\n";
echo "   - SMC-optimized scoring system\n\n";

echo "=== MICRO TRADING CONFIGURATION ===\n";
echo "Max Trade Duration: " . config('micro_trading.signal_settings.max_trade_duration_hours', 2) . " hours\n";
echo "Min Strength Threshold: " . config('micro_trading.signal_settings.min_strength_threshold', 0.4) . "\n";
echo "Min Confluence: " . config('micro_trading.signal_settings.min_confluence', 1) . "\n";
echo "Cooldown Minutes: " . config('micro_trading.trading_sessions.cooldown_minutes', 10) . "\n";
echo "Max Trades Per Hour: " . config('micro_trading.trading_sessions.max_trades_per_hour', 5) . "\n\n";

echo "=== WHEN TO PLACE NEW TRADES ===\n";
echo "✅ Good conditions:\n";
echo "   - No open positions (single position management)\n";
echo "   - Not in cooldown period\n";
echo "   - Within trading session hours\n";
echo "   - Under max trades per hour limit\n";
echo "   - Strong SMC signal with good confluence\n\n";

echo "❌ Bad conditions:\n";
echo "   - Already have open position\n";
echo "   - In cooldown period (10 minutes after closing)\n";
echo "   - Outside trading hours\n";
echo "   - Max trades per hour reached\n";
echo "   - Weak or low-confluence signals\n\n";

echo "=== SMC STRATEGY FOR MICRO TRADING ===\n";
echo "🎯 OrderBlock_Support: Price at support level = bullish signal\n";
echo "🎯 OrderBlock_Resistance: Price at resistance level = bearish signal\n";
echo "🎯 OrderBlock_Breakout: Price breaking above/below order blocks\n";
echo "🎯 BOS/CHoCH: Structure breaks for trend confirmation\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Run the bot: php artisan futures:run --all\n";
echo "2. Monitor for 2-hour time exits\n";
echo "3. Watch for faster re-entries (10-min cooldown)\n";
echo "4. Observe SMC signal prioritization\n\n";

echo "=== END TEST ===\n";
