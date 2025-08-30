<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTrade;
use App\Models\FuturesTradingBot;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "⚡ Quick Reset - Trades Per Hour\n";
echo "================================\n";

try {
    $bot = FuturesTradingBot::first();
    
    // Option 1: Update recent trade timestamps to bypass the hour limit
    $updated = FuturesTrade::where('futures_trading_bot_id', $bot->id)
        ->where('created_at', '>=', now()->subHour())
        ->update(['created_at' => now()->subHours(2)]);
    
    echo "✅ Updated {$updated} recent trades to be 2 hours old\n";
    echo "🎯 You can now test new trades immediately!\n";
    
    // Show current status
    $currentCount = FuturesTrade::where('futures_trading_bot_id', $bot->id)
        ->where('created_at', '>=', now()->subHour())
        ->count();
    
    $maxAllowed = config('micro_trading.trading_sessions.max_trades_per_hour', 3);
    
    echo "📊 Current trades this hour: {$currentCount}/{$maxAllowed}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🚀 Ready for testing!\n";
