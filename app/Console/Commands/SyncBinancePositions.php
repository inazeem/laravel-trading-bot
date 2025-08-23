<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Log;

class SyncBinancePositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:binance-positions {--bot-id= : Specific bot ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync database with actual Binance positions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔄 Starting Binance position synchronization...");
        
        try {
            // Get bots to sync
            $botId = $this->option('bot-id');
            if ($botId) {
                $bots = FuturesTradingBot::where('id', $botId)->get();
            } else {
                $bots = FuturesTradingBot::where('exchange', 'binance')->get();
            }
            
            if ($bots->isEmpty()) {
                $this->error("No Binance bots found to sync.");
                return 1;
            }
            
            $this->info("Found " . $bots->count() . " Binance bot(s) to sync.");
            
            foreach ($bots as $bot) {
                $this->info("\n📊 Syncing bot: {$bot->name} ({$bot->symbol})");
                
                // Get API key for this bot
                $apiKey = $bot->apiKey;
                if (!$apiKey) {
                    $this->error("No API key found for bot {$bot->name}");
                    continue;
                }
                
                $exchangeService = new ExchangeService($apiKey);
                
                // Get actual open positions from Binance
                $this->info("🔍 Fetching actual positions from Binance...");
                $binancePositions = $exchangeService->getOpenPositions($bot->symbol);
                
                if (empty($binancePositions)) {
                    $this->info("✅ No open positions found on Binance for {$bot->symbol}");
                    
                    // Check if we have any trades marked as open in database
                    $openTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                        ->where('status', 'open')
                        ->get();
                    
                    if ($openTrades->isNotEmpty()) {
                        $this->warn("⚠️ Found {$openTrades->count()} trades marked as open in database but no positions on Binance");
                        
                        foreach ($openTrades as $trade) {
                            $this->info("   - Trade ID {$trade->id}: {$trade->side} {$trade->quantity} {$trade->symbol}");
                            
                            // Check order status
                            $orderStatus = $exchangeService->getOrderStatus($trade->symbol, $trade->order_id);
                            
                            if ($orderStatus) {
                                $this->info("     Order status: {$orderStatus['status']}");
                                
                                if ($orderStatus['status'] === 'FILLED') {
                                    $this->info("     ✅ Order was filled - updating trade status to open");
                                    $trade->update([
                                        'status' => 'open',
                                        'entry_price' => floatval($orderStatus['avgPrice'] ?? $trade->entry_price),
                                        'exchange_response' => $orderStatus
                                    ]);
                                } elseif (in_array($orderStatus['status'], ['CANCELED', 'REJECTED', 'EXPIRED'])) {
                                    $this->info("     ❌ Order was {$orderStatus['status']} - updating trade status to cancelled");
                                    $trade->update([
                                        'status' => 'cancelled',
                                        'exchange_response' => $orderStatus
                                    ]);
                                } else {
                                    $this->info("     ⏳ Order status: {$orderStatus['status']} - keeping as is");
                                }
                            } else {
                                $this->warn("     ⚠️ Could not fetch order status");
                            }
                        }
                    }
                } else {
                    $this->info("📈 Found " . count($binancePositions) . " open position(s) on Binance:");
                    
                    foreach ($binancePositions as $position) {
                        $this->info("   - {$position['side']} {$position['quantity']} {$position['symbol']} @ {$position['entry_price']}");
                        $this->info("     Unrealized PnL: {$position['unrealized_pnl']}");
                        
                        // Check if we have a corresponding trade in database
                        // Normalize symbol for comparison (add dash for database symbol)
                        $dbSymbol = str_replace('USDT', '-USDT', $position['symbol']);
                        $trade = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                            ->where('symbol', $dbSymbol)
                            ->where('side', $position['side'])
                            ->where('status', 'open')
                            ->first();
                        
                        if ($trade) {
                            $this->info("     ✅ Found matching open trade in database (ID: {$trade->id})");
                            
                            // Update trade with current position data
                            $trade->update([
                                'quantity' => $position['quantity'],
                                'entry_price' => $position['entry_price'],
                                'unrealized_pnl' => $position['unrealized_pnl'],
                                'leverage' => $position['leverage'],
                                'margin_type' => $position['margin_type']
                            ]);
                            
                            $this->info("     📝 Updated trade with current position data");
                        } else {
                            $this->warn("     ⚠️ No matching open trade found in database");
                            
                            // Check if we have a closed trade that should be open
                            $closedTrade = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                                ->where('symbol', $dbSymbol)
                                ->where('side', $position['side'])
                                ->where('status', 'closed')
                                ->latest()
                                ->first();
                            
                            if ($closedTrade) {
                                $this->info("     🔄 Found closed trade that should be open - reopening...");
                                
                                $closedTrade->update([
                                    'status' => 'open',
                                    'quantity' => $position['quantity'],
                                    'entry_price' => $position['entry_price'],
                                    'unrealized_pnl' => $position['unrealized_pnl'],
                                    'exit_price' => null,
                                    'closed_at' => null
                                ]);
                                
                                $this->info("     ✅ Reopened trade ID {$closedTrade->id}");
                            } else {
                                $this->warn("     ❌ No matching trade found - position may have been opened manually");
                            }
                        }
                    }
                }
            }
            
            $this->info("\n✅ Binance position synchronization completed!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error during synchronization: " . $e->getMessage());
            Log::error("SyncBinancePositions error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
