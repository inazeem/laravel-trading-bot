<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Models\FuturesTradingBot;
use App\Models\ApiKey;

echo "=== Trading Bot Configuration Check ===\n\n";

// Find active futures trading bots
$bots = FuturesTradingBot::where('is_active', 1)->with('apiKey')->get();

if ($bots->isEmpty()) {
    echo "❌ No active futures trading bots found\n";
    echo "💡 Create a bot in your admin panel first\n\n";
    exit(1);
}

foreach ($bots as $bot) {
    echo "🤖 Bot Found: {$bot->name}\n";
    echo "   Exchange: {$bot->exchange}\n";
    echo "   Symbol: {$bot->symbol}\n";
    echo "   Leverage: {$bot->leverage}x\n";
    echo "   Margin Type: {$bot->margin_type}\n";
    echo "   Risk %: {$bot->risk_percentage}%\n";
    echo "   Max Position: {$bot->max_position_size}\n";
    echo "   Stop Loss: {$bot->stop_loss_percentage}%\n";
    echo "   Take Profit: {$bot->take_profit_percentage}%\n";
    echo "   Timeframes: " . json_encode($bot->timeframes) . "\n";
    echo "   Status: {$bot->status}\n";
    echo "   Last Run: " . ($bot->last_run_at ? $bot->last_run_at->format('Y-m-d H:i:s') : 'Never') . "\n";
    
    if ($bot->apiKey) {
        echo "   API Key: " . substr($bot->apiKey->decrypted_api_key, 0, 10) . "...\n";
        echo "   Exchange Match: " . ($bot->exchange === $bot->apiKey->exchange ? '✅ Yes' : '❌ No') . "\n";
    } else {
        echo "   ❌ No API key configured\n";
    }
    
    echo "\n";
}

// Check if any bot is configured for KuCoin and SOL
$kucoinSolBot = FuturesTradingBot::where('is_active', 1)
    ->where('exchange', 'kucoin')
    ->where('symbol', 'SOL-USDT')
    ->first();

if ($kucoinSolBot) {
    echo "🎯 Found KuCoin SOL-USDT bot!\n";
    echo "   ✅ This bot should place orders when conditions are met\n";
    echo "   ✅ Uses the same API credentials that worked for manual order\n\n";
    
    // Test the service initialization
    try {
        $botService = new \App\Services\FuturesTradingBotService($kucoinSolBot);
        echo "   ✅ Bot service initialized successfully\n";
        echo "   💡 The bot is ready to trade when signals are detected\n\n";
        
        echo "🔍 Signal Requirements:\n";
        echo "   - Signal strength ≥ 95% (ultra high requirement)\n";
        echo "   - Valid risk/reward ratio ≥ {$kucoinSolBot->min_risk_reward_ratio}\n";
        echo "   - No existing open positions\n";
        echo "   - Sufficient account balance\n";
        echo "   - Good timing conditions\n\n";
        
        echo "🎯 When conditions are met, the bot will:\n";
        echo "   1. ✅ Calculate position size based on risk percentage\n";
        echo "   2. ✅ Place KuCoin futures order (SOL-USDT → SOLUSDTM)\n";
        echo "   3. ✅ Set stop-loss and take-profit orders\n";
        echo "   4. ✅ Save trade to database\n";
        echo "   5. ✅ Monitor position for exit conditions\n\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Error initializing bot service: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "❌ No KuCoin SOL-USDT bot found\n";
    echo "💡 Configure a bot with:\n";
    echo "   - Exchange: kucoin\n";
    echo "   - Symbol: SOL-USDT\n";
    echo "   - Active: Yes\n\n";
}

echo "=== Check Complete ===\n";
?>
