<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);
$exchangeService = new \App\Services\ExchangeService($bot->apiKey);

echo "=== DEBUGGING SL/TP ORDERS ===\n";

// Get current trade
$trade = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->where('status', 'open')
    ->first();

if (!$trade) {
    echo "No open trades found\n";
    exit;
}

echo "Current Trade:\n";
echo "  Symbol: {$trade->symbol}\n";
echo "  Side: {$trade->side}\n";
echo "  Entry Price: {$trade->entry_price}\n";
echo "  Quantity: {$trade->quantity}\n";

// Test with different parameters
$entryPrice = $trade->entry_price;
$quantity = $trade->quantity;

// Calculate 1:2 risk/reward
$riskAmount = $entryPrice * 0.02; // 2% risk
$stopLoss = $entryPrice - $riskAmount;
$takeProfit = $entryPrice + ($riskAmount * 2);

echo "\nCalculated SL/TP:\n";
echo "  Stop Loss: {$stopLoss}\n";
echo "  Take Profit: {$takeProfit}\n";

// Test the order parameters
echo "\n=== TESTING ORDER PARAMETERS ===\n";

// Check if quantity precision is correct
$precision = 1; // SOLUSDT precision
$roundedQuantity = round($quantity, $precision);
echo "Quantity precision test:\n";
echo "  Original: {$quantity}\n";
echo "  Rounded to {$precision} decimal: {$roundedQuantity}\n";

// Check price precision
$stopLossRounded = round($stopLoss, 4);
$takeProfitRounded = round($takeProfit, 4);
echo "Price precision test:\n";
echo "  SL: {$stopLoss} -> {$stopLossRounded}\n";
echo "  TP: {$takeProfit} -> {$takeProfitRounded}\n";

// Test the actual API call with detailed error handling
echo "\n=== TESTING API CALLS ===\n";

try {
    echo "Testing stop loss order placement...\n";
    
    // Use reflection to access private method for testing
    $reflection = new ReflectionClass($exchangeService);
    $method = $reflection->getMethod('placeBinanceStopLossOrderDirect');
    $method->setAccessible(true);
    
    $slOrderId = $method->invokeArgs($exchangeService, [
        $trade->symbol,
        $trade->side === 'long' ? 'BUY' : 'SELL',
        $roundedQuantity,
        $stopLossRounded
    ]);
    
    echo "SL Order Result: " . ($slOrderId ? $slOrderId : 'FAILED') . "\n";
    
} catch (\Exception $e) {
    echo "SL Order Error: " . $e->getMessage() . "\n";
}

try {
    echo "Testing take profit order placement...\n";
    
    $reflection = new ReflectionClass($exchangeService);
    $method = $reflection->getMethod('placeBinanceTakeProfitOrderDirect');
    $method->setAccessible(true);
    
    $tpOrderId = $method->invokeArgs($exchangeService, [
        $trade->symbol,
        $trade->side === 'long' ? 'BUY' : 'SELL',
        $roundedQuantity,
        $takeProfitRounded
    ]);
    
    echo "TP Order Result: " . ($tpOrderId ? $tpOrderId : 'FAILED') . "\n";
    
} catch (\Exception $e) {
    echo "TP Order Error: " . $e->getMessage() . "\n";
}
