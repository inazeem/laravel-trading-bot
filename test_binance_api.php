<?php

/**
 * Test Binance API Connection
 * 
 * Simple test to debug Binance API connection and balance fetching
 */

require_once 'vendor/autoload.php';

use App\Models\TradingBot;
use App\Models\ApiKey;
use App\Services\ExchangeService;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Test Binance API Connection\n";
echo "=============================\n\n";

try {
    // Get the first trading bot
    $bot = TradingBot::first();
    
    if (!$bot) {
        echo "❌ No trading bots found\n";
        exit;
    }
    
    echo "🤖 Bot: {$bot->name} ({$bot->symbol})\n";
    echo "Exchange: {$bot->exchange}\n";
    echo "API Key ID: {$bot->api_key_id}\n\n";
    
    // Get API key
    $apiKey = $bot->apiKey;
    if (!$apiKey) {
        echo "❌ No API key found for bot\n";
        exit;
    }
    
    echo "🔑 API Key Details:\n";
    echo "Exchange: {$apiKey->exchange}\n";
    echo "Active: " . ($apiKey->is_active ? 'Yes' : 'No') . "\n";
    echo "Has Trade Permission: " . ($apiKey->hasPermission('trade') ? 'Yes' : 'No') . "\n\n";
    
    // Test ExchangeService
    echo "📊 Testing ExchangeService...\n";
    
    $exchangeService = new ExchangeService($apiKey);
    
    try {
        $balances = $exchangeService->getBalance();
        
        echo "✅ Balance fetch successful\n";
        echo "Total balance entries: " . count($balances) . "\n\n";
        
        if (count($balances) > 0) {
            echo "📋 First 10 balance entries:\n";
            $count = 0;
            foreach ($balances as $balance) {
                if ($count >= 10) break;
                
                $currency = $balance['currency'] ?? $balance['asset'] ?? 'Unknown';
                $available = $balance['available'] ?? $balance['free'] ?? 0;
                $locked = $balance['locked'] ?? 0;
                
                if ((float)$available > 0 || (float)$locked > 0) {
                    echo "  {$currency}: Available={$available}, Locked={$locked}\n";
                }
                $count++;
            }
            
            // Look specifically for SUI
            $suiFound = false;
            foreach ($balances as $balance) {
                $currency = $balance['currency'] ?? $balance['asset'] ?? null;
                if ($currency === 'SUI') {
                    $available = $balance['available'] ?? $balance['free'] ?? 0;
                    $locked = $balance['locked'] ?? 0;
                    echo "\n🎯 SUI Balance Found:\n";
                    echo "  Available: {$available}\n";
                    echo "  Locked: {$locked}\n";
                    echo "  Raw: " . json_encode($balance) . "\n";
                    $suiFound = true;
                    break;
                }
            }
            
            if (!$suiFound) {
                echo "\n❌ SUI not found in balance response\n";
            }
            
        } else {
            echo "❌ No balance entries returned\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Error fetching balance: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
