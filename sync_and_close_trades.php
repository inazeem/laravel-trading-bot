<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;
use Illuminate\Support\Facades\Log;

echo "=== SYNC AND CLOSE PHANTOM TRADES ===\n\n";

// Get active bots
$bots = FuturesTradingBot::where('is_active', true)->get();

foreach ($bots as $bot) {
    echo "Processing bot: {$bot->name} ({$bot->symbol})\n";
    echo "=====================================\n";
    
    // Create exchange service for this bot
    $exchangeService = new ExchangeService($bot->apiKey);
    
    // Get open trades from database
    $openTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
        ->where('status', 'open')
        ->get();
    
    echo "Open trades in database: " . $openTrades->count() . "\n";
    
    if ($openTrades->count() > 0) {
        foreach ($openTrades as $trade) {
            echo "\nChecking trade ID: {$trade->id}\n";
            echo "  Symbol: {$trade->symbol}\n";
            echo "  Side: {$trade->side}\n";
            echo "  Entry Price: {$trade->entry_price}\n";
            echo "  Order ID: {$trade->order_id}\n";
            
            // Check if order exists on Binance
            if ($trade->order_id) {
                echo "  Checking order status on Binance...\n";
                
                try {
                    $orderStatus = $exchangeService->getOrderStatus($trade->symbol, $trade->order_id);
                    
                    if ($orderStatus) {
                        echo "  Order status: {$orderStatus['status']}\n";
                        
                        if (in_array($orderStatus['status'], ['FILLED', 'PARTIALLY_FILLED'])) {
                            echo "  ✅ Order was filled - keeping as open\n";
                        } elseif (in_array($orderStatus['status'], ['CANCELED', 'REJECTED', 'EXPIRED'])) {
                            echo "  ❌ Order was {$orderStatus['status']} - closing trade\n";
                            $trade->update([
                                'status' => 'cancelled',
                                'exit_price' => $trade->entry_price,
                                'realized_pnl' => 0,
                                'closed_at' => now()
                            ]);
                            echo "  ✅ Trade closed as cancelled\n";
                        } else {
                            echo "  ⏳ Order status: {$orderStatus['status']} - keeping as is\n";
                        }
                    } else {
                        echo "  ❌ Order not found on Binance - closing trade\n";
                        $trade->update([
                            'status' => 'cancelled',
                            'exit_price' => $trade->entry_price,
                            'realized_pnl' => 0,
                            'closed_at' => now()
                        ]);
                        echo "  ✅ Trade closed as cancelled\n";
                    }
                } catch (\Exception $e) {
                    echo "  ❌ Error checking order: " . $e->getMessage() . "\n";
                    echo "  Closing trade due to error\n";
                    $trade->update([
                        'status' => 'cancelled',
                        'exit_price' => $trade->entry_price,
                        'realized_pnl' => 0,
                        'closed_at' => now()
                    ]);
                    echo "  ✅ Trade closed as cancelled\n";
                }
            } else {
                echo "  ❌ No order ID - closing trade\n";
                $trade->update([
                    'status' => 'cancelled',
                    'exit_price' => $trade->entry_price,
                    'realized_pnl' => 0,
                    'closed_at' => now()
                ]);
                echo "  ✅ Trade closed as cancelled\n";
            }
        }
    }
    
    // Get open positions from Binance
    echo "\nChecking open positions on Binance...\n";
    try {
        $binancePositions = $exchangeService->getOpenPositions($bot->symbol);
        
        if (empty($binancePositions)) {
            echo "✅ No open positions found on Binance\n";
        } else {
            echo "Found " . count($binancePositions) . " open position(s) on Binance:\n";
            foreach ($binancePositions as $position) {
                echo "  - {$position['side']} {$position['symbol']} (Qty: {$position['quantity']}, Entry: {$position['entry_price']})\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ Error getting Binance positions: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Final summary
echo "=== FINAL SUMMARY ===\n";
$remainingOpenTrades = FuturesTrade::where('status', 'open')->count();
echo "Remaining open trades in database: {$remainingOpenTrades}\n";

if ($remainingOpenTrades > 0) {
    echo "\nRemaining open trades:\n";
    $trades = FuturesTrade::where('status', 'open')->get();
    foreach ($trades as $trade) {
        echo "  - ID: {$trade->id}, Bot: {$trade->bot->name}, Symbol: {$trade->symbol}, Side: {$trade->side}\n";
    }
} else {
    echo "✅ All phantom trades have been closed!\n";
}

echo "\n=== SYNC COMPLETED ===\n";
