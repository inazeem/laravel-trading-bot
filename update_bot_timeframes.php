<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FUTURES BOT TIMEFRAMES UPDATE ===\n\n";

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
    
    // Available timeframes in the system
    $availableTimeframes = ['15m', '30m', '1h', '4h', '1d'];
    
    echo "📋 AVAILABLE TIMEFRAMES:\n";
    echo "========================\n";
    foreach ($availableTimeframes as $tf) {
        echo "- {$tf}\n";
    }
    echo "\n";
    
    // Ask user which timeframes they want
    echo "🎯 SELECT TIMEFRAMES:\n";
    echo "====================\n";
    echo "Please specify which timeframes you want to use.\n";
    echo "Available options: 15m, 30m, 1h, 4h, 1d\n";
    echo "Format: 15m,30m,1h (comma-separated)\n\n";
    
    // For now, let's use a good combination: 15m, 30m, 1h, 4h
    $newTimeframes = ['15m', '30m', '1h', '4h'];
    
    echo "📊 SELECTED TIMEFRAMES: " . implode(', ', $newTimeframes) . "\n";
    echo "🔄 Timeframe count: " . count($newTimeframes) . "\n\n";
    
    // Update the bot configuration
    $bot->timeframes = $newTimeframes;
    $bot->save();
    
    echo "✅ CONFIGURATION UPDATED:\n";
    echo "=======================\n";
    echo "⏰ New timeframes: " . json_encode($bot->timeframes) . "\n";
    echo "📈 Position side: {$bot->position_side}\n";
    echo "🔄 Timeframe count: " . count($bot->timeframes) . "\n\n";
    
    echo "📊 CONFLUENCE CALCULATION:\n";
    echo "==========================\n";
    echo "With " . count($newTimeframes) . " timeframes, confluence will be calculated as:\n";
    echo "- Signal appears on 1 timeframe: Confluence = 0\n";
    echo "- Signal appears on 2 timeframes: Confluence = 1 ✅\n";
    echo "- Signal appears on 3 timeframes: Confluence = 2 ✅\n";
    echo "- Signal appears on 4 timeframes: Confluence = 3 ✅\n";
    echo "- Minimum confluence required: 1 (for high-strength signals)\n\n";
    
    echo "🎯 TRADING BEHAVIOR:\n";
    echo "===================\n";
    echo "✅ Bot will analyze 4 timeframes for better signal confirmation\n";
    echo "✅ 15m: Short-term signals and quick entries\n";
    echo "✅ 30m: Medium-term confirmation\n";
    echo "✅ 1h: Trend direction and major support/resistance\n";
    echo "✅ 4h: Long-term trend and major market structure\n";
    echo "✅ Multiple timeframe confluence improves signal quality\n\n";
    
    echo "📈 TIMEFRAME ANALYSIS:\n";
    echo "=====================\n";
    echo "15m: Quick signals, good for scalping\n";
    echo "30m: Balance between speed and accuracy\n";
    echo "1h: Trend confirmation and major levels\n";
    echo "4h: Long-term bias and market structure\n\n";
    
    echo "🚀 BENEFITS:\n";
    echo "============\n";
    echo "✅ Removed 1m and 5m (too noisy for reliable signals)\n";
    echo "✅ Added 4h for better trend analysis\n";
    echo "✅ 4 timeframes provide excellent confluence\n";
    echo "✅ Better signal quality and reduced false signals\n";
    echo "✅ More comprehensive market analysis\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

