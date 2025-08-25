<?php

require_once 'vendor/autoload.php';

use App\Models\TradingBot;
use App\Models\FuturesTradingBot;
use App\Models\ApiKey;
use App\Services\ExchangeService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== BINANCE BOT STATUS CHECK ===\n\n";

// Check regular trading bots (spot)
echo "1. REGULAR TRADING BOTS (SPOT):\n";
echo "===============================\n";

$spotBots = TradingBot::where('exchange', 'binance')->get();

if ($spotBots->count() > 0) {
    foreach ($spotBots as $bot) {
        echo "   Bot: {$bot->name}\n";
        echo "   - ID: {$bot->id}\n";
        echo "   - Symbol: {$bot->symbol}\n";
        echo "   - Status: {$bot->status}\n";
        echo "   - Active: " . ($bot->is_active ? 'Yes' : 'No') . "\n";
        echo "   - Last Run: " . ($bot->last_run_at ? $bot->last_run_at->format('Y-m-d H:i:s') : 'Never') . "\n";
        echo "   - Timeframes: " . implode(', ', $bot->timeframes) . "\n";
        echo "   - Risk: {$bot->risk_percentage}%\n";
        echo "   - Max Position: {$bot->max_position_size}\n";
        echo "   - Total Trades: {$bot->trades()->count()}\n";
        echo "   - Total Signals: {$bot->signals()->count()}\n\n";
    }
} else {
    echo "   No Binance spot trading bots found!\n\n";
}

// Check futures trading bots
echo "2. FUTURES TRADING BOTS:\n";
echo "=======================\n";

$futuresBots = FuturesTradingBot::where('exchange', 'binance')->get();

if ($futuresBots->count() > 0) {
    foreach ($futuresBots as $bot) {
        echo "   Bot: {$bot->name}\n";
        echo "   - ID: {$bot->id}\n";
        echo "   - Symbol: {$bot->symbol}\n";
        echo "   - Status: {$bot->status}\n";
        echo "   - Active: " . ($bot->is_active ? 'Yes' : 'No') . "\n";
        echo "   - Last Run: " . ($bot->last_run_at ? $bot->last_run_at->format('Y-m-d H:i:s') : 'Never') . "\n";
        echo "   - Timeframes: " . implode(', ', $bot->timeframes) . "\n";
        echo "   - Risk: {$bot->risk_percentage}%\n";
        echo "   - Max Position: {$bot->max_position_size}\n";
        echo "   - Total Trades: {$bot->trades()->count()}\n";
        echo "   - Total Signals: {$bot->signals()->count()}\n\n";
    }
} else {
    echo "   No Binance futures trading bots found!\n\n";
}

// Check Binance API keys
echo "3. BINANCE API KEYS:\n";
echo "===================\n";

$binanceApiKeys = ApiKey::where('exchange', 'binance')->get();

if ($binanceApiKeys->count() > 0) {
    foreach ($binanceApiKeys as $apiKey) {
        echo "   API Key: {$apiKey->name}\n";
        echo "   - ID: {$apiKey->id}\n";
        echo "   - Has API Key: " . (!empty($apiKey->api_key) ? 'Yes' : 'No') . "\n";
        echo "   - Has API Secret: " . (!empty($apiKey->api_secret) ? 'Yes' : 'No') . "\n";
        echo "   - Has Passphrase: " . (!empty($apiKey->passphrase) ? 'Yes' : 'No') . "\n";
        echo "   - Permissions: " . implode(', ', $apiKey->permissions ?? []) . "\n\n";
    }
} else {
    echo "   No Binance API keys found!\n\n";
}

// Test Binance API connection
echo "4. TESTING BINANCE API CONNECTION:\n";
echo "==================================\n";

if ($binanceApiKeys->count() > 0) {
    $apiKey = $binanceApiKeys->first();
    echo "   Testing with API key: {$apiKey->name}\n";
    
    try {
        $exchangeService = new ExchangeService($apiKey);
        
        // Test price fetching
        echo "   Testing price fetching for BTCUSDT...\n";
        $price = $exchangeService->getCurrentPrice('BTCUSDT');
        
        if ($price) {
            echo "   ✅ Price fetched successfully: $price\n";
        } else {
            echo "   ❌ Failed to fetch price\n";
        }
        
        // Test balance fetching
        echo "   Testing balance fetching...\n";
        $balance = $exchangeService->getBalance();
        
        if (!empty($balance)) {
            echo "   ✅ Balance fetched successfully!\n";
            foreach ($balance as $bal) {
                $currency = $bal['currency'] ?? $bal['asset'] ?? 'Unknown';
                $available = $bal['available'] ?? $bal['free'] ?? 0;
                if ($available > 0) {
                    echo "   - $currency: $available\n";
                }
            }
        } else {
            echo "   ❌ Failed to fetch balance\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error testing API: " . $e->getMessage() . "\n";
    }
} else {
    echo "   No API keys available for testing\n";
}

// Check recent logs
echo "\n5. RECENT LOGS:\n";
echo "===============\n";

try {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        $recentLines = array_slice($lines, -30); // Last 30 lines
        
        echo "   Recent log entries (Binance related):\n";
        $foundBinanceLogs = false;
        
        foreach ($recentLines as $line) {
            if (strpos($line, 'Binance') !== false || strpos($line, 'binance') !== false) {
                echo "   " . trim($line) . "\n";
                $foundBinanceLogs = true;
            }
        }
        
        if (!$foundBinanceLogs) {
            echo "   No recent Binance-related logs found\n";
        }
    } else {
        echo "   No log file found\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error reading logs: " . $e->getMessage() . "\n";
}

// Check scheduled tasks
echo "\n6. SCHEDULED TASKS:\n";
echo "==================\n";

echo "   Checking if bots are scheduled to run...\n";

// Check if there are any scheduled commands
$schedulerFile = base_path('app/Console/Kernel.php');
if (file_exists($schedulerFile)) {
    $schedulerContent = file_get_contents($schedulerFile);
    
    if (strpos($schedulerContent, 'trading:run') !== false) {
        echo "   ✅ Trading bot scheduler found\n";
    } else {
        echo "   ❌ Trading bot scheduler not found\n";
    }
    
    if (strpos($schedulerContent, 'futures:run') !== false) {
        echo "   ✅ Futures bot scheduler found\n";
    } else {
        echo "   ❌ Futures bot scheduler not found\n";
    }
} else {
    echo "   ❌ Scheduler file not found\n";
}

echo "\n=== BINANCE BOT STATUS CHECK COMPLETE ===\n";

