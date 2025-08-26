<?php

/**
 * Check Database Structure Script
 */

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Database Structure Check\n";
echo "==========================\n\n";

try {
    // Check trading_bots table structure
    $columns = DB::select("PRAGMA table_info(trading_bots)");
    
    echo "📋 Trading Bots Table Structure:\n";
    echo "--------------------------------\n";
    
    $hasLastTradeAt = false;
    
    foreach ($columns as $column) {
        echo "  {$column->name} - {$column->type}\n";
        if ($column->name === 'last_trade_at') {
            $hasLastTradeAt = true;
        }
    }
    
    echo "\n";
    
    if ($hasLastTradeAt) {
        echo "✅ last_trade_at field exists in database\n";
    } else {
        echo "❌ last_trade_at field missing from database\n";
        echo "   This field is required for the enhanced trading bot features.\n";
    }
    
    // Check if there are any trading bots
    $botCount = DB::table('trading_bots')->count();
    echo "\n📊 Trading Bots Count: {$botCount}\n";
    
    if ($botCount > 0) {
        $bot = DB::table('trading_bots')->first();
        echo "Sample bot data:\n";
        foreach ($bot as $key => $value) {
            echo "  {$key}: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking database structure: " . $e->getMessage() . "\n";
}
