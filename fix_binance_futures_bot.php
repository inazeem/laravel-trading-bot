<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\ApiKey;
use App\Services\ExchangeService;
use App\Services\FuturesTradingBotService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIXING BINANCE FUTURES BOT ===\n\n";

// Get the Binance futures bot
$futuresBot = FuturesTradingBot::where('exchange', 'binance')->first();

if (!$futuresBot) {
    echo "❌ No Binance futures bot found!\n";
    exit(1);
}

echo "✅ Found Binance futures bot: {$futuresBot->name}\n";
echo "   - Symbol: {$futuresBot->symbol}\n";
echo "   - Status: {$futuresBot->status}\n";
echo "   - Active: " . ($futuresBot->is_active ? 'Yes' : 'No') . "\n";
echo "   - Last Run: " . ($futuresBot->last_run_at ? $futuresBot->last_run_at->format('Y-m-d H:i:s') : 'Never') . "\n\n";

// Check API key
$apiKey = $futuresBot->apiKey;
if (!$apiKey) {
    echo "❌ No API key found for this bot!\n";
    exit(1);
}

echo "✅ API Key: {$apiKey->name}\n";
echo "   - Has API Key: " . (!empty($apiKey->api_key) ? 'Yes' : 'No') . "\n";
echo "   - Has API Secret: " . (!empty($apiKey->api_secret) ? 'Yes' : 'No') . "\n";
echo "   - Permissions: " . implode(', ', $apiKey->permissions ?? []) . "\n\n";

// Test API connection
echo "1. TESTING BINANCE API CONNECTION:\n";
echo "==================================\n";

try {
    $exchangeService = new ExchangeService($apiKey);
    
    // Test price fetching
    echo "   Testing price fetching for {$futuresBot->symbol}...\n";
    $price = $exchangeService->getCurrentPrice($futuresBot->symbol);
    
    if ($price) {
        echo "   ✅ Price fetched successfully: $price\n";
    } else {
        echo "   ❌ Failed to fetch price\n";
    }
    
    // Test spot balance fetching
    echo "   Testing spot balance fetching...\n";
    $balance = $exchangeService->getBalance();
    
    if (!empty($balance)) {
        echo "   ✅ Spot balance fetched successfully!\n";
        foreach ($balance as $bal) {
            $currency = $bal['currency'] ?? $bal['asset'] ?? 'Unknown';
            $available = $bal['available'] ?? $bal['free'] ?? 0;
            if ($available > 0) {
                echo "   - $currency: $available\n";
            }
        }
    } else {
        echo "   ❌ Failed to fetch spot balance\n";
    }
    
    // Test futures balance fetching
    echo "   Testing futures balance fetching...\n";
    $futuresBalance = $exchangeService->getFuturesBalance();
    
    if (!empty($futuresBalance)) {
        echo "   ✅ Futures balance fetched successfully!\n";
        foreach ($futuresBalance as $bal) {
            $currency = $bal['currency'] ?? $bal['asset'] ?? 'Unknown';
            $available = $bal['available'] ?? $bal['free'] ?? 0;
            if ($available > 0) {
                echo "   - $currency: $available\n";
            }
        }
    } else {
        echo "   ❌ Failed to fetch futures balance\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error testing API: " . $e->getMessage() . "\n";
}

// Test bot execution
echo "\n2. TESTING BOT EXECUTION:\n";
echo "=========================\n";

try {
    echo "   Running the futures bot...\n";
    $botService = new FuturesTradingBotService($futuresBot);
    $botService->run();
    
    echo "   ✅ Bot executed successfully!\n";
    
    // Check if bot status was updated
    $futuresBot->refresh();
    echo "   - New status: {$futuresBot->status}\n";
    echo "   - Last run: " . ($futuresBot->last_run_at ? $futuresBot->last_run_at->format('Y-m-d H:i:s') : 'Never') . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Error running bot: " . $e->getMessage() . "\n";
}

// Check recent signals and trades
echo "\n3. RECENT ACTIVITY:\n";
echo "===================\n";

// Check recent signals
$recentSignals = $futuresBot->signals()->latest()->take(5)->get();
if ($recentSignals->count() > 0) {
    echo "   Recent signals:\n";
    foreach ($recentSignals as $signal) {
        echo "   - {$signal->signal_type} ({$signal->direction}) at {$signal->created_at->format('H:i:s')}\n";
    }
} else {
    echo "   No recent signals found\n";
}

// Check recent trades
$recentTrades = $futuresBot->trades()->latest()->take(5)->get();
if ($recentTrades->count() > 0) {
    echo "   Recent trades:\n";
    foreach ($recentTrades as $trade) {
        echo "   - {$trade->side} {$trade->symbol} at {$trade->created_at->format('H:i:s')}\n";
    }
} else {
    echo "   No recent trades found\n";
}

// Check scheduler setup
echo "\n4. SCHEDULER SETUP:\n";
echo "==================\n";

// Check if there's a scheduler file
$schedulerFile = base_path('app/Console/Kernel.php');
if (file_exists($schedulerFile)) {
    echo "   ✅ Scheduler file found\n";
    
    $schedulerContent = file_get_contents($schedulerFile);
    
    if (strpos($schedulerContent, 'futures:run') !== false) {
        echo "   ✅ Futures bot scheduler found\n";
    } else {
        echo "   ❌ Futures bot scheduler not found - needs to be added\n";
    }
    
    if (strpos($schedulerContent, 'trading:run') !== false) {
        echo "   ✅ Trading bot scheduler found\n";
    } else {
        echo "   ❌ Trading bot scheduler not found - needs to be added\n";
    }
} else {
    echo "   ❌ Scheduler file not found at: $schedulerFile\n";
    echo "   This means bots are not running automatically!\n";
}

// Check if cron is running
echo "\n5. CRON SETUP:\n";
echo "==============\n";

echo "   To run bots automatically, you need to set up a cron job:\n";
echo "   Add this to your crontab:\n";
echo "   * * * * * cd " . base_path() . " && php artisan schedule:run >> /dev/null 2>&1\n\n";

echo "   Or run this command manually to test:\n";
echo "   php artisan schedule:run\n\n";

// Recommendations
echo "6. RECOMMENDATIONS:\n";
echo "==================\n";

if ($futuresBot->status === 'idle') {
    echo "   ✅ Bot is in idle state (normal)\n";
} else {
    echo "   ⚠️ Bot status is: {$futuresBot->status}\n";
}

if ($futuresBot->is_active) {
    echo "   ✅ Bot is active\n";
} else {
    echo "   ❌ Bot is not active - activate it in the admin panel\n";
}

if ($futuresBot->last_run_at && $futuresBot->last_run_at->diffInMinutes(now()) < 60) {
    echo "   ✅ Bot ran recently (within the last hour)\n";
} else {
    echo "   ⚠️ Bot hasn't run recently - check scheduler setup\n";
}

echo "\n=== BINANCE FUTURES BOT FIX COMPLETE ===\n";

