<?php

namespace App\Services;

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Models\FuturesSignal;
use Illuminate\Support\Facades\Log;
use App\Services\FuturesTradingBotLogger;
use App\Services\StrategyService;
use Illuminate\Support\Facades\DB;

class SimpleFuturesTradingBotService
{
    private FuturesTradingBot $bot;
    private ExchangeService $exchangeService;
    private FuturesTradingBotLogger $logger;
    private StrategyService $strategyService;

    public function __construct(FuturesTradingBot $bot)
    {
        $this->bot = $bot->load('apiKey');
        $this->exchangeService = new ExchangeService($bot->apiKey);
        $this->logger = new FuturesTradingBotLogger($bot);
        $this->strategyService = new StrategyService();
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
            
            // Execute strategy logic to get trading instructions
            $this->logger->info("🧠 [STRATEGY] Executing attached strategies...");
            $strategyResults = $this->strategyService->executeStrategy($this->bot);
            $this->logger->info("📊 [STRATEGY] Strategy execution completed - " . count($strategyResults) . " results");
            
            // Check if we should place a new trade based on strategy
            if ($this->shouldPlaceNewTrade($strategyResults)) {
                $this->placeNewTrade($currentPrice, $strategyResults);
            } else {
                $this->logger->info("⏸️ [STRATEGY] No trade placed - see detailed analysis above");
            }
            
        } catch (\Exception $e) {
            $this->logger->error("❌ Error in futures bot: " . $e->getMessage());
            $this->bot->update(['status' => 'error', 'last_error' => $e->getMessage()]);
        }
    }

    /**
     * Check if we should place a new trade based on strategy results
     */
    private function shouldPlaceNewTrade(array $strategyResults): bool
    {
        $this->logger->info("🔍 [TRADE DECISION] Analyzing strategy results...");
        
        // Simple cooldown check
        $lastTrade = FuturesTrade::where('bot_id', $this->bot->id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($lastTrade && $lastTrade->created_at->diffInMinutes(now()) < 30) {
            $minutesLeft = 30 - $lastTrade->created_at->diffInMinutes(now());
            $this->logger->info("⏳ [COOLDOWN] Waiting {$minutesLeft} more minutes between trades");
            $this->logger->info("   Last trade: {$lastTrade->created_at->format('Y-m-d H:i:s')}");
            return false;
        }
        
        // Get current price for detailed analysis
        $currentPrice = $this->exchangeService->getCurrentPrice($this->bot->symbol);
        
        // Check strategy signals with detailed logging
        $hasStrongSignal = false;
        $strongestSignal = null;
        
        foreach ($strategyResults as $result) {
            if ($result['success'] && $result['result']) {
                $strategyResult = $result['result'];
                $strategyName = $result['strategy'];
                
                $this->logger->info("📊 [STRATEGY] {$strategyName} Analysis:");
                $this->logger->info("   Action: {$strategyResult['action']}");
                $this->logger->info("   Confidence: {$strategyResult['confidence']}%");
                $this->logger->info("   Reason: {$strategyResult['reason']}");
                
                // Log detailed SMC analysis if available
                if (isset($strategyResult['smc_analysis'])) {
                    $this->logSMCAnalysis($strategyResult['smc_analysis'], $currentPrice);
                }
                
                // Check if this is a strong signal
                if ($strategyResult['action'] === 'buy' && $strategyResult['confidence'] >= 70) {
                    if (!$strongestSignal || $strategyResult['confidence'] > $strongestSignal['confidence']) {
                        $strongestSignal = $strategyResult;
                        $hasStrongSignal = true;
                    }
                }
            } else {
                $this->logger->warning("⚠️ [STRATEGY] {$result['strategy']} failed: " . ($result['error'] ?? 'Unknown error'));
            }
        }
        
        if ($hasStrongSignal && $strongestSignal) {
            $this->logger->info("✅ [TRADE DECISION] Strong BUY signal detected!");
            $this->logger->info("   Strategy: " . ($strongestSignal['strategy'] ?? 'Unknown'));
            $this->logger->info("   Confidence: {$strongestSignal['confidence']}%");
            $this->logger->info("   Current Price: {$currentPrice}");
            $this->logger->info("   Action: Proceeding with trade placement");
            return true;
        }
        
        // Log why no trade was placed
        $this->logger->info("❌ [TRADE DECISION] No trade placed - reasons:");
        if (empty($strategyResults)) {
            $this->logger->info("   • No strategy results available");
        } else {
            $this->logger->info("   • No strategies met minimum confidence threshold (70%)");
            $this->logger->info("   • Current price: {$currentPrice}");
            
            // Show what signals we did get
            foreach ($strategyResults as $result) {
                if ($result['success'] && $result['result']) {
                    $strategyResult = $result['result'];
                    $this->logger->info("   • {$result['strategy']}: {$strategyResult['action']} ({$strategyResult['confidence']}%)");
                }
            }
        }
        
        return false;
    }

    /**
     * Place a new futures trade based on strategy instructions
     */
    private function placeNewTrade(float $currentPrice, array $strategyResults): void
    {
        $this->logger->info("📈 Placing new futures trade...");
        
        // Get strategy-based parameters
        $strategyParams = $this->getStrategyParameters($strategyResults);
        
        // Strategy-based position sizing
        $positionSize = $this->calculateStrategyPositionSize($currentPrice, $strategyParams);
        if ($positionSize <= 0) {
            $this->logger->warning("⚠️ Insufficient balance for trade");
            return;
        }
        
        // Strategy-based stop loss and take profit
        $stopLoss = $this->calculateStrategyStopLoss($currentPrice, $strategyParams);
        $takeProfit = $this->calculateStrategyTakeProfit($currentPrice, $strategyParams);
        
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

    /**
     * Get strategy parameters from strategy results
     */
    private function getStrategyParameters(array $strategyResults): array
    {
        $params = [];
        
        foreach ($strategyResults as $result) {
            if ($result['success'] && $result['result']) {
                $strategyParams = $result['result']['parameters'] ?? [];
                $params = array_merge($params, $strategyParams);
            }
        }
        
        return $params;
    }

    /**
     * Calculate strategy-based position size
     */
    private function calculateStrategyPositionSize(float $currentPrice, array $strategyParams): float
    {
        $balance = $this->exchangeService->getBalance();
        $riskAmount = $balance * ($this->bot->risk_percentage / 100);
        
        // Use strategy-specific risk if available
        if (isset($strategyParams['risk_percentage'])) {
            $riskAmount = $balance * ($strategyParams['risk_percentage'] / 100);
        }
        
        $positionSize = $riskAmount / $currentPrice;
        
        // Apply max position size limit
        $positionSize = min($positionSize, $this->bot->max_position_size);
        
        $this->logger->info("💰 Strategy-based position size: {$positionSize}");
        
        return $positionSize;
    }

    /**
     * Calculate strategy-based stop loss
     */
    private function calculateStrategyStopLoss(float $currentPrice, array $strategyParams): float
    {
        $stopLossPercentage = $this->bot->stop_loss_percentage / 100;
        
        // Use strategy-specific stop loss if available
        if (isset($strategyParams['stop_loss_percentage'])) {
            $stopLossPercentage = $strategyParams['stop_loss_percentage'] / 100;
        }
        
        return $currentPrice * (1 - $stopLossPercentage);
    }

    /**
     * Calculate strategy-based take profit
     */
    private function calculateStrategyTakeProfit(float $currentPrice, array $strategyParams): float
    {
        $takeProfitPercentage = $this->bot->take_profit_percentage / 100;
        
        // Use strategy-specific take profit if available
        if (isset($strategyParams['take_profit_percentage'])) {
            $takeProfitPercentage = $strategyParams['take_profit_percentage'] / 100;
        }
        
        return $currentPrice * (1 + $takeProfitPercentage);
    }

    /**
     * Log detailed SMC analysis
     */
    private function logSMCAnalysis(array $smcAnalysis, float $currentPrice): void
    {
        $this->logger->info("🧠 [SMC ANALYSIS] Smart Money Concepts Details:");
        
        // Log price zones
        if (isset($smcAnalysis['price_zones'])) {
            $zones = $smcAnalysis['price_zones'];
            $this->logger->info("   📊 PRICE ZONES:");
            $this->logger->info("      • Discount Zone: {$zones['discount']['min']} - {$zones['discount']['max']}");
            $this->logger->info("      • Equilibrium Zone: {$zones['equilibrium']['min']} - {$zones['equilibrium']['max']}");
            $this->logger->info("      • Premium Zone: {$zones['premium']['min']} - {$zones['premium']['max']}");
        }
        
        // Log current price zone
        if (isset($smcAnalysis['current_zone'])) {
            $zone = $smcAnalysis['current_zone'];
            $this->logger->info("   🎯 CURRENT PRICE ZONE: {$zone['name']} ({$zone['percentage']}%)");
            $this->logger->info("      • Current Price: {$currentPrice}");
            $this->logger->info("      • Zone Range: {$zone['min']} - {$zone['max']}");
            $this->logger->info("      • Distance from zone center: {$zone['distance_from_center']}%");
        }
        
        // Log swing points
        if (isset($smcAnalysis['swing_points'])) {
            $swings = $smcAnalysis['swing_points'];
            $this->logger->info("   📈 SWING POINTS:");
            $this->logger->info("      • Swing High: {$swings['swing_high']}");
            $this->logger->info("      • Swing Low: {$swings['swing_low']}");
            $this->logger->info("      • Range Size: {$swings['range_size']} ({$swings['range_percentage']}%)");
        }
        
        // Log signal details
        if (isset($smcAnalysis['signal'])) {
            $signal = $smcAnalysis['signal'];
            $this->logger->info("   🚦 SIGNAL ANALYSIS:");
            $this->logger->info("      • Action: {$signal['action']}");
            $this->logger->info("      • Strength: {$signal['strength']}%");
            $this->logger->info("      • Reason: {$signal['reason']}");
            
            if (isset($signal['entry_price'])) {
                $this->logger->info("      • Suggested Entry: {$signal['entry_price']}");
            }
        }
        
        // Log trading conditions
        if (isset($smcAnalysis['conditions'])) {
            $conditions = $smcAnalysis['conditions'];
            $this->logger->info("   ⚙️ TRADING CONDITIONS:");
            $this->logger->info("      • Range Valid: " . ($conditions['range_valid'] ? 'Yes' : 'No'));
            $this->logger->info("      • Signal Strength: " . ($conditions['signal_strong'] ? 'Yes' : 'No'));
            $this->logger->info("      • Zone Proximity: " . ($conditions['zone_proximity'] ? 'Yes' : 'No'));
        }
        
        // Log why no trade if applicable
        if (isset($smcAnalysis['no_trade_reason'])) {
            $this->logger->info("   ❌ NO TRADE REASON: {$smcAnalysis['no_trade_reason']}");
        }
    }
}

