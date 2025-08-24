<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;
use Illuminate\Support\Facades\Log;

echo "=== FIX POSITION SYNC ISSUE ===\n\n";

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
                            echo "  ✅ Order was filled\n";
                            
                            // Check if there's a corresponding position on Binance
                            $binancePositions = $exchangeService->getOpenPositions($bot->symbol);
                            $positionExists = false;
                            
                            foreach ($binancePositions as $position) {
                                if ($position['symbol'] === str_replace('-', '', $trade->symbol) && 
                                    $position['side'] === $trade->side) {
                                    $positionExists = true;
                                    echo "  ✅ Position found on Binance - keeping trade open\n";
                                    break;
                                }
                            }
                            
                            if (!$positionExists) {
                                echo "  ❌ Order was filled but no position on Binance - position was closed\n";
                                echo "  Closing trade as completed...\n";
                                
                                // Try to get the current price to calculate PnL
                                $currentPrice = $exchangeService->getCurrentPrice($trade->symbol);
                                
                                if ($currentPrice) {
                                    // Calculate PnL based on entry and current price
                                    if ($trade->side === 'long') {
                                        $pnl = ($currentPrice - $trade->entry_price) * $trade->quantity;
                                    } else {
                                        $pnl = ($trade->entry_price - $currentPrice) * $trade->quantity;
                                    }
                                    
                                    echo "  Current price: {$currentPrice}, Calculated PnL: {$pnl}\n";
                                    
                                    $trade->update([
                                        'status' => 'closed',
                                        'exit_price' => $currentPrice,
                                        'realized_pnl' => $pnl,
                                        'closed_at' => now()
                                    ]);
                                } else {
                                    // Use entry price if we can't get current price
                                    echo "  Could not get current price, using entry price\n";
                                    $trade->update([
                                        'status' => 'closed',
                                        'exit_price' => $trade->entry_price,
                                        'realized_pnl' => 0,
                                        'closed_at' => now()
                                    ]);
                                }
                                
                                echo "  ✅ Trade closed as completed\n";
                            }
                            
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

// Show recent closed trades
echo "\n=== RECENT CLOSED TRADES ===\n";
$recentClosedTrades = FuturesTrade::where('status', 'closed')
    ->orWhere('status', 'cancelled')
    ->latest()
    ->take(5)
    ->get();

foreach ($recentClosedTrades as $trade) {
    echo "  - ID: {$trade->id}, {$trade->side} {$trade->symbol}\n";
    echo "    Entry: {$trade->entry_price}, Exit: {$trade->exit_price}\n";
    echo "    PnL: {$trade->realized_pnl}, Status: {$trade->status}\n";
    echo "    Closed: {$trade->closed_at}\n\n";
}

echo "\n=== SYNC COMPLETED ===\n";
