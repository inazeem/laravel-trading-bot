<?php

namespace App\Services;

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Models\FuturesSignal;
use Illuminate\Support\Facades\Log;
use App\Services\FuturesTradingBotLogger;
use Illuminate\Support\Facades\DB;

class FuturesTradingBotService
{
    private FuturesTradingBot $bot;
    private ExchangeService $exchangeService;
    private SmartMoneyConceptsService $smcService;
    private FuturesTradingBotLogger $logger;
    private array $timeframeIntervals = [
        '1m' => '1m',
        '5m' => '5m', 
        '15m' => '15m',
        '30m' => '30m',
        '1h' => '1h',
        '4h' => '4h',
        '1d' => '1d'
    ];

    /**
     * Get the correct interval format for the exchange
     */
    private function getExchangeInterval(string $timeframe): string
    {
        if ($this->bot->exchange === 'kucoin') {
            // KuCoin uses different interval formats
            $kucoinIntervals = [
                '1m' => '1minute',
                '5m' => '5minute',
                '15m' => '15minute',
                '30m' => '30minute',
                '1h' => '1hour',
                '4h' => '4hour',
                '1d' => '1day'
            ];
            return $kucoinIntervals[$timeframe] ?? $timeframe;
        }
        
        // Binance and other exchanges use standard formats
        return $this->timeframeIntervals[$timeframe] ?? $timeframe;
    }

    /**
     * Filter timeframes based on exchange support
     */
    private function getSupportedTimeframes(): array
    {
        // Both KuCoin and Binance support all timeframes
        return $this->bot->timeframes;
    }

    public function __construct(FuturesTradingBot $bot)
    {
        $this->bot = $bot->load('apiKey');
        $this->exchangeService = new ExchangeService($bot->apiKey);
        $this->logger = new FuturesTradingBotLogger($bot);
    }

    /**
     * Run the futures trading bot
     */
    public function run(): void
    {
        try {
            $this->logger->info("🚀 [FUTURES BOT START] Futures trading bot '{$this->bot->name}' starting execution");
            $this->logger->info("📊 [CONFIG] Symbol: {$this->bot->symbol}, Exchange: {$this->bot->exchange}");
            $this->logger->info("⚙️ [CONFIG] Risk: {$this->bot->risk_percentage}%, Max Position: {$this->bot->max_position_size}");
            $this->logger->info("⚙️ [CONFIG] Leverage: {$this->bot->leverage}x, Margin: {$this->bot->margin_type}");
            $this->logger->info("⏰ [CONFIG] Timeframes: " . implode(', ', $this->bot->timeframes));
            
            // Update bot status
            $this->bot->update(['status' => 'running', 'last_run_at' => now()]);
            
            // Get current price
            $this->logger->info("💰 [PRICE] Fetching current price for {$this->bot->symbol}...");
            $currentPrice = $this->exchangeService->getCurrentPrice($this->bot->symbol);
            if (!$currentPrice) {
                $this->logger->error("❌ [PRICE] Failed to get current price for {$this->bot->symbol}");
                return;
            }
            $this->logger->info("✅ [PRICE] Current price: $currentPrice");
            
            // Analyze all timeframes
            $this->logger->info("🔍 [ANALYSIS] Starting Smart Money Concepts analysis for futures...");
            $signals = $this->analyzeAllTimeframes($currentPrice);
            
            // Process signals
            $this->logger->info("📈 [SIGNALS] Processing " . count($signals) . " total signals...");
            $this->processSignals($signals, $currentPrice);
            
            // Update existing positions
            $this->updateExistingPositions($currentPrice);
            
            // Update bot status
            $this->bot->update(['status' => 'idle']);
            
            $this->logger->info("✅ [FUTURES BOT END] Futures trading bot '{$this->bot->name}' completed successfully");
            
        } catch (\Exception $e) {
            $this->logger->error("❌ [ERROR] Error running futures trading bot {$this->bot->name}: " . $e->getMessage());
            $this->logger->error("🔍 [STACK] " . $e->getTraceAsString());
            $this->bot->update(['status' => 'error']);
        }
    }

