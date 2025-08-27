<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FUTURES BOT CONFIGURATION UPDATE ===\n\n";

try {
    // Get the futures bot
    $bot = FuturesTradingBot::where('is_active', true)->first();
    
    if (!$bot) {
        echo "❌ No active futures bot found\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name}\n";
    echo "📊 Symbol: {$bot->symbol}\n";
    echo "⏰ Current timeframes: " . json_encode($bot->timeframes) . "\n";
    echo "📈 Current position side: {$bot->position_side}\n\n";
    
    // Update timeframes to 15m, 30m, 1h
    $newTimeframes = ['15m', '30m', '1h'];
    $bot->timeframes = $newTimeframes;
    
    // Update position side to allow both long and short
    $bot->position_side = 'both';
    
    // Save the changes
    $bot->save();
    
    echo "✅ CONFIGURATION UPDATED:\n";
    echo "=======================\n";
    echo "⏰ New timeframes: " . json_encode($bot->timeframes) . "\n";
    echo "📈 New position side: {$bot->position_side}\n";
    echo "🔄 Timeframe count: " . count($bot->timeframes) . "\n\n";
    
    echo "📊 CONFLUENCE CALCULATION:\n";
    echo "==========================\n";
    echo "With 3 timeframes, confluence will be calculated as:\n";
    echo "- Signal appears on 1 timeframe: Confluence = 0\n";
    echo "- Signal appears on 2 timeframes: Confluence = 1 ✅\n";
    echo "- Signal appears on 3 timeframes: Confluence = 2 ✅\n";
    echo "- Minimum confluence required: 1 (for high-strength signals)\n\n";
    
    echo "🎯 TRADING BEHAVIOR:\n";
    echo "===================\n";
    echo "✅ Bot will now take BOTH long and short positions\n";
    echo "✅ Bullish signals with high strength → LONG positions\n";
    echo "✅ Bearish signals with high strength → SHORT positions\n";
    echo "✅ Multiple timeframes provide better signal confirmation\n\n";
    
    echo "🚀 NEXT STEPS:\n";
    echo "==============\n";
    echo "1. The bot is now configured for optimal trading\n";
    echo "2. It will analyze 15m, 30m, and 1h timeframes\n";
    echo "3. It will take both bullish and bearish trades\n";
    echo "4. Confluence across timeframes will improve signal quality\n";
    echo "5. The bot will automatically restart with new settings\n\n";
    
    echo "📈 EXPECTED IMPROVEMENTS:\n";
    echo "========================\n";
    echo "✅ Better signal confirmation with 3 timeframes\n";
    echo "✅ Balanced long/short trading (not just shorting)\n";
    echo "✅ Higher quality signals through confluence\n";
    echo "✅ More trading opportunities\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
