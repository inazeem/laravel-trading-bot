<?php

namespace App\Services;

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Models\FuturesSignal;
use Illuminate\Support\Facades\Log;
use App\Services\FuturesTradingBotLogger;
use Illuminate\Support\Facades\DB;

class SimpleFuturesTradingBotService
{
    private FuturesTradingBot $bot;
    private ExchangeService $exchangeService;
    private FuturesTradingBotLogger $logger;

    public function __construct(FuturesTradingBot $bot)
    {
        $this->bot = $bot->load('apiKey');
        $this->exchangeService = new ExchangeService($bot->apiKey);
        $this->logger = new FuturesTradingBotLogger($bot);
    }

    /**
     * Run the simplified futures trading bot
     */
    public function run(): void
    {
        try {
            $this->logger->info("🚀 [SIMPLE FUTURES BOT] Starting '{$this->bot->name}'");
            $this->logger->info("📊 [CONFIG] Symbol: {$this->bot->symbol}, Exchange: {$this->bot->exchange}");
            $this->logger->info("⚙️ [CONFIG] Risk: {$this->bot->risk_percentage}%, Max Position: {$this->bot->max_position_size}");
            
            // Update bot status
            $this->bot->update(['status' => 'running', 'last_run_at' => now()]);
            
            // Get current price
            $currentPrice = $this->exchangeService->getCurrentPrice($this->bot->symbol);
            if (!$currentPrice) {
                $this->logger->error("❌ Failed to get current price for {$this->bot->symbol}");
                return;
            }
            
            $this->logger->info("💰 Current price: {$currentPrice}");
            
            // Check for existing position
            $existingTrade = $this->getExistingPosition();
            if ($existingTrade) {
                $this->handleExistingPosition($existingTrade, $currentPrice);
                return;
            }
            
            // Check if we should place a new trade
            if ($this->shouldPlaceNewTrade()) {
                $this->placeNewTrade($currentPrice);
            }
            
        } catch (\Exception $e) {
            $this->logger->error("❌ Error in futures bot: " . $e->getMessage());
            $this->bot->update(['status' => 'error', 'last_error' => $e->getMessage()]);
        }
    }

    /**
     * Check if we should place a new trade
     */
    private function shouldPlaceNewTrade(): bool
    {
        // Simple cooldown check
        $lastTrade = FuturesTrade::where('bot_id', $this->bot->id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($lastTrade && $lastTrade->created_at->diffInMinutes(now()) < 30) {
            $this->logger->info("⏳ Cooldown period - waiting 30 minutes between trades");
            return false;
        }
        
        return true;
    }

    /**
     * Place a new futures trade
     */
    private function placeNewTrade(float $currentPrice): void
    {
        $this->logger->info("📈 Placing new futures trade...");
        
        // Simple position sizing
        $positionSize = $this->calculateSimplePositionSize($currentPrice);
        if ($positionSize <= 0) {
            $this->logger->warning("⚠️ Insufficient balance for trade");
            return;
        }
        
        // Simple stop loss and take profit
        $stopLoss = $this->calculateSimpleStopLoss($currentPrice);
        $takeProfit = $this->calculateSimpleTakeProfit($currentPrice);
        
        $this->logger->info("🎯 Stop Loss: {$stopLoss}, Take Profit: {$takeProfit}");
        
        // Place the order
        $order = $this->placeFuturesOrder($positionSize, $stopLoss, $takeProfit);
        
        if ($order) {
            $this->logger->info("✅ Order placed successfully");
            $this->saveTrade($order, $currentPrice, $stopLoss, $takeProfit);
        } else {
            $this->logger->error("❌ Failed to place order");
        }
    }

    /**
     * Calculate simple position size
     */
    private function calculateSimplePositionSize(float $currentPrice): float
    {
        $balance = $this->exchangeService->getBalance();
        $riskAmount = $balance * ($this->bot->risk_percentage / 100);
        
        // Simple position size calculation
        $positionSize = $riskAmount / $currentPrice;
        
        // Apply max position size limit
        $positionSize = min($positionSize, $this->bot->max_position_size);
        
        $this->logger->info("💰 Position size: {$positionSize}");
        
        return $positionSize;
    }

    /**
     * Calculate simple stop loss
     */
    private function calculateSimpleStopLoss(float $currentPrice): float
    {
        $stopLossPercentage = $this->bot->stop_loss_percentage / 100;
        return $currentPrice * (1 - $stopLossPercentage);
    }

    /**
     * Calculate simple take profit
     */
    private function calculateSimpleTakeProfit(float $currentPrice): float
    {
        $takeProfitPercentage = $this->bot->take_profit_percentage / 100;
        return $currentPrice * (1 + $takeProfitPercentage);
    }

    /**
     * Place futures order
     */
    private function placeFuturesOrder(float $positionSize, float $stopLoss, float $takeProfit): ?array
    {
        try {
            // Simple order placement - you can customize this based on your exchange
            $order = [
                'symbol' => $this->bot->symbol,
                'side' => 'BUY', // Always long for simplicity
                'quantity' => $positionSize,
                'stop_loss' => $stopLoss,
                'take_profit' => $takeProfit,
                'leverage' => $this->bot->leverage,
                'margin_type' => $this->bot->margin_type
            ];
            
            $this->logger->info("📤 Placing order: " . json_encode($order));
            
            // Here you would call your exchange API
            // For now, return mock order
            return $order;
            
        } catch (\Exception $e) {
            $this->logger->error("❌ Order placement failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Save trade to database
     */
    private function saveTrade(array $order, float $currentPrice, float $stopLoss, float $takeProfit): void
    {
        try {
            FuturesTrade::create([
                'bot_id' => $this->bot->id,
                'symbol' => $this->bot->symbol,
                'side' => 'long',
                'entry_price' => $currentPrice,
                'quantity' => $order['quantity'],
                'stop_loss' => $stopLoss,
                'take_profit' => $takeProfit,
                'leverage' => $this->bot->leverage,
                'margin_type' => $this->bot->margin_type,
                'status' => 'open'
            ]);
            
            $this->logger->info("💾 Trade saved to database");
            
        } catch (\Exception $e) {
            $this->logger->error("❌ Failed to save trade: " . $e->getMessage());
        }
    }

    /**
     * Get existing position
     */
    private function getExistingPosition(): ?FuturesTrade
    {
        return FuturesTrade::where('bot_id', $this->bot->id)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Handle existing position
     */
    private function handleExistingPosition(FuturesTrade $trade, float $currentPrice): void
    {
        $this->logger->info("📊 Monitoring existing position");
        $this->logger->info("   Entry: {$trade->entry_price}, Current: {$currentPrice}");
        $this->logger->info("   Stop Loss: {$trade->stop_loss}, Take Profit: {$trade->take_profit}");
        
        // Check if stop loss or take profit hit
        if ($currentPrice <= $trade->stop_loss) {
            $this->closePosition($trade, 'stop_loss', $currentPrice);
        } elseif ($currentPrice >= $trade->take_profit) {
            $this->closePosition($trade, 'take_profit', $currentPrice);
        } else {
            $this->logger->info("⏳ Position still open - monitoring...");
        }
    }

    /**
     * Close position
     */
    private function closePosition(FuturesTrade $trade, string $reason, float $currentPrice): void
    {
        $this->logger->info("🔒 Closing position: {$reason}");
        
        $trade->update([
            'exit_price' => $currentPrice,
            'exit_reason' => $reason,
            'status' => 'closed',
            'closed_at' => now()
        ]);
        
        $this->logger->info("✅ Position closed");
    }
}