    /**
     * Analyze all configured timeframes for futures
     */
    private function analyzeAllTimeframes(float $currentPrice): array
    {
        $allSignals = [];
        
        $supportedTimeframes = $this->getSupportedTimeframes();
        
        if (empty($supportedTimeframes)) {
            $this->logger->warning("⚠️ [TIMEFRAMES] No supported timeframes found for {$this->bot->exchange}. Available timeframes: " . implode(', ', $this->bot->timeframes));
            return $allSignals;
        }
        
        $this->logger->info("📊 [TIMEFRAMES] Analyzing " . count($supportedTimeframes) . " supported timeframes for futures...");
        
        foreach ($supportedTimeframes as $timeframe) {
            $interval = $this->getExchangeInterval($timeframe);
            
            $this->logger->info("⏰ [TIMEFRAME] Processing {$timeframe} timeframe (interval: {$interval})...");
            
            // Get candlestick data
            $this->logger->info("📈 [CANDLES] Fetching 500 candlesticks for {$this->bot->symbol} on {$timeframe}...");
            $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, 500);
            if (empty($candles)) {
                $this->logger->warning("⚠️ [CANDLES] No candle data received for {$timeframe} timeframe");
                continue;
            }
            
            $this->logger->info("✅ [CANDLES] Received " . count($candles) . " candlesticks for {$timeframe}");
            
            // Initialize Smart Money Concepts service
            $this->logger->info("🧠 [SMC] Initializing Smart Money Concepts analysis for {$timeframe}...");
            $this->smcService = new SmartMoneyConceptsService($candles);
            
            // Generate signals for this timeframe
            $this->logger->info("🔍 [SIGNALS] Generating signals for {$timeframe} timeframe...");
            $signals = $this->smcService->generateSignals($currentPrice);
            
            foreach ($signals as $signal) {
                $signal['timeframe'] = $timeframe;
                $allSignals[] = $signal;
            }
            
            $this->logger->info("📊 [SIGNALS] Generated " . count($signals) . " signals for {$timeframe} timeframe");
            
            // Log signal details
            foreach ($signals as $index => $signal) {
                $price = $signal['price'] ?? $signal['level'] ?? 'N/A';
                $this->logger->info("📋 [SIGNAL {$index}] Type: {$signal['type']}, Direction: {$signal['direction']}, Strength: {$signal['strength']}, Price: {$price}");
            }
        }
        
