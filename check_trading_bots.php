<?php

/**
 * Check Trading Bots Script
 * 
 * This script displays all spot trading bots in the system
 */

require_once 'vendor/autoload.php';

use App\Models\TradingBot;
use App\Models\FuturesTradingBot;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🤖 Trading Bots Inventory Check\n";
echo "===============================\n\n";

try {
    // Get all spot trading bots
    $spotBots = TradingBot::with(['user', 'apiKey'])->get();
    
    echo "📊 SPOT TRADING BOTS\n";
    echo "--------------------\n";
    echo "Total Spot Trading Bots: " . $spotBots->count() . "\n\n";
    
    if ($spotBots->count() > 0) {
        foreach ($spotBots as $index => $bot) {
            $status = $bot->is_active ? '🟢 Active' : '🔴 Inactive';
            $lastRun = $bot->last_run_at ? $bot->last_run_at->format('Y-m-d H:i:s') : 'Never';
            $lastTrade = $bot->last_trade_at ? $bot->last_trade_at->format('Y-m-d H:i:s') : 'No trades yet';
            
            echo "Bot #" . ($index + 1) . ":\n";
            echo "  Name: {$bot->name}\n";
            echo "  Status: {$status}\n";
            echo "  Exchange: {$bot->exchange}\n";
            echo "  Symbol: {$bot->symbol}\n";
            echo "  Risk %: {$bot->risk_percentage}%\n";
            echo "  Max Position: {$bot->max_position_size}\n";
            echo "  Timeframes: " . implode(', ', $bot->timeframes) . "\n";
            echo "  Last Run: {$lastRun}\n";
            echo "  Last Trade: {$lastTrade}\n";
            echo "  User: " . ($bot->user ? $bot->user->name : 'Unknown') . "\n";
            echo "  API Key: " . ($bot->apiKey ? $bot->apiKey->name : 'Unknown') . "\n";
            echo "\n";
        }
    } else {
        echo "ℹ️  No spot trading bots found in the system.\n\n";
    }
    
    // Get all futures trading bots for comparison
    $futuresBots = FuturesTradingBot::with(['user', 'apiKey'])->get();
    
    echo "📈 FUTURES TRADING BOTS\n";
    echo "----------------------\n";
    echo "Total Futures Trading Bots: " . $futuresBots->count() . "\n\n";
    
    if ($futuresBots->count() > 0) {
        foreach ($futuresBots as $index => $bot) {
            $status = $bot->is_active ? '🟢 Active' : '🔴 Inactive';
            $lastRun = $bot->last_run_at ? $bot->last_run_at->format('Y-m-d H:i:s') : 'Never';
            
            echo "Futures Bot #" . ($index + 1) . ":\n";
            echo "  Name: {$bot->name}\n";
            echo "  Status: {$status}\n";
            echo "  Exchange: {$bot->exchange}\n";
            echo "  Symbol: {$bot->symbol}\n";
            echo "  Leverage: {$bot->leverage}x\n";
            echo "  Risk %: {$bot->risk_percentage}%\n";
            echo "  Timeframes: " . implode(', ', $bot->timeframes) . "\n";
            echo "  Last Run: {$lastRun}\n";
            echo "  User: " . ($bot->user ? $bot->user->name : 'Unknown') . "\n";
            echo "\n";
        }
    } else {
        echo "ℹ️  No futures trading bots found in the system.\n\n";
    }
    
    // Summary
    echo "📋 SUMMARY\n";
    echo "----------\n";
    echo "Total Spot Trading Bots: " . $spotBots->count() . "\n";
    echo "Total Futures Trading Bots: " . $futuresBots->count() . "\n";
    echo "Total Trading Bots: " . ($spotBots->count() + $futuresBots->count()) . "\n";
    
    // Active bots count
    $activeSpotBots = $spotBots->where('is_active', true)->count();
    $activeFuturesBots = $futuresBots->where('is_active', true)->count();
    
    echo "\n🟢 Active Bots:\n";
    echo "  Active Spot Bots: {$activeSpotBots}\n";
    echo "  Active Futures Bots: {$activeFuturesBots}\n";
    echo "  Total Active Bots: " . ($activeSpotBots + $activeFuturesBots) . "\n";
    
    // Bot status breakdown
    echo "\n📊 Status Breakdown:\n";
    echo "  Active Spot Bots: {$activeSpotBots}\n";
    echo "  Inactive Spot Bots: " . ($spotBots->count() - $activeSpotBots) . "\n";
    echo "  Active Futures Bots: {$activeFuturesBots}\n";
    echo "  Inactive Futures Bots: " . ($futuresBots->count() - $activeFuturesBots) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error checking trading bots: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
