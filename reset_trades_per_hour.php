<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTrade;
use App\Models\FuturesTradingBot;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔄 Resetting Trades Per Hour Limit\n";
echo "===================================\n";

try {
    // Get the bot
    $bot = FuturesTradingBot::first();
    if (!$bot) {
        echo "❌ No futures bot found\n";
        exit;
    }
    
    echo "📊 Bot: {$bot->name} (ID: {$bot->id})\n";
    
    // Count current trades this hour
    $tradesThisHour = FuturesTrade::where('futures_trading_bot_id', $bot->id)
        ->where('created_at', '>=', now()->subHour())
        ->count();
    
    $maxTradesPerHour = config('micro_trading.trading_sessions.max_trades_per_hour', 3);
    
    echo "📈 Current trades this hour: {$tradesThisHour}/{$maxTradesPerHour}\n";
    
    if ($tradesThisHour == 0) {
        echo "✅ No trades to reset - limit is already clear!\n";
        exit;
    }
    
    echo "\n🔧 Reset Options:\n";
    echo "================\n";
    echo "1. Delete recent trades from this hour (TESTING ONLY)\n";
    echo "2. Update trade timestamps to bypass limit (TESTING ONLY)\n";
    echo "3. Show trades to manually review\n";
    echo "\nEnter choice (1-3): ";
    
    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    fclose($handle);
    
    switch ($choice) {
        case '1':
            // Delete recent trades (TESTING ONLY)
            $deleted = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                ->where('created_at', '>=', now()->subHour())
                ->delete();
            
            echo "🗑️  Deleted {$deleted} trades from this hour\n";
            echo "✅ Trades per hour limit has been reset!\n";
            break;
            
        case '2':
            // Update timestamps to 2 hours ago
            $updated = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                ->where('created_at', '>=', now()->subHour())
                ->update(['created_at' => now()->subHours(2)]);
            
            echo "⏰ Updated {$updated} trade timestamps to 2 hours ago\n";
            echo "✅ Trades per hour limit has been reset!\n";
            break;
            
        case '3':
            // Show trades for manual review
            $trades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                ->where('created_at', '>=', now()->subHour())
                ->orderBy('created_at', 'desc')
                ->get();
            
            echo "\n📋 Recent Trades (last hour):\n";
            echo "=============================\n";
            foreach ($trades as $trade) {
                echo "Trade #{$trade->id}: {$trade->side} {$trade->quantity} @ {$trade->entry_price} - {$trade->status} ({$trade->created_at})\n";
            }
            echo "\nReview these trades and manually delete/update if needed.\n";
            break;
            
        default:
            echo "❌ Invalid choice. No changes made.\n";
            break;
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Current config allows {$maxTradesPerHour} trades per hour.\n";
echo "💡 To permanently change limit, edit config/micro_trading.php\n";
echo "🔧 Reset completed!\n";