        Log::info("🎯 [SUMMARY] Total signals generated across all timeframes: " . count($allSignals));
        return $allSignals;
    }

    /**
     * Process trading signals for futures
     */
    private function processSignals(array $signals, float $currentPrice): void
    {
        if (empty($signals)) {
            Log::info("📭 [SIGNALS] No trading signals generated - no action needed");
            return;
        }
        
        Log::info("🔍 [FILTER] Filtering and ranking " . count($signals) . " signals...");
        
        // Filter and rank signals
        $filteredSignals = $this->filterSignals($signals);
        
        Log::info("✅ [FILTER] " . count($filteredSignals) . " signals passed filtering criteria");
        
        foreach ($filteredSignals as $index => $signal) {
            Log::info("🎯 [PROCESS] Processing signal " . ($index + 1) . " of " . count($filteredSignals));
            $this->processSignal($signal, $currentPrice);
        }
    }

    /**
     * Filter and rank signals based on strength and confluence
     */
    private function filterSignals(array $signals): array
    {
        $filtered = [];
        
        foreach ($signals as $signal) {
            // Minimum strength threshold
            if (($signal['strength'] ?? 0) < 0.5) {
                continue;
            }
            
            // Check for signal confluence across timeframes
            $confluence = $this->calculateSignalConfluence($signal, $signals);
            
            // If only one timeframe is configured, accept signals with good strength
            if (count($this->bot->timeframes) === 1) {
                if (($signal['strength'] ?? 0) >= 0.5) {
                    $signal['confluence'] = 1; // Single timeframe confluence
                    $filtered[] = $signal;
                }
            } else {
                // Multiple timeframes: require confluence
                if ($confluence >= 1) { // At least 1 other timeframe showing same signal
                    $signal['confluence'] = $confluence;
                    $filtered[] = $signal;
                }
            }
        }
        
        // Sort by confluence and strength
        usort($filtered, function($a, $b) {
            $scoreA = ($a['confluence'] * 10) + ($a['strength'] ?? 0);
            $scoreB = ($b['confluence'] * 10) + ($b['strength'] ?? 0);
            return $scoreB <=> $scoreA;
        });
        
        return $filtered;
    }

    /**
     * Calculate signal confluence across timeframes
     */
    private function calculateSignalConfluence(array $signal, array $allSignals): int
    {
        $confluence = 0;
        
        foreach ($allSignals as $otherSignal) {
            if ($otherSignal['type'] === $signal['type'] && 
                $otherSignal['direction'] === $signal['direction'] &&
                $otherSignal['timeframe'] !== $signal['timeframe']) {
                $confluence++;
            }
        }
        
        return $confluence;
    }

    /**
     * Process individual signal for futures
     */
    private function processSignal(array $signal, float $currentPrice): void
    {
        // Check if we already have an open position
        $openTrade = $this->getOpenTrade();
        
        if ($openTrade) {
            $this->handleExistingPosition($openTrade, $signal, $currentPrice);
        } else {
            $this->handleNewSignal($signal, $currentPrice);
        }
    }

    /**
     * Handle new trading signal for futures
     */
    private function handleNewSignal(array $signal, float $currentPrice): void
    {
        // Check position side restrictions
        if (!$this->canTakePosition($signal['direction'])) {
            $this->logger->info("🚫 [RESTRICTION] Cannot take {$signal['direction']} position due to bot configuration");
            return;
        }

        // Calculate position size
        $positionSize = $this->calculatePositionSize($currentPrice);
        
        if ($positionSize <= 0) {
            $this->logger->warning("Insufficient balance for futures trade - Position size calculated as: {$positionSize}");
            return;
        }
        
        // Calculate stop loss and take profit
        $stopLoss = $this->calculateStopLoss($signal, $currentPrice);
        $takeProfit = $this->calculateTakeProfit($signal, $currentPrice);
        
        // Validate risk/reward ratio
        $riskRewardRatio = $this->calculateRiskRewardRatio($currentPrice, $stopLoss, $takeProfit);
        if ($riskRewardRatio < 1.5) {
            $this->logger->info("Risk/reward ratio too low: {$riskRewardRatio}");
            return;
        }
        
        // Place the futures order
        $order = $this->placeFuturesOrder($signal, $positionSize);
        
        if ($order) {
            // Save trade to database
            $this->saveFuturesTrade($signal, $order, $currentPrice, $stopLoss, $takeProfit);
            
            // Save signal
            $this->saveFuturesSignal($signal, $currentPrice, $stopLoss, $takeProfit, $riskRewardRatio);
            
            $this->logger->info("Futures order placed successfully: {$order['order_id']}");
        }
    }

    /**
     * Handle existing position for futures
     */
    private function handleExistingPosition(FuturesTrade $trade, array $signal, float $currentPrice): void
    {
        // Check if we should close the position
        $shouldClose = $this->shouldClosePosition($trade, $signal, $currentPrice);
        
        if ($shouldClose) {
            $this->closePosition($trade, $currentPrice);
        }
    }

    /**
     * Check if bot can take position in given direction
     */
    private function canTakePosition(string $direction): bool
    {
        if ($this->bot->position_side === 'both') {
            return true;
        }
        
        return $this->bot->position_side === $direction;
    }

    /**
     * Calculate position size based on risk management for futures
     */
    private function calculatePositionSize(float $currentPrice): float
    {
        $balance = $this->exchangeService->getFuturesBalance();
        $usdtBalance = 0;
        
        foreach ($balance as $bal) {
            if ($bal['currency'] === 'USDT') {
                $usdtBalance = $bal['available'];
                break;
            }
        }
        
        $this->logger->info("Balance calculation: USDT Balance = {$usdtBalance}, Current Price = {$currentPrice}");
        
        if ($usdtBalance <= 0) {
            $this->logger->warning("No USDT balance available for futures");
            return 0;
        }
        
        // Calculate position size based on risk percentage and leverage
        $riskAmount = $usdtBalance * ($this->bot->risk_percentage / 100);
        $positionValue = $riskAmount * $this->bot->leverage;
        $positionSize = $positionValue / $currentPrice;
        
        $this->logger->info("Position size calculation: Risk Amount = {$riskAmount} USDT, Leverage = {$this->bot->leverage}x, Position Size = {$positionSize}");
        
        // Apply maximum position size limit
        $maxPositionSize = $this->bot->max_position_size;
        if ($positionSize > $maxPositionSize) {
            $positionSize = $maxPositionSize;
            $this->logger->info("Position size limited by max position size: {$positionSize}");
        }
        
        return $positionSize;
    }

    /**
     * Calculate stop loss for futures
     */
    private function calculateStopLoss(array $signal, float $currentPrice): float
    {
        $stopLossPercentage = $this->bot->stop_loss_percentage / 100;
        
        if ($signal['direction'] === 'long') {
            return $currentPrice * (1 - $stopLossPercentage);
        } else {
            return $currentPrice * (1 + $stopLossPercentage);
        }
    }

    /**
     * Calculate take profit for futures
     */
    private function calculateTakeProfit(array $signal, float $currentPrice): float
    {
        $takeProfitPercentage = $this->bot->take_profit_percentage / 100;
        
        if ($signal['direction'] === 'long') {
            return $currentPrice * (1 + $takeProfitPercentage);
        } else {
            return $currentPrice * (1 - $takeProfitPercentage);
        }
    }

    /**
     * Calculate risk/reward ratio
     */
    private function calculateRiskRewardRatio(float $currentPrice, float $stopLoss, float $takeProfit): float
    {
        $risk = abs($currentPrice - $stopLoss);
        $reward = abs($takeProfit - $currentPrice);
        
        if ($risk == 0) {
            return 0;
        }
        
        return $reward / $risk;
    }

    /**
     * Place futures order
     */
    private function placeFuturesOrder(array $signal, float $positionSize): ?array
    {
        try {
            $side = $signal['direction'] === 'long' ? 'buy' : 'sell';
            
            $orderResult = $this->exchangeService->placeFuturesOrder(
                $this->bot->symbol,
                $side,
                $positionSize,
                $this->bot->leverage,
                $this->bot->margin_type
            );
            
            return $orderResult;
        } catch (\Exception $e) {
            $this->logger->error("Failed to place futures order: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Save futures trade to database
     */
    private function saveFuturesTrade(array $signal, array $order, float $currentPrice, float $stopLoss, float $takeProfit): void
    {
        // Map signal direction to trade side
        $tradeSide = $signal['direction'] === 'bullish' ? 'long' : 'short';
        
        $trade = FuturesTrade::create([
            'futures_trading_bot_id' => $this->bot->id,
            'symbol' => $this->bot->symbol,
            'side' => $tradeSide,
            'quantity' => $order['quantity'] ?? 0,
            'entry_price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'leverage' => $this->bot->leverage,
            'margin_type' => $this->bot->margin_type,
            'status' => 'open',
            'order_id' => $order['order_id'] ?? null,
            'exchange_response' => $order,
            'opened_at' => now(),
        ]);
        
        $this->logger->info("Futures trade saved: ID {$trade->id}");
    }

    /**
     * Save futures signal to database
     */
    private function saveFuturesSignal(array $signal, float $currentPrice, float $stopLoss, float $takeProfit, float $riskRewardRatio): void
    {
        FuturesSignal::create([
            'futures_trading_bot_id' => $this->bot->id,
            'symbol' => $this->bot->symbol,
            'timeframe' => $signal['timeframe'],
            'direction' => $signal['direction'],
            'signal_type' => $signal['type'],
            'strength' => $signal['strength'] ?? 0,
            'price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'risk_reward_ratio' => $riskRewardRatio,
            'signal_data' => $signal,
            'executed' => true,
        ]);
    }

    /**
     * Get open trade for this bot
     */
    private function getOpenTrade(): ?FuturesTrade
    {
        return $this->bot->openTrades()->first();
    }

    /**
     * Check if position should be closed
     */
    private function shouldClosePosition(FuturesTrade $trade, array $signal, float $currentPrice): bool
    {
        // Check stop loss
        if ($trade->isLong() && $currentPrice <= $trade->stop_loss) {
            $this->logger->info("Stop loss triggered for long position at {$currentPrice}");
            return true;
        }
        
        if ($trade->isShort() && $currentPrice >= $trade->stop_loss) {
            $this->logger->info("Stop loss triggered for short position at {$currentPrice}");
            return true;
        }
        
        // Check take profit
        if ($trade->isLong() && $currentPrice >= $trade->take_profit) {
            $this->logger->info("Take profit triggered for long position at {$currentPrice}");
            return true;
        }
        
        if ($trade->isShort() && $currentPrice <= $trade->take_profit) {
            $this->logger->info("Take profit triggered for short position at {$currentPrice}");
            return true;
        }
        
        // Check for opposite signal
        if ($signal['direction'] !== $trade->side && ($signal['strength'] ?? 0) > 0.7) {
            $this->logger->info("Opposite signal detected, closing position");
            return true;
        }
        
        return false;
    }

    /**
     * Close position
     */
    public function closePosition(FuturesTrade $trade, float $currentPrice): void
    {
        try {
            $side = $trade->isLong() ? 'sell' : 'buy';
            
            $orderResult = $this->exchangeService->closeFuturesPosition(
                $trade->symbol,
                $side,
                $trade->quantity,
                $trade->order_id
            );
            
            if ($orderResult) {
                $realizedPnL = $trade->calculateUnrealizedPnL($currentPrice);
                $pnlPercentage = $trade->calculatePnLPercentage();
                
                $trade->update([
                    'exit_price' => $currentPrice,
                    'realized_pnl' => $realizedPnL,
                    'pnl_percentage' => $pnlPercentage,
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
                
                $this->logger->info("Position closed: PnL = {$realizedPnL}, PnL% = {$pnlPercentage}%");
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to close position: " . $e->getMessage());
        }
    }

    /**
     * Update existing positions with current PnL
     */
    private function updateExistingPositions(float $currentPrice): void
    {
        $openTrades = $this->bot->openTrades()->get();
        
        foreach ($openTrades as $trade) {
            $unrealizedPnL = $trade->calculateUnrealizedPnL($currentPrice);
            $pnlPercentage = $trade->calculatePnLPercentage();
            
            $trade->update([
                'unrealized_pnl' => $unrealizedPnL,
                'pnl_percentage' => $pnlPercentage,
            ]);
        }
    }
}
