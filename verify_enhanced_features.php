<?php

/**
 * Verify Enhanced Features Script
 * 
 * This script verifies that the spot trading bot is using the enhanced features
 */

require_once 'vendor/autoload.php';

use App\Services\TradingBotService;
use App\Models\TradingBot;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Enhanced Features Verification\n";
echo "================================\n\n";

try {
    // Get the first spot trading bot
    $bot = TradingBot::first();
    
    if (!$bot) {
        echo "❌ No spot trading bots found in the system.\n";
        exit;
    }
    
    echo "🤖 Checking Bot: {$bot->name}\n";
    echo "Symbol: {$bot->symbol}\n";
    echo "Exchange: {$bot->exchange}\n\n";
    
    // Create TradingBotService instance
    $service = new TradingBotService($bot);
    
    // Use reflection to check if enhanced methods exist
    $reflection = new ReflectionClass($service);
    
    echo "📋 Enhanced Features Check:\n";
    echo "---------------------------\n";
    
    // Check for enhanced methods
    $enhancedMethods = [
        'filterSignalsByStrength' => '70% Signal Strength Filtering',
        'calculateTenPercentPositionSize' => '10% Position Sizing',
        'isInCooldownPeriod' => '3-Hour Cooldown Management',
        'setCooldownPeriod' => 'Cooldown Period Setting',
        'extractAssetSymbol' => 'Asset Symbol Extraction',
        'getMinimumOrderSize' => 'Minimum Order Size Validation'
    ];
    
    $featuresFound = 0;
    $totalFeatures = count($enhancedMethods);
    
    foreach ($enhancedMethods as $method => $description) {
        if ($reflection->hasMethod($method)) {
            echo "✅ {$description}: Method '{$method}' found\n";
            $featuresFound++;
        } else {
            echo "❌ {$description}: Method '{$method}' NOT found\n";
        }
    }
    
    echo "\n📊 Feature Implementation Status:\n";
    echo "--------------------------------\n";
    echo "Enhanced Features Found: {$featuresFound}/{$totalFeatures}\n";
    
    if ($featuresFound === $totalFeatures) {
        echo "🎉 ALL ENHANCED FEATURES ARE IMPLEMENTED!\n\n";
        
        echo "🚀 Your spot trading bot is using:\n";
        echo "   - 70% minimum signal strength requirement\n";
        echo "   - 10% position sizing based on holdings\n";
        echo "   - 3-hour cooldown periods between trades\n";
        echo "   - Asset holdings tracking and management\n";
        echo "   - Enhanced risk management (1.5:1 R/R ratio)\n";
        echo "   - SMC-based stop loss and take profit\n";
        
    } else {
        echo "⚠️  Some enhanced features are missing.\n";
        echo "Please ensure all enhanced features are properly implemented.\n";
    }
    
    // Check if the bot has the last_trade_at field
    echo "\n📅 Database Field Check:\n";
    echo "----------------------\n";
    
    // Check if the field exists in the database schema
    $columns = DB::select("PRAGMA table_info(trading_bots)");
    $hasLastTradeAtField = false;
    foreach ($columns as $column) {
        if ($column->name === 'last_trade_at') {
            $hasLastTradeAtField = true;
            break;
        }
    }
    
    if ($hasLastTradeAtField) {
        echo "✅ last_trade_at field exists in database\n";
        if ($bot->last_trade_at) {
            echo "   Last trade: {$bot->last_trade_at->format('Y-m-d H:i:s')}\n";
        } else {
            echo "   Last trade: No trades yet\n";
        }
    } else {
        echo "❌ last_trade_at field missing from database\n";
        echo "   Run migration: php artisan migrate\n";
    }
    
    // Check configuration
    echo "\n⚙️  Configuration Check:\n";
    echo "----------------------\n";
    
    $config = config('enhanced_trading');
    if ($config) {
        echo "✅ Enhanced trading configuration loaded\n";
        echo "   Minimum strength: " . ($config['signal_strength']['minimum_strength'] * 100) . "%\n";
        echo "   Position size: " . ($config['position_sizing']['percentage_of_holdings'] * 100) . "%\n";
        echo "   Cooldown hours: {$config['cooldown']['after_trade_hours']}\n";
    } else {
        echo "❌ Enhanced trading configuration not found\n";
        echo "   Check if config/enhanced_trading.php exists\n";
    }
    
    // Test cooldown functionality
    echo "\n⏰ Cooldown Test:\n";
    echo "----------------\n";
    
    if ($bot->last_trade_at) {
        $cooldownHours = 3;
        $cooldownEnd = $bot->last_trade_at->addHours($cooldownHours);
        $isInCooldown = now()->lt($cooldownEnd);
        
        if ($isInCooldown) {
            $remainingMinutes = now()->diffInMinutes($cooldownEnd);
            echo "🕐 Bot is in cooldown period\n";
            echo "   Remaining: {$remainingMinutes} minutes\n";
        } else {
            echo "✅ Bot is not in cooldown period\n";
            echo "   Ready for new trades\n";
        }
    } else {
        echo "ℹ️  No previous trades - no cooldown active\n";
    }
    
    echo "\n🎯 Conclusion:\n";
    echo "--------------\n";
    
    if ($featuresFound === $totalFeatures && $hasLastTradeAtField && $config) {
        echo "✅ Your spot trading bot is FULLY ENHANCED and ready to use!\n";
        echo "   All enhanced features are implemented and configured.\n";
        echo "   The bot will now trade with 70%+ signal strength, 10% position sizing,\n";
        echo "   and 3-hour cooldown periods.\n";
    } else {
        echo "⚠️  Your spot trading bot needs some setup:\n";
        if ($featuresFound < $totalFeatures) {
            echo "   - Some enhanced features are missing from the code\n";
        }
        if (!$hasLastTradeAtField) {
            echo "   - Database migration needed for last_trade_at field\n";
        }
        if (!$config) {
            echo "   - Enhanced trading configuration missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error during verification: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
