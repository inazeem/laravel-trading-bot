<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);
$exchangeService = new \App\Services\ExchangeService($bot->apiKey);

echo "=== FIXING SL/TP ORDERS ===\n";

// Get current trade
$trade = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->where('status', 'open')
    ->first();

if (!$trade) {
    echo "No open trades found\n";
    exit;
}

echo "Current Trade:\n";
echo "  ID: {$trade->id}\n";
echo "  Symbol: {$trade->symbol}\n";
echo "  Side: {$trade->side}\n";
echo "  Entry Price: {$trade->entry_price}\n";
echo "  Quantity: {$trade->quantity}\n";

// Calculate 1:2 risk/reward ratio
$entryPrice = $trade->entry_price;
$quantity = $trade->quantity;

if ($trade->side === 'long') {
    // For long position: SL below entry, TP above entry
    $riskAmount = $entryPrice * 0.02; // 2% risk
    $stopLoss = $entryPrice - $riskAmount;
    $takeProfit = $entryPrice + ($riskAmount * 2); // 1:2 ratio
} else {
    // For short position: SL above entry, TP below entry  
    $riskAmount = $entryPrice * 0.02; // 2% risk
    $stopLoss = $entryPrice + $riskAmount;
    $takeProfit = $entryPrice - ($riskAmount * 2); // 1:2 ratio
}

echo "\nCalculated SL/TP with 1:2 Risk/Reward:\n";
echo "  Stop Loss: {$stopLoss}\n";
echo "  Take Profit: {$takeProfit}\n";

$risk = abs($entryPrice - $stopLoss);
$reward = abs($takeProfit - $entryPrice);
$riskRewardRatio = $reward / $risk;

echo "  Risk: {$risk}\n";
echo "  Reward: {$reward}\n";
echo "  R/R Ratio: {$riskRewardRatio}\n";

// Place SL/TP orders
echo "\nPlacing SL/TP orders...\n";

try {
    // Place stop loss order
    echo "Placing stop loss order...\n";
    $slOrderId = $exchangeService->placeStopLossOrder(
        $trade->symbol,
        $trade->side === 'long' ? 'BUY' : 'SELL', // Original side for SL
        $quantity,
        $stopLoss
    );
    
    if ($slOrderId) {
        echo "✅ Stop Loss order placed: {$slOrderId}\n";
    } else {
        echo "❌ Stop Loss order failed\n";
    }
    
    // Place take profit order
    echo "Placing take profit order...\n";
    $tpOrderId = $exchangeService->placeTakeProfitOrder(
        $trade->symbol,
        $trade->side === 'long' ? 'BUY' : 'SELL', // Original side for TP
        $quantity,
        $takeProfit
    );
    
    if ($tpOrderId) {
        echo "✅ Take Profit order placed: {$tpOrderId}\n";
    } else {
        echo "❌ Take Profit order failed\n";
    }
    
    // Update trade record
    if ($slOrderId || $tpOrderId) {
        $trade->update([
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'stop_loss_order_id' => $slOrderId,
            'take_profit_order_id' => $tpOrderId
        ]);
        echo "✅ Trade record updated with SL/TP\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error placing orders: " . $e->getMessage() . "\n";
}
