<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Services\FuturesTradingBotService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MANUAL BOT EXECUTION TEST ===\n\n";

try {
    // Get the futures bot
    $bot = FuturesTradingBot::where('is_active', true)->first();
    
    if (!$bot) {
        echo "❌ No active futures bot found\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name}\n";
    echo "📊 Symbol: {$bot->symbol}\n";
    echo "⚙️ Status: {$bot->status}\n";
    echo "💰 Risk: {$bot->risk_percentage}%\n";
    echo "📈 Max Position: {$bot->max_position_size}\n";
    echo "⚡ Leverage: {$bot->leverage}x\n";
    echo "💳 Margin Type: {$bot->margin_type}\n\n";
    
    // Check if bot has API key
    if (!$bot->apiKey) {
        echo "❌ Bot has no API key configured\n";
        exit(1);
    }
    
    echo "🔑 API Key: {$bot->apiKey->name}\n\n";
    
    // Create service and run
    echo "🚀 Starting bot execution...\n";
    $service = new FuturesTradingBotService($bot);
    $service->run();
    
    echo "✅ Bot execution completed\n";
    
    // Check for any new trades
    $recentTrades = $bot->trades()->where('created_at', '>=', now()->subMinutes(5))->get();
    echo "📊 Recent trades (last 5 minutes): " . $recentTrades->count() . "\n";
    
    if ($recentTrades->count() > 0) {
        foreach ($recentTrades as $trade) {
            echo "   - Trade ID: {$trade->id}, Side: {$trade->side}, Status: {$trade->status}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error during bot execution: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
