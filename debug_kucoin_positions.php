<?php

require_once 'vendor/autoload.php';

use App\Models\ApiKey;
use App\Services\ExchangeService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Debugging KuCoin Positions...\n\n";

try {
    // Get KuCoin API key
    $apiKey = ApiKey::where('exchange', 'kucoin')->first();
    
    if (!$apiKey) {
        echo "❌ No KuCoin API key found in database\n";
        exit(1);
    }
    
    echo "✅ Found KuCoin API key: " . substr($apiKey->decrypted_api_key, 0, 10) . "...\n";
    
    // Create exchange service
    $exchangeService = new ExchangeService($apiKey);
    
    echo "\n📊 Fetching KuCoin positions...\n";
    
    // Get all positions
    $positions = $exchangeService->getOpenPositions();
    
    echo "📈 Raw positions response:\n";
    echo json_encode($positions, JSON_PRETTY_PRINT) . "\n\n";
    
    if (empty($positions)) {
        echo "⚠️ No positions returned. Let's check the raw API response...\n\n";
        
        // Test raw API call
        $timestamp = time() * 1000;
        $endpoint = '/api/v1/positions';
        
        // Use reflection to access private methods
        $reflection = new ReflectionClass($exchangeService);
        $createSignatureMethod = $reflection->getMethod('createKuCoinSignature');
        $createSignatureMethod->setAccessible(true);
        $createPassphraseMethod = $reflection->getMethod('createKuCoinPassphraseSignature');
        $createPassphraseMethod->setAccessible(true);
        
        $signature = $createSignatureMethod->invoke($exchangeService, 'GET', $endpoint, '', $timestamp);
        $passphrase = $createPassphraseMethod->invoke($exchangeService);
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'KC-API-KEY' => $apiKey->decrypted_api_key,
            'KC-API-SIGN' => $signature,
            'KC-API-TIMESTAMP' => $timestamp,
            'KC-API-PASSPHRASE' => $passphrase,
            'KC-API-KEY-VERSION' => '2',
        ])->get('https://api-futures.kucoin.com' . $endpoint);
        
        echo "🔍 Raw API Response Status: " . $response->status() . "\n";
        echo "🔍 Raw API Response Body:\n";
        echo $response->body() . "\n\n";
        
        if ($response->successful()) {
            $data = $response->json();
            echo "📋 Parsed API Data:\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
            
            if (isset($data['data']) && is_array($data['data'])) {
                echo "📊 Position Details:\n";
                foreach ($data['data'] as $index => $position) {
                    echo "Position {$index}:\n";
                    echo "  Symbol: " . ($position['symbol'] ?? 'N/A') . "\n";
                    echo "  Current Qty: " . ($position['currentQty'] ?? 'N/A') . "\n";
                    echo "  Position Side: " . ($position['side'] ?? 'N/A') . "\n";
                    echo "  Entry Price: " . ($position['avgEntryPrice'] ?? 'N/A') . "\n";
                    echo "  Unrealized PnL: " . ($position['unrealisedPnl'] ?? 'N/A') . "\n";
                    echo "  Is Active: " . (abs($position['currentQty'] ?? 0) > 0 ? 'YES' : 'NO') . "\n";
                    echo "  ---\n";
                }
            }
        } else {
            echo "❌ API request failed\n";
        }
    } else {
        echo "✅ Found " . count($positions) . " position(s):\n";
        foreach ($positions as $index => $position) {
            echo "Position " . ($index + 1) . ":\n";
            echo "  Symbol: " . $position['symbol'] . "\n";
            echo "  Side: " . $position['side'] . "\n";
            echo "  Quantity: " . $position['quantity'] . "\n";
            echo "  Entry Price: " . $position['entry_price'] . "\n";
            echo "  Unrealized PnL: " . $position['unrealized_pnl'] . "\n";
            echo "  Leverage: " . $position['leverage'] . "\n";
            echo "  ---\n";
        }
    }
    
    // Test account overview as well
    echo "\n💰 Testing KuCoin Futures Balance...\n";
    $balance = $exchangeService->getFuturesBalance();
    echo "Balance: " . json_encode($balance, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nDebug complete!\n";
