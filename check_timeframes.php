<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TIMEFRAMES CHECK ===\n\n";

try {
    // Get the futures bot
    $bot = FuturesTradingBot::where('is_active', true)->first();
    
    if (!$bot) {
        echo "❌ No active futures bot found\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name}\n";
    echo "📊 Symbol: {$bot->symbol}\n";
    echo "⏰ Timeframes: " . json_encode($bot->timeframes) . "\n";
    echo "📈 Timeframe Count: " . count($bot->timeframes) . "\n\n";
    
    // Check confluence calculation
    echo "🔍 CONFLUENCE ANALYSIS:\n";
    echo "======================\n";
    
    if (count($bot->timeframes) > 1) {
        echo "✅ Multiple timeframes configured - confluence will be calculated across timeframes\n";
        echo "   Minimum confluence required: 1 (signal must appear on at least 2 timeframes)\n";
    } else {
        echo "⚠️ Single timeframe configured - confluence requirement relaxed\n";
        echo "   Minimum confluence required: 0 (single timeframe signals allowed)\n";
    }
    
    echo "\n📊 RECOMMENDED TIMEFRAMES FOR BETTER CONFLUENCE:\n";
    echo "===============================================\n";
    echo "For better signal confirmation, consider adding these timeframes:\n";
    echo "- 5m (for short-term confirmation)\n";
    echo "- 1h (for medium-term trend)\n";
    echo "- 4h (for long-term trend)\n";
    
    echo "\n🔧 HOW TO ADD TIMEFRAMES:\n";
    echo "=======================\n";
    echo "1. Go to the admin panel\n";
    echo "2. Edit the futures bot configuration\n";
    echo "3. Add more timeframes to the configuration\n";
    echo "4. Save and restart the bot\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

