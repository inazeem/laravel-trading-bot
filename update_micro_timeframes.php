<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MICRO TRADING TIMEFRAMES UPDATE ===\n\n";

try {
    // Get the futures bot
    $bot = FuturesTradingBot::where('is_active', true)->first();
    
    if (!$bot) {
        echo "❌ No active futures bot found\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name}\n";
    echo "📊 Symbol: {$bot->symbol}\n";
    echo "⏰ Current timeframes: " . json_encode($bot->timeframes) . "\n\n";
    
    // Update to micro trading timeframes only
    $microTimeframes = ['15m', '30m', '1h'];
    $bot->timeframes = $microTimeframes;
    $bot->save();
    
    echo "✅ MICRO TRADING CONFIGURATION UPDATED:\n";
    echo "=====================================\n";
    echo "⏰ New timeframes: " . json_encode($bot->timeframes) . "\n";
    echo "📈 Position side: {$bot->position_side}\n";
    echo "🔄 Timeframe count: " . count($bot->timeframes) . "\n\n";
    
    echo "📊 MICRO TRADING CONFLUENCE CALCULATION:\n";
    echo "=======================================\n";
    echo "With 3 timeframes, confluence will be calculated as:\n";
    echo "- Signal appears on 1 timeframe: Confluence = 0\n";
    echo "- Signal appears on 2 timeframes: Confluence = 1 ✅ (Minimum required)\n";
    echo "- Signal appears on 3 timeframes: Confluence = 2 ✅ (Strong confirmation)\n";
    echo "- Minimum confluence required: 1 (for high-strength signals)\n\n";
    
    echo "🎯 MICRO TRADING BEHAVIOR:\n";
    echo "=========================\n";
    echo "✅ 15m: Quick signals for micro trading entries\n";
    echo "✅ 30m: Medium-term confirmation for micro trades\n";
    echo "✅ 1h: Trend direction for micro trading bias\n";
    echo "✅ No 4h confusion - focused on short-term moves\n";
    echo "✅ Cleaner signal generation with less noise\n\n";
    
    echo "📈 MICRO TRADING BENEFITS:\n";
    echo "=========================\n";
    echo "✅ Focused on short-term price movements\n";
    echo "✅ Faster signal generation and trade execution\n";
    echo "✅ Less conflicting signals across timeframes\n";
    echo "✅ Better suited for 1-2 hour micro trades\n";
    echo "✅ Reduced confusion from multiple timeframes\n";
    echo "✅ Higher signal quality with focused analysis\n\n";
    
    echo "🚀 EXPECTED IMPROVEMENTS:\n";
    echo "=======================\n";
    echo "✅ Cleaner signal generation\n";
    echo "✅ Less conflicting signals\n";
    echo "✅ Faster trade execution\n";
    echo "✅ Better micro trading performance\n";
    echo "✅ Reduced analysis paralysis\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

