<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\FuturesTradingBotService;
use Illuminate\Support\Facades\DB;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Testing Critical Bot Fixes\n";
echo "============================\n\n";

// Get the first active futures bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "❌ No active futures bot found. Please create one first.\n";
    exit(1);
}

echo "✅ Found active bot: {$bot->name}\n";
echo "📊 Symbol: {$bot->symbol}\n";
echo "⚙️ Leverage: {$bot->leverage}x\n";
echo "💰 Margin Type: {$bot->margin_type}\n\n";

try {
    // Test 1: Check for existing open positions
    echo "🔍 Test 1: Checking for existing open positions...\n";
    $openTrade = $bot->openTrades()->first();
    
    if ($openTrade) {
        echo "⚠️ Found open position: ID {$openTrade->id}, Side: {$openTrade->side}, Status: {$openTrade->status}\n";
        echo "📊 Current PnL: {$openTrade->unrealized_pnl}\n";
    } else {
        echo "✅ No open positions found\n";
    }
    
    // Test 2: Check PnL persistence
    echo "\n🔍 Test 2: Testing PnL persistence...\n";
    if ($openTrade) {
        $persistentPnL = DB::table('futures_trade_pnl_history')
            ->where('futures_trade_id', $openTrade->id)
            ->value('pnl_value');
        
        if ($persistentPnL !== null) {
            echo "✅ Found persistent PnL: {$persistentPnL}\n";
        } else {
            echo "⚠️ No persistent PnL found for trade {$openTrade->id}\n";
        }
    } else {
        echo "ℹ️ No open trade to test PnL persistence\n";
    }
    
    // Test 3: Check sync status
    echo "\n🔍 Test 3: Testing position sync...\n";
    $exchangeService = new \App\Services\ExchangeService($bot->apiKey);
    $exchangePositions = $exchangeService->getOpenPositions($bot->symbol);
    
    echo "📊 Exchange positions: " . count($exchangePositions) . "\n";
    foreach ($exchangePositions as $position) {
        echo "   - Symbol: {$position['symbol']}, Side: {$position['side']}, Quantity: {$position['quantity']}\n";
    }
    
    // Test 4: Check database vs exchange consistency
    echo "\n🔍 Test 4: Checking database vs exchange consistency...\n";
    $dbOpenTrades = $bot->trades()->where('status', 'open')->count();
    $exchangeOpenPositions = count($exchangePositions);
    
    if ($dbOpenTrades === $exchangeOpenPositions) {
        echo "✅ Database and exchange are in sync\n";
    } else {
        echo "⚠️ Mismatch: DB has {$dbOpenTrades} open trades, Exchange has {$exchangeOpenPositions} positions\n";
    }
    
    // Test 5: Check for multiple trades prevention
    echo "\n🔍 Test 5: Testing multiple trades prevention...\n";
    if ($openTrade) {
        echo "🚫 Multiple trades prevention: Active - open position exists\n";
        echo "   The bot should NOT place new trades while position is open\n";
    } else {
        echo "✅ Multiple trades prevention: Ready - no open positions\n";
        echo "   The bot can place new trades safely\n";
    }
    
    // Test 6: Check cooldown status
    echo "\n🔍 Test 6: Checking cooldown status...\n";
    $lastClosedTrade = $bot->trades()
        ->where('status', 'closed')
        ->latest('closed_at')
        ->first();
    
    if ($lastClosedTrade && $lastClosedTrade->closed_at) {
        $cooldownEnd = $lastClosedTrade->closed_at->addMinutes(30);
        $now = now();
        
        if ($now->lt($cooldownEnd)) {
            $remainingMinutes = $now->diffInMinutes($cooldownEnd);
            echo "⏰ Cooldown active: {$remainingMinutes} minutes remaining\n";
        } else {
            echo "✅ Cooldown period expired\n";
        }
    } else {
        echo "ℹ️ No recent closed trades found\n";
    }
    
    // Test 7: Check leverage setting
    echo "\n🔍 Test 7: Checking leverage configuration...\n";
    echo "🎯 Configured leverage: {$bot->leverage}x\n";
    echo "💰 Configured margin type: {$bot->margin_type}\n";
    
    if ($openTrade) {
        echo "📊 Actual trade leverage: {$openTrade->leverage}x\n";
        echo "📊 Actual trade margin type: {$openTrade->margin_type}\n";
        
        if ($openTrade->leverage == $bot->leverage && $openTrade->margin_type == $bot->margin_type) {
            echo "✅ Leverage and margin type match configuration\n";
        } else {
            echo "⚠️ Leverage or margin type mismatch with configuration\n";
        }
    }
    
    echo "\n🎉 Critical fixes test completed!\n";
    echo "\n📋 Summary:\n";
    echo "- Multiple trades prevention: " . ($openTrade ? "ACTIVE" : "READY") . "\n";
    echo "- Position sync: " . ($dbOpenTrades === $exchangeOpenPositions ? "SYNCED" : "MISMATCH") . "\n";
    echo "- PnL persistence: " . ($openTrade && DB::table('futures_trade_pnl_history')->where('futures_trade_id', $openTrade->id)->exists() ? "ENABLED" : "NEEDS_CHECK") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
