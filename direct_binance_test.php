<?php

/**
 * Direct Binance API Test
 * 
 * Test Binance API directly to see the response
 */

require_once 'vendor/autoload.php';

use App\Models\TradingBot;
use Illuminate\Support\Facades\Http;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Direct Binance API Test\n";
echo "=========================\n\n";

try {
    // Get the first trading bot
    $bot = TradingBot::first();
    
    if (!$bot) {
        echo "❌ No trading bots found\n";
        exit;
    }
    
    // Get API key
    $apiKey = $bot->apiKey;
    if (!$apiKey) {
        echo "❌ No API key found for bot\n";
        exit;
    }
    
    echo "🔑 API Key Details:\n";
    echo "Exchange: {$apiKey->exchange}\n";
    echo "API Key: " . substr($apiKey->decrypted_api_key, 0, 10) . "...\n";
    echo "Secret Key: " . substr($apiKey->decrypted_api_secret, 0, 10) . "...\n\n";
    
    // Make direct API call
    echo "📊 Making direct Binance API call...\n";
    
    // First get server time to sync timestamp
    echo "🕐 Getting Binance server time...\n";
    $serverTimeResponse = Http::get('https://api.binance.com/api/v3/time');
    $serverTime = 0;
    
    if ($serverTimeResponse->successful()) {
        $serverData = $serverTimeResponse->json();
        $serverTime = $serverData['serverTime'] ?? 0;
        echo "✅ Server time: {$serverTime}\n";
    } else {
        echo "❌ Failed to get server time\n";
    }
    
    // Use server time if available, otherwise use local time with adjustment
    $timestamp = $serverTime > 0 ? $serverTime : (round(microtime(true) * 1000) - 2000);
    $endpoint = '/api/v3/account';
    
    $params = [
        'timestamp' => $timestamp
    ];
    
    $queryString = http_build_query($params);
    $signature = hash_hmac('sha256', $queryString, $apiKey->decrypted_api_secret);
    $params['signature'] = $signature;
    
    echo "Timestamp: {$timestamp}\n";
    echo "Signature: " . substr($signature, 0, 20) . "...\n";
    echo "URL: https://api.binance.com{$endpoint}\n\n";
    
    $response = Http::withHeaders([
        'X-MBX-APIKEY' => $apiKey->decrypted_api_key
    ])->get('https://api.binance.com' . $endpoint, $params);
    
    echo "📋 API Response:\n";
    echo "Status Code: " . $response->status() . "\n";
    echo "Response Body:\n";
    echo $response->body() . "\n\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (isset($data['balances'])) {
            echo "✅ Found balances array with " . count($data['balances']) . " entries\n";
            
            // Look for SUI
            foreach ($data['balances'] as $balance) {
                $currency = $balance['asset'] ?? 'Unknown';
                $free = $balance['free'] ?? 0;
                $locked = $balance['locked'] ?? 0;
                
                if ($currency === 'SUI') {
                    echo "\n🎯 SUI Balance Found:\n";
                    echo "  Free: {$free}\n";
                    echo "  Locked: {$locked}\n";
                    echo "  Raw: " . json_encode($balance) . "\n";
                }
                
                if ((float)$free > 0 || (float)$locked > 0) {
                    echo "  {$currency}: Free={$free}, Locked={$locked}\n";
                }
            }
        } else {
            echo "❌ No 'balances' key found in response\n";
            echo "Available keys: " . implode(', ', array_keys($data)) . "\n";
        }
    } else {
        echo "❌ API request failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
