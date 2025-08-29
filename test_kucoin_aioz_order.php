<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Services\ExchangeService;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Log;

echo "=== KuCoin AIOZ-USDT Test Order ===\n\n";

try {
    // Check if KuCoin API key exists
    $apiKey = ApiKey::where('exchange', 'kucoin')->first();
    
    if (!$apiKey) {
        echo "❌ Error: No KuCoin API key found in database\n";
        echo "Please add your KuCoin API credentials first:\n";
        echo "1. Go to your admin panel\n";
        echo "2. Add API Key with exchange = 'kucoin'\n";
        echo "3. Add your KuCoin API Key, Secret, and Passphrase\n\n";
        exit(1);
    }
    
    echo "✅ Found KuCoin API key: " . substr($apiKey->decrypted_api_key, 0, 10) . "...\n\n";
    
    // Initialize Exchange Service
    $exchangeService = new ExchangeService($apiKey);
    
    // First, check if AIOZ-USDT futures symbol exists
    echo "1. Checking AIOZ futures symbol availability...\n";
    
    // Map AIOZ-USDT to KuCoin futures format
    $reflection = new ReflectionClass($exchangeService);
    $mapMethod = $reflection->getMethod('mapToKuCoinFuturesSymbol');
    $mapMethod->setAccessible(true);
    $futuresSymbol = $mapMethod->invoke($exchangeService, 'AIOZ-USDT');
    
    echo "   AIOZ-USDT -> {$futuresSymbol}\n";
    
    // Check current price
    echo "\n2. Getting current price for AIOZ-USDT...\n";
    $currentPrice = $exchangeService->getCurrentPrice('AIOZ-USDT');
    
    if (!$currentPrice) {
        echo "❌ Error: Could not get current price for AIOZ-USDT\n";
        echo "This symbol might not be available on KuCoin futures\n\n";
        exit(1);
    }
    
    echo "✅ Current AIOZ price: $" . number_format($currentPrice, 6) . "\n";
    
    // Get futures balance
    echo "\n3. Checking futures balance...\n";
    $balance = $exchangeService->getFuturesBalance();
    
    if (empty($balance)) {
        echo "❌ Error: Could not get futures balance\n";
        echo "Please check your API credentials and permissions\n\n";
        exit(1);
    }
    
    $usdtBalance = 0;
    foreach ($balance as $bal) {
        if (($bal['currency'] ?? '') === 'USDT') {
            $usdtBalance = $bal['available'] ?? 0;
            break;
        }
    }
    
    echo "✅ Available USDT balance: $" . number_format($usdtBalance, 2) . "\n";
    
    if ($usdtBalance < 10) {
        echo "❌ Error: Insufficient USDT balance for test order (minimum $10 recommended)\n\n";
        exit(1);
    }
    
    // Calculate test order parameters
    echo "\n4. Preparing test order...\n";
    
    $testOrderValue = 10; // $10 test order
    $leverage = 1; // 1x leverage for safety
    $quantity = $testOrderValue / $currentPrice;
    
    echo "   Order Value: $" . $testOrderValue . "\n";
    echo "   Leverage: {$leverage}x\n";
    echo "   Quantity: " . number_format($quantity, 3) . " AIOZ\n";
    echo "   Side: buy (long)\n";
    
    // Ask for confirmation
    echo "\n⚠️  This will place a REAL order on KuCoin futures!\n";
    echo "Do you want to proceed? (type 'yes' to confirm): ";
    
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) !== 'yes') {
        echo "\n❌ Order cancelled by user\n\n";
        exit(0);
    }
    
    echo "\n5. Placing test order on KuCoin futures...\n";
    
    // Place the test order
    $orderResult = $exchangeService->placeFuturesOrder(
        'AIOZ-USDT',     // symbol
        'buy',           // side
        $quantity,       // quantity
        $leverage,       // leverage
        'isolated',      // margin type
        null,            // stop loss
        null             // take profit
    );
    
    if ($orderResult) {
        echo "✅ SUCCESS! Test order placed:\n";
        echo "   Order ID: " . $orderResult['order_id'] . "\n";
        echo "   Symbol: " . $orderResult['symbol'] . "\n";
        echo "   Side: " . $orderResult['side'] . "\n";
        echo "   Quantity: " . $orderResult['quantity'] . "\n";
        echo "   Status: " . $orderResult['status'] . "\n";
        
        if (isset($orderResult['futures_symbol'])) {
            echo "   KuCoin Symbol: " . $orderResult['futures_symbol'] . "\n";
        }
        
        echo "\n🎉 KuCoin futures integration is working!\n";
        
        // Wait a moment and check order status
        echo "\n6. Checking order status...\n";
        sleep(2);
        
        $orderStatus = $exchangeService->getOrderStatus('AIOZ-USDT', $orderResult['order_id']);
        
        if ($orderStatus) {
            echo "✅ Order Status: " . $orderStatus['status'] . "\n";
        } else {
            echo "⚠️  Could not retrieve order status\n";
        }
        
    } else {
        echo "❌ FAILED! Order was not placed\n";
        echo "Check the logs for error details\n\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
