<?php

require_once 'vendor/autoload.php';

use App\Models\ApiKey;
use App\Services\ExchangeService;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Testing Binance Futures Leverage Setting\n";
echo "==========================================\n\n";

// Get the first active Binance API key
$apiKey = ApiKey::where('exchange', 'binance')
    ->where('is_active', true)
    ->first();

if (!$apiKey) {
    echo "❌ No active Binance API key found. Please create one first.\n";
    exit(1);
}

echo "✅ Found API key for exchange: {$apiKey->exchange}\n";
echo "📊 Testing symbol: BTC-USDT\n";
echo "🎯 Target leverage: 20x\n";
echo "💰 Margin type: isolated\n\n";

try {
    $exchangeService = new ExchangeService($apiKey);
    
    // Test leverage setting
    echo "🔄 Setting leverage to 20x...\n";
    
    // Use reflection to access the private method for testing
    $reflection = new ReflectionClass($exchangeService);
    $setLeverageMethod = $reflection->getMethod('setBinanceFuturesLeverage');
    $setLeverageMethod->setAccessible(true);
    
    $result = $setLeverageMethod->invoke($exchangeService, 'BTC-USDT', 20, 'isolated');
    
    if ($result) {
        echo "✅ Leverage set successfully!\n";
    } else {
        echo "❌ Failed to set leverage\n";
    }
    
    echo "\n🔄 Setting margin type to isolated...\n";
    
    $setMarginTypeMethod = $reflection->getMethod('setBinanceFuturesMarginType');
    $setMarginTypeMethod->setAccessible(true);
    
    $result = $setMarginTypeMethod->invoke($exchangeService, 'BTC-USDT', 'isolated');
    
    if ($result) {
        echo "✅ Margin type set successfully!\n";
    } else {
        echo "❌ Failed to set margin type\n";
    }
    
    echo "\n🎉 Test completed! Check the logs for detailed information.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
