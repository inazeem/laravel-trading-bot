<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING ORDER CANCELLATION LOGS ===\n";

$logs = \App\Models\TradingBotLog::where('futures_trading_bot_id', 7)
    ->where(function($query) {
        $query->where('message', 'LIKE', '%CLEANUP%')
              ->orWhere('message', 'LIKE', '%cancelled%')
              ->orWhere('message', 'LIKE', '%Protective orders%')
              ->orWhere('message', 'LIKE', '%cancel%');
    })
    ->latest()
    ->take(15)
    ->get();

foreach($logs as $log) {
    echo "{$log->created_at->format('H:i:s')} [{$log->level}] {$log->message}\n";
}

echo "\n=== CURRENT TRADE STATUS ===\n";
$trade = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->where('status', 'open')
    ->first();

if ($trade) {
    echo "Trade ID: {$trade->id}\n";
    echo "Status: {$trade->status}\n";
    echo "Stop Loss Order ID: {$trade->stop_loss_order_id}\n";
    echo "Take Profit Order ID: {$trade->take_profit_order_id}\n";
    echo "Stop Loss Price: {$trade->stop_loss}\n";
    echo "Take Profit Price: {$trade->take_profit}\n";
} else {
    echo "No open trades found\n";
}

echo "\n=== TESTING ORDER CANCELLATION FUNCTIONALITY ===\n";

if ($trade && ($trade->stop_loss_order_id || $trade->take_profit_order_id)) {
    echo "Current trade has SL/TP orders that should be cancelled when position closes.\n";
    echo "Testing cancellation methods...\n";
    
    $exchangeService = new \App\Services\ExchangeService($trade->futuresTradingBot->apiKey);
    
    // Test individual order cancellation
    if ($trade->stop_loss_order_id) {
        echo "Testing SL order cancellation (ID: {$trade->stop_loss_order_id})...\n";
        $slResult = $exchangeService->cancelOrder($trade->symbol, $trade->stop_loss_order_id);
        echo "SL cancellation result: " . ($slResult ? 'SUCCESS' : 'FAILED') . "\n";
    }
    
    if ($trade->take_profit_order_id) {
        echo "Testing TP order cancellation (ID: {$trade->take_profit_order_id})...\n";
        $tpResult = $exchangeService->cancelOrder($trade->symbol, $trade->take_profit_order_id);
        echo "TP cancellation result: " . ($tpResult ? 'SUCCESS' : 'FAILED') . "\n";
    }
    
    // Test cancel all orders
    echo "Testing cancel all orders for symbol...\n";
    $cancelAllResult = $exchangeService->cancelAllOpenOrdersForSymbol($trade->symbol);
    echo "Cancel all result: " . ($cancelAllResult ? 'SUCCESS' : 'FAILED') . "\n";
    
} else {
    echo "No SL/TP orders to test cancellation with.\n";
}

echo "\n=== VERIFICATION ===\n";
echo "The system should automatically cancel SL/TP orders when:\n";
echo "1. Position is manually closed\n";
echo "2. Position is closed by stop loss\n";
echo "3. Position is closed by take profit\n";
echo "4. Position is closed due to margin issues\n";
echo "5. Position no longer exists on exchange\n";
