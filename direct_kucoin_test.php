<?php

require_once 'vendor/autoload.php';

use App\Models\ApiKey;
use App\Services\ExchangeService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Direct KuCoin Position Test...\n\n";

try {
    // Get KuCoin API key
    $apiKey = ApiKey::where('exchange', 'kucoin')->first();
    
    if (!$apiKey) {
        echo "❌ No KuCoin API key found\n";
        exit(1);
    }
    
    echo "✅ API Key: " . substr($apiKey->decrypted_api_key, 0, 10) . "...\n";
    echo "✅ Exchange: " . $apiKey->exchange . "\n\n";
    
    // Create exchange service
    $exchangeService = new ExchangeService($apiKey);
    
    // Test the specific KuCoin method using reflection
    $reflection = new ReflectionClass($exchangeService);
    $method = $reflection->getMethod('getKuCoinOpenPositions');
    $method->setAccessible(true);
    
    echo "📊 Calling getKuCoinOpenPositions directly...\n";
    $positions = $method->invoke($exchangeService);
    
    echo "📈 Result: " . json_encode($positions, JSON_PRETTY_PRINT) . "\n\n";
    
    if (!empty($positions)) {
        echo "✅ SUCCESS! Found " . count($positions) . " position(s)\n";
    } else {
        echo "❌ No positions returned from KuCoin method\n";
    }
    
    // Also test the public method
    echo "\n📊 Testing public getOpenPositions method...\n";
    $publicPositions = $exchangeService->getOpenPositions();
    echo "📈 Public method result: " . json_encode($publicPositions, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest complete!\n";
