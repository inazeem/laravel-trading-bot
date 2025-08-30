<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Debug Symbol Mapping for Position Sync...\n\n";

try {
    // Get all active futures bots
    $bots = FuturesTradingBot::where('is_active', true)->get();
    
    foreach ($bots as $bot) {
        echo "📊 Bot: {$bot->name} ({$bot->symbol})\n";
        
        if ($bot->apiKey && $bot->apiKey->exchange === 'kucoin') {
            $exchangeService = new ExchangeService($bot->apiKey);
            
            // Get open trades from database
            $openTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                ->where('status', 'open')
                ->get();
            
            echo "  📋 Open trades in database: " . $openTrades->count() . "\n";
            
            foreach ($openTrades as $trade) {
                echo "    Trade ID: {$trade->id}, Symbol: {$trade->symbol}, Side: {$trade->side}\n";
            }
            
            // Get positions from exchange
            $exchangePositions = $exchangeService->getOpenPositions();
            echo "  📊 Positions on exchange: " . count($exchangePositions) . "\n";
            
            foreach ($exchangePositions as $position) {
                echo "    Exchange Symbol: {$position['symbol']}, Side: {$position['side']}\n";
                
                // Test symbol conversion
                $dbSymbol = $position['symbol'];
                if (str_ends_with($dbSymbol, 'USDTM')) {
                    $dbSymbol = str_replace('USDTM', '', $dbSymbol) . '-USDT';
                } elseif (str_contains($dbSymbol, 'USDT')) {
                    $dbSymbol = str_replace('USDT', '-USDT', $dbSymbol);
                }
                echo "    Converted Symbol: {$dbSymbol}\n";
                
                // Check for matches
                foreach ($openTrades as $trade) {
                    if ($dbSymbol === $trade->symbol && $position['side'] === $trade->side) {
                        echo "    ✅ MATCH FOUND: Trade {$trade->id} matches position\n";
                    } else {
                        echo "    ❌ NO MATCH: '{$dbSymbol}' vs '{$trade->symbol}' OR '{$position['side']}' vs '{$trade->side}'\n";
                    }
                }
            }
        }
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Debug complete!\n";
