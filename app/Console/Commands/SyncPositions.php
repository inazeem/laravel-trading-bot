<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;

class SyncPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'futures:sync-positions {--bot-id= : Specific bot ID to sync} {--all : Sync all bots}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync futures trading bot positions with exchange';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting position synchronization...');

        if ($this->option('all')) {
            $bots = FuturesTradingBot::where('is_active', true)->get();
            $this->info("Found {$bots->count()} active bots to sync");
        } elseif ($botId = $this->option('bot-id')) {
            $bots = FuturesTradingBot::where('id', $botId)->where('is_active', true)->get();
            if ($bots->isEmpty()) {
                $this->error("No active bot found with ID: {$botId}");
                return 1;
            }
        } else {
            $this->error('Please specify --bot-id or --all option');
            return 1;
        }

        $totalSynced = 0;
        $totalClosed = 0;
        $totalUpdated = 0;

        foreach ($bots as $bot) {
            $this->info("\n📊 Syncing bot: {$bot->name} ({$bot->symbol})");
            
            try {
                $exchangeService = new ExchangeService($bot->apiKey);
                
                // Get open trades from database
                $openTrades = FuturesTrade::where('futures_trading_bot_id', $bot->id)
                    ->where('status', 'open')
                    ->get();
                
                $this->info("Found {$openTrades->count()} open trades in database");
                
                if ($openTrades->isEmpty()) {
                    $this->info("✅ No open trades to sync");
                    continue;
                }
                
                // Get actual positions from exchange
                $exchangePositions = $exchangeService->getOpenPositions($bot->symbol);
                $this->info("Found " . count($exchangePositions) . " positions on exchange");
                
                foreach ($openTrades as $trade) {
                    $this->info("  🔍 Checking trade ID: {$trade->id} ({$trade->side} {$trade->symbol})");
                    
                    // Check if order exists on exchange
                    if ($trade->order_id) {
                        $orderStatus = $exchangeService->getOrderStatus($trade->symbol, $trade->order_id);
                        
                        if (!$orderStatus) {
                            $this->warn("    ❌ Order not found on exchange - marking as cancelled");
                            $trade->update([
                                'status' => 'cancelled',
                                'exit_price' => $trade->entry_price,
                                'realized_pnl' => 0,
                                'closed_at' => now()
                            ]);
                            $totalClosed++;
                            continue;
                        }
                        
                        if (in_array($orderStatus['status'], ['CANCELED', 'REJECTED', 'EXPIRED'])) {
                            $this->warn("    ❌ Order was {$orderStatus['status']} - marking as cancelled");
                            $trade->update([
                                'status' => 'cancelled',
                                'exit_price' => $trade->entry_price,
                                'realized_pnl' => 0,
                                'closed_at' => now()
                            ]);
                            $totalClosed++;
                            continue;
                        }
                    }
                    
                    // Check if position exists on exchange
                    $positionExists = false;
                    foreach ($exchangePositions as $position) {
                        // Convert KuCoin futures symbol (e.g., SOLUSDTM) to database format (e.g., SOL-USDT)
                        $dbSymbol = $position['symbol'];
                        if (str_ends_with($dbSymbol, 'USDTM')) {
                            $dbSymbol = str_replace('USDTM', '', $dbSymbol) . '-USDT';
                        } elseif (str_contains($dbSymbol, 'USDT')) {
                            $dbSymbol = str_replace('USDT', '-USDT', $dbSymbol);
                        }
                        
                        if ($dbSymbol === $trade->symbol && $position['side'] === $trade->side) {
                            $positionExists = true;
                            
                            // Update trade with current position data
                            $trade->update([
                                'quantity' => $position['quantity'],
                                'entry_price' => $position['entry_price'],
                                'unrealized_pnl' => $position['unrealized_pnl']
                            ]);
                            
                            $this->info("    ✅ Position found - updated with current data");
                            $totalUpdated++;
                            break;
                        }
                    }
                    
                    if (!$positionExists) {
                        $this->warn("    ⚠️ Position not found on exchange - position was likely closed");
                        
                        // Get current price to calculate PnL
                        $currentPrice = $exchangeService->getCurrentPrice($trade->symbol);
                        
                        if ($currentPrice) {
                            if ($trade->side === 'long') {
                                $pnl = ($currentPrice - $trade->entry_price) * $trade->quantity;
                            } else {
                                $pnl = ($trade->entry_price - $currentPrice) * $trade->quantity;
                            }
                            
                            $trade->update([
                                'status' => 'closed',
                                'exit_price' => $currentPrice,
                                'realized_pnl' => $pnl,
                                'closed_at' => now()
                            ]);
                            
                            $this->info("    ✅ Closed trade with PnL: {$pnl}");
                        } else {
                            $trade->update([
                                'status' => 'closed',
                                'exit_price' => $trade->entry_price,
                                'realized_pnl' => 0,
                                'closed_at' => now()
                            ]);
                            
                            $this->info("    ✅ Closed trade with zero PnL");
                        }
                        $totalClosed++;
                    }
                }
                
                $totalSynced++;
                
            } catch (\Exception $e) {
                $this->error("❌ Error syncing bot {$bot->name}: " . $e->getMessage());
            }
        }
        
        $this->info("\n🎯 Synchronization Summary:");
        $this->info("  📊 Bots synced: {$totalSynced}");
        $this->info("  📝 Trades updated: {$totalUpdated}");
        $this->info("  ❌ Trades closed: {$totalClosed}");
        $this->info("✅ Position synchronization completed!");
        
        return 0;
    }
}
