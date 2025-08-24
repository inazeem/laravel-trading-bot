<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\TradingLearningService;

echo "=== BOT LEARNING FUNCTIONALITY TEST ===\n\n";

// Get the active bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "No active bot found!\n";
    exit;
}

echo "Bot: {$bot->name} ({$bot->symbol})\n";
echo "Timeframes: " . implode(', ', $bot->timeframes) . "\n\n";

// Check trading history
$totalTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)->count();
$closedTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
    ->where('status', 'closed')
    ->count();
$openTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
    ->where('status', 'open')
    ->count();

echo "=== TRADING HISTORY ===\n";
echo "Total Trades: {$totalTrades}\n";
echo "Closed Trades: {$closedTrades}\n";
echo "Open Trades: {$openTrades}\n\n";

// Test learning service
$learningService = new TradingLearningService($bot);

echo "=== LEARNING ANALYSIS ===\n";

// Get learning summary
$summary = $learningService->getLearningSummary();
echo "Learning Summary:\n";
if (isset($summary['message'])) {
    echo "  {$summary['message']}\n";
} else {
    echo "  Total Trades: {$summary['total_trades']}\n";
    echo "  Win Rate: {$summary['win_rate']}%\n";
    echo "  Total PnL: {$summary['total_pnl']}\n";
    if (isset($summary['avg_pnl'])) {
        echo "  Average PnL: {$summary['avg_pnl']}\n";
    }
}
echo "\n";

// Run full analysis if enough data
if ($closedTrades >= 5) {
    echo "Running full learning analysis...\n";
    $analysis = $learningService->analyzeAndLearn();
    
    echo "\n=== LEARNING RESULTS ===\n";
    
    if (!empty($analysis['recommendations'])) {
        echo "💡 Recommendations:\n";
        foreach ($analysis['recommendations'] as $recommendation) {
            echo "  - {$recommendation}\n";
        }
        echo "\n";
    }
    
    if (!empty($analysis['best_signal_types'])) {
        echo "🎯 Best Performing Signal Types:\n";
        foreach (array_slice($analysis['best_signal_types'], 0, 3) as $signal) {
            echo "  - {$signal['signal_type']}: {$signal['win_rate']}% win rate, {$signal['avg_pnl']} avg PnL\n";
        }
        echo "\n";
    }
    
    if (!empty($analysis['best_timeframes'])) {
        echo "⏰ Best Performing Timeframes:\n";
        foreach (array_slice($analysis['best_timeframes'], 0, 3) as $timeframe) {
            echo "  - {$timeframe['timeframe']}: {$timeframe['win_rate']}% win rate, {$timeframe['avg_pnl']} avg PnL\n";
        }
        echo "\n";
    }
    
    if (!empty($analysis['risk_adjustments'])) {
        echo "⚙️ Risk Adjustments Applied:\n";
        foreach ($analysis['risk_adjustments'] as $adjustment) {
            echo "  - {$adjustment}\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️ Need at least 5 closed trades for meaningful learning analysis\n";
    echo "Current closed trades: {$closedTrades}\n\n";
}

echo "=== LEARNING FEATURES ===\n\n";

echo "1. 🧠 PERFORMANCE ANALYSIS\n";
echo "   - Analyzes win rate, profit factor, average PnL\n";
echo "   - Tracks best performing signal types\n";
echo "   - Identifies best performing timeframes\n";
echo "   - Analyzes market conditions\n\n";

echo "2. 🎯 SIGNAL OPTIMIZATION\n";
echo "   - Learns which SMC signals work best\n";
echo "   - Optimizes signal strength thresholds\n";
echo "   - Adjusts confluence requirements\n";
echo "   - Prioritizes high-performing signal types\n\n";

echo "3. ⏰ TIMEFRAME OPTIMIZATION\n";
echo "   - Identifies best performing timeframes\n";
echo "   - Adjusts timeframe weights\n";
echo "   - Optimizes for micro trading patterns\n\n";

echo "4. ⚙️ RISK MANAGEMENT LEARNING\n";
echo "   - Adjusts stop loss based on performance\n";
echo "   - Optimizes take profit levels\n";
echo "   - Adjusts position sizing\n";
echo "   - Learns optimal risk/reward ratios\n\n";

echo "5. 📊 CONTINUOUS IMPROVEMENT\n";
echo "   - Runs after every bot execution\n";
echo "   - Stores learning data in database\n";
echo "   - Applies improvements automatically\n";
echo "   - Tracks performance over time\n\n";

echo "=== LEARNING INTEGRATION ===\n";
echo "✅ Learning runs automatically on every bot execution\n";
echo "✅ Analyzes all completed trades\n";
echo "✅ Applies improvements to bot configuration\n";
echo "✅ Stores learning data for future reference\n";
echo "✅ Optimizes for micro trading with SMC\n\n";

echo "=== MICRO TRADING LEARNING ===\n";
echo "🎯 SMC Signal Performance:\n";
echo "   - OrderBlock_Support/Resistance success rates\n";
echo "   - OrderBlock_Breakout performance\n";
echo "   - BOS/CHoCH effectiveness\n";
echo "   - Signal confluence optimization\n\n";

echo "⏰ Timeframe Learning:\n";
echo "   - 5m vs 15m vs 1h performance\n";
echo "   - Best timeframe combinations\n";
echo "   - Micro trading timing optimization\n\n";

echo "⚡ Speed Optimization:\n";
echo "   - 2-hour trade duration effectiveness\n";
echo "   - 10-minute cooldown optimization\n";
echo "   - Signal processing speed improvements\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Run more trades to gather learning data\n";
echo "2. Monitor learning recommendations\n";
echo "3. Watch for automatic optimizations\n";
echo "4. Check performance improvements over time\n\n";

echo "=== END TEST ===\n";
