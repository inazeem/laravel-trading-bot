<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TRADING CONDITIONS DIAGNOSTIC ===\n\n";

try {
    // Get the futures bot
    $bot = FuturesTradingBot::where('is_active', true)->first();
    
    if (!$bot) {
        echo "❌ No active futures bot found\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name}\n";
    echo "📊 Symbol: {$bot->symbol}\n";
    echo "⚙️ Status: {$bot->status}\n\n";
    
    // Check 1: Cooldown period
    echo "1. COOLDOWN PERIOD CHECK:\n";
    echo "========================\n";
    
    if ($bot->last_position_closed_at) {
        $cooldownMinutes = config('micro_trading.trading_sessions.cooldown_minutes', 10);
        $cooldownEnd = $bot->last_position_closed_at->addMinutes($cooldownMinutes);
        $isInCooldown = now()->lt($cooldownEnd);
        
        echo "   Last position closed: " . $bot->last_position_closed_at->format('Y-m-d H:i:s') . "\n";
        echo "   Cooldown duration: {$cooldownMinutes} minutes\n";
        echo "   Cooldown ends: " . $cooldownEnd->format('Y-m-d H:i:s') . "\n";
        echo "   Current time: " . now()->format('Y-m-d H:i:s') . "\n";
        echo "   In cooldown: " . ($isInCooldown ? 'YES' : 'NO') . "\n";
        
        if ($isInCooldown) {
            $remainingMinutes = now()->diffInMinutes($cooldownEnd, false);
            echo "   ⏰ Remaining cooldown: {$remainingMinutes} minutes\n";
        }
    } else {
        echo "   ✅ No cooldown period (no recent position closure)\n";
    }
    echo "\n";
    
    // Check 2: Trading session hours
    echo "2. TRADING SESSION HOURS CHECK:\n";
    echo "==============================\n";
    
    $sessionHours = config('micro_trading.trading_sessions.session_hours', ['start' => 0, 'end' => 24]);
    $currentHour = now()->hour;
    $inSession = $currentHour >= $sessionHours['start'] && $currentHour < $sessionHours['end'];
    
    echo "   Session hours: {$sessionHours['start']}:00 - {$sessionHours['end']}:00\n";
    echo "   Current hour: {$currentHour}:00\n";
    echo "   In session: " . ($inSession ? 'YES' : 'NO') . "\n";
    echo "\n";
    
    // Check 3: Max trades per hour
    echo "3. MAX TRADES PER HOUR CHECK:\n";
    echo "============================\n";
    
    $maxTradesPerHour = config('micro_trading.trading_sessions.max_trades_per_hour', 5);
    $tradesThisHour = FuturesTrade::where('futures_trading_bot_id', $bot->id)
        ->where('created_at', '>=', now()->subHour())
        ->count();
    
    echo "   Max trades per hour: {$maxTradesPerHour}\n";
    echo "   Trades this hour: {$tradesThisHour}\n";
    echo "   Limit reached: " . ($tradesThisHour >= $maxTradesPerHour ? 'YES' : 'NO') . "\n";
    echo "\n";
    
    // Check 4: Open positions
    echo "4. OPEN POSITIONS CHECK:\n";
    echo "=======================\n";
    
    $openTrades = $bot->trades()->where('status', 'open')->get();
    echo "   Open trades: " . $openTrades->count() . "\n";
    
    if ($openTrades->count() > 0) {
        foreach ($openTrades as $trade) {
            echo "   - Trade ID: {$trade->id}, Side: {$trade->side}, Status: {$trade->status}\n";
        }
    }
    echo "\n";
    
    // Check 5: Signal generation
    echo "5. SIGNAL GENERATION CHECK:\n";
    echo "==========================\n";
    
    $apiKey = $bot->apiKey;
    if (!$apiKey) {
        echo "   ❌ No API key configured\n";
    } else {
        $exchangeService = new ExchangeService($apiKey);
        $currentPrice = $exchangeService->getCurrentPrice($bot->symbol);
        
        if ($currentPrice) {
            echo "   Current price: $currentPrice\n";
            
            // Test signal generation
            foreach ($bot->timeframes as $timeframe) {
                $interval = $timeframe;
                if ($bot->exchange === 'kucoin') {
                    $kucoinIntervals = [
                        '1m' => '1minute',
                        '5m' => '5minute',
                        '15m' => '15minute',
                        '30m' => '30minute',
                        '1h' => '1hour',
                        '4h' => '4hour',
                        '1d' => '1day'
                    ];
                    $interval = $kucoinIntervals[$timeframe] ?? $timeframe;
                }
                
                $candles = $exchangeService->getCandles($bot->symbol, $interval, 60);
                
                if (!empty($candles)) {
                    $smcService = new \App\Services\SmartMoneyConceptsService($candles);
                    $signals = $smcService->generateSignals($currentPrice);
                    
                    echo "   {$timeframe} timeframe: " . count($signals) . " signals\n";
                    
                    foreach ($signals as $index => $signal) {
                        $strength = $signal['strength'] ?? 0;
                        $percentage = round($strength * 100, 2);
                        echo "     - {$signal['type']} ({$signal['direction']}): {$percentage}% strength\n";
                    }
                } else {
                    echo "   {$timeframe} timeframe: No candle data\n";
                }
            }
        } else {
            echo "   ❌ Could not fetch current price\n";
        }
    }
    echo "\n";
    
    // Check 6: Balance and position size
    echo "6. BALANCE AND POSITION SIZE CHECK:\n";
    echo "===================================\n";
    
    if ($apiKey) {
        try {
            $balance = $exchangeService->getFuturesBalance();
            $usdtBalance = 0;
            
            // Find USDT balance in futures account
            foreach ($balance as $asset) {
                if ($asset['currency'] === 'USDT') {
                    $usdtBalance = $asset['available'];
                    break;
                }
            }
            
            echo "   USDT Balance: $usdtBalance\n";
            
            // Calculate potential position size
            $riskAmount = $usdtBalance * ($bot->risk_percentage / 100);
            $positionSize = $riskAmount * $bot->leverage / $currentPrice;
            
            echo "   Risk amount (5%): $riskAmount USDT\n";
            echo "   Potential position size: $positionSize {$bot->symbol}\n";
            
            if ($positionSize < 0.001) {
                echo "   ⚠️ Position size too small for trading\n";
            } else {
                echo "   ✅ Position size sufficient for trading\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Could not fetch balance: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // Summary
    echo "SUMMARY:\n";
    echo "========\n";
    
    $canTrade = true;
    $reasons = [];
    
    if ($isInCooldown ?? false) {
        $canTrade = false;
        $reasons[] = "Bot is in cooldown period";
    }
    
    if (!($inSession ?? true)) {
        $canTrade = false;
        $reasons[] = "Outside trading session hours";
    }
    
    if (($tradesThisHour ?? 0) >= ($maxTradesPerHour ?? 5)) {
        $canTrade = false;
        $reasons[] = "Max trades per hour reached";
    }
    
    if (($openTrades->count() ?? 0) > 0) {
        $canTrade = false;
        $reasons[] = "Already have open positions";
    }
    
    if ($canTrade) {
        echo "✅ Bot CAN place trades - all conditions met\n";
    } else {
        echo "❌ Bot CANNOT place trades:\n";
        foreach ($reasons as $reason) {
            echo "   - $reason\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
