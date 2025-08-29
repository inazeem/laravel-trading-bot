<?php

require_once 'vendor/autoload.php';

use App\Models\ApiKey;
use App\Services\ExchangeService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Testing Fixed KuCoin Position Detection...\n\n";

try {
    // Get KuCoin API key
    $apiKey = ApiKey::where('exchange', 'kucoin')->first();
    
    if (!$apiKey) {
        echo "❌ No KuCoin API key found in database\n";
        exit(1);
    }
    
    echo "✅ Found KuCoin API key\n";
    
    // Create exchange service
    $exchangeService = new ExchangeService($apiKey);
    
    echo "\n📊 Testing KuCoin position fetching with updated logic...\n";
    
    // Get all positions using the updated method
    $positions = $exchangeService->getOpenPositions();
    
    echo "📈 Positions found: " . count($positions) . "\n\n";
    
    if (!empty($positions)) {
        echo "✅ SUCCESS! Found positions:\n";
        foreach ($positions as $index => $position) {
            echo "Position " . ($index + 1) . ":\n";
            echo "  Symbol: " . $position['symbol'] . "\n";
            echo "  Side: " . $position['side'] . "\n";
            echo "  Quantity: " . $position['quantity'] . "\n";
            echo "  Entry Price: " . $position['entry_price'] . "\n";
            echo "  Unrealized PnL: " . $position['unrealized_pnl'] . "\n";
            echo "  Leverage: " . $position['leverage'] . "\n";
            echo "  Margin Type: " . $position['margin_type'] . "\n";
            echo "  ---\n";
        }
    } else {
        echo "❌ Still no positions detected. Check logs for processing details.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest complete!\n";
