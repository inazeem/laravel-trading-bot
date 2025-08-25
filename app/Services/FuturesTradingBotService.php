<?php

namespace App\Services;

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Models\FuturesSignal;
use Illuminate\Support\Facades\Log;
use App\Services\FuturesTradingBotLogger;
use App\Services\SmartMoneyConceptsService;
use App\Services\BitcoinCorrelationService;
use App\Services\TradingLearningService;
use Illuminate\Support\Facades\DB;

class FuturesTradingBotService
{
    private FuturesTradingBot $bot;
    private ExchangeService $exchangeService;
    private SmartMoneyConceptsService $smcService;
    private BitcoinCorrelationService $btcCorrelationService;
    private TradingLearningService $learningService;
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
        $this->btcCorrelationService = new BitcoinCorrelationService($this->exchangeService);
        $this->learningService = new TradingLearningService($bot);
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
            
            // Sync positions with exchange before processing
            $this->syncPositionsWithExchange();
            
            // Learn from trading history and apply improvements
            $this->learnFromTradingHistory();
            
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
            
            // Final sync to ensure database is up to date
            $this->finalPositionSync();
            
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
     * Analyze all configured timeframes
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
            
            // Get candlestick data - optimized for micro trading
            $candleLimit = $this->getOptimalCandleLimit($timeframe);
            $this->logger->info("📈 [CANDLES] Fetching {$candleLimit} candlesticks for {$this->bot->symbol} on {$timeframe}...");
            $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, $candleLimit);
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
     * Get optimal candle limit based on timeframe for micro trading
     */
    private function getOptimalCandleLimit(string $timeframe): int
    {
        // Use configuration for micro trading optimization
        $limits = config('micro_trading.candle_limits', []);
        
        return $limits[$timeframe] ?? 100; // Default fallback
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
        
        // CRITICAL FIX: Check for existing open position BEFORE processing signals
        $existingOpenTrade = $this->getOpenTrade();
        if ($existingOpenTrade) {
            Log::info("🚫 [MULTIPLE TRADES PREVENTION] Found existing open position - skipping new signal processing");
            Log::info("📊 [EXISTING POSITION] Trade ID: {$existingOpenTrade->id}, Side: {$existingOpenTrade->side}, Status: {$existingOpenTrade->status}");
            
            // Only handle existing position monitoring, don't process new signals
            $this->handleExistingPosition($existingOpenTrade, $signals[0], $currentPrice);
            return;
        }
        
        // Filter and rank signals
        $filteredSignals = $this->filterSignals($signals);
        
        Log::info("✅ [FILTER] " . count($filteredSignals) . " signals passed filtering criteria");
        
        // Only process the best signal to avoid conflicting actions
        if (!empty($filteredSignals)) {
            $bestSignal = $filteredSignals[0];
            Log::info("🎯 [BEST SIGNAL] Processing best signal: Type: {$bestSignal['type']}, Direction: {$bestSignal['direction']}, Strength: {$bestSignal['strength']}, Confluence: {$bestSignal['confluence']}");
            
            // CRITICAL FIX: Double-check no open position exists before placing trade
            $doubleCheckOpenTrade = $this->getOpenTrade();
            if ($doubleCheckOpenTrade) {
                Log::warning("🚨 [SAFETY CHECK] Open position detected during signal processing - aborting new trade");
                return;
            }
            
            // Check if it's a good time for new trade (micro trading optimized)
            if ($this->isGoodTimeForNewTrade()) {
                $this->processSignal($bestSignal, $currentPrice);
            } else {
                Log::info("⏰ [TIMING] Skipping signal - not a good time for new trade");
            }
        }
    }

    /**
     * Filter and rank signals based on strength and confluence - HIGH STRENGTH REQUIREMENT
     */
    private function filterSignals(array $signals): array
    {
        $filtered = [];
        $this->logger->info("🔍 [FILTER] Starting to filter " . count($signals) . " signals with HIGH STRENGTH requirement...");
        
        foreach ($signals as $index => $signal) {
            $this->logger->info("🔍 [FILTER] Processing signal {$index}: " . json_encode($signal));
            
            // CRITICAL REQUIREMENT: Only accept signals with strength above 90%
            $requiredStrength = config('micro_trading.signal_settings.high_strength_requirement', 0.90); // 90% strength requirement
            $signalStrength = $signal['strength'] ?? 0;
            
            if ($signalStrength < $requiredStrength) {
                $this->logger->info("❌ [FILTER] Signal {$index} rejected - strength too low: {$signalStrength} (required: {$requiredStrength} = " . ($requiredStrength * 100) . "%)");
                continue;
            }
            
            $this->logger->info("✅ [FILTER] Signal {$index} passed HIGH STRENGTH requirement: {$signalStrength} >= {$requiredStrength} (" . ($requiredStrength * 100) . "%)");
            
            // Check for signal confluence across timeframes
            $confluence = $this->calculateSignalConfluence($signal, $signals);
            $this->logger->info("🔗 [FILTER] Signal {$index} confluence: {$confluence}");
            
            // For high-strength signals (90%+), we can be more flexible with confluence
            $minConfluence = 1; // Minimum confluence requirement for 90%+ strength signals
            
            if ($confluence >= $minConfluence) {
                $signal['confluence'] = $confluence;
                $filtered[] = $signal;
                $this->logger->info("✅ [FILTER] Signal {$index} accepted (high strength: {$signalStrength}, confluence: {$confluence})");
            } else {
                // Even for 90%+ strength, we still need some confluence
                $this->logger->info("❌ [FILTER] Signal {$index} rejected - insufficient confluence: {$confluence} (minimum: {$minConfluence})");
            }
        }
        
        $this->logger->info("📊 [FILTER] Filtering complete: " . count($filtered) . " signals passed HIGH STRENGTH requirement");
        
        if (empty($filtered)) {
            $this->logger->info("⚠️ [FILTER] No signals met the " . ($requiredStrength * 100) . "% strength requirement - no trades will be placed");
        } else {
            $this->logger->info("🎯 [FILTER] Found " . count($filtered) . " signals with " . ($requiredStrength * 100) . "%+ strength - proceeding with trade evaluation");
        }
        
        // Sort by SMC priority: OrderBlock_Support/Resistance > Breakout > BOS/CHoCH
        usort($filtered, function($a, $b) {
            // Priority weights for SMC signals
            $priorityWeights = [
                'OrderBlock_Support' => 100,
                'OrderBlock_Resistance' => 100,
                'OrderBlock_Breakout' => 80,
                'BOS' => 60,
                'CHoCH' => 60,
            ];
            
            $priorityA = $priorityWeights[$a['type']] ?? 50;
            $priorityB = $priorityWeights[$b['type']] ?? 50;
            
            // Calculate final score: (priority * 10) + (confluence * 5) + strength
            $scoreA = ($priorityA * 10) + ($a['confluence'] * 5) + ($a['strength'] ?? 0);
            $scoreB = ($priorityB * 10) + ($b['confluence'] * 5) + ($b['strength'] ?? 0);
            
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
        $this->logger->info("🔄 [PROCESS SIGNAL] Processing signal: " . json_encode($signal));
        
        // Check if we already have an open position
        $openTrade = $this->getOpenTrade();
        
        if ($openTrade) {
            $this->logger->info("📊 [EXISTING POSITION] Found open trade: " . json_encode($openTrade->toArray()));
            $this->handleExistingPosition($openTrade, $signal, $currentPrice);
        } else {
            $this->logger->info("🆕 [NO OPEN POSITION] No open trade found - handling new signal");
            $this->handleNewSignal($signal, $currentPrice);
        }
    }

    /**
     * Handle new trading signal for futures
     */
    private function handleNewSignal(array $signal, float $currentPrice): void
    {
        $this->logger->info("🚀 [NEW SIGNAL] Starting to process new signal: " . json_encode($signal));
        
        // Check if we're in cooldown period after closing a position
        if ($this->isInCooldownPeriod()) {
            $this->logger->info("⏰ [COOLDOWN] Skipping new signal - bot is in cooldown period after recent position closure");
            return;
        }
        
        $this->logger->info("✅ [COOLDOWN] Not in cooldown period - proceeding");
        
        // Check position side restrictions
        if (!$this->canTakePosition($signal['direction'])) {
            $this->logger->info("🚫 [RESTRICTION] Cannot take {$signal['direction']} position due to bot configuration");
            return;
        }
        
        $this->logger->info("✅ [RESTRICTION] Position side check passed - proceeding");
        
        // Check Bitcoin correlation if enabled and not trading BTC itself
        if ($this->bot->enable_bitcoin_correlation && $this->bot->symbol !== 'BTC-USDT') {
            $this->logger->info("🔗 [BTC CORRELATION] Checking Bitcoin correlation for {$signal['direction']} signal...");
            
            $recommendation = $this->btcCorrelationService->getCorrelationRecommendation($signal, $signal['timeframe']);
            
            $this->logger->info("🔗 [BTC CORRELATION] BTC Sentiment: {$recommendation['btc_sentiment']}, Recommendation: {$recommendation['reason']}");
            
            if (!$recommendation['should_trade']) {
                $this->logger->info("🚫 [BTC CORRELATION] Skipping trade - {$recommendation['reason']}");
                return;
            }
            
            $this->logger->info("✅ [BTC CORRELATION] Bitcoin correlation check passed - proceeding with trade");
        } else {
            $this->logger->info("✅ [BTC CORRELATION] Bitcoin correlation check skipped (disabled or BTC trading)");
        }

        // Close any existing position first
        $this->closeExistingPosition();

        // Calculate position size
        $positionSize = $this->calculatePositionSize($currentPrice);
        
        $this->logger->info("💰 [POSITION SIZE] Calculated position size: {$positionSize}");
        
        if ($positionSize <= 0) {
            $this->logger->warning("❌ [POSITION SIZE] Insufficient balance for futures trade - Position size calculated as: {$positionSize}");
            return;
        }
        
        $this->logger->info("✅ [POSITION SIZE] Position size check passed");
        
        // Calculate stop loss and take profit
        $stopLoss = $this->calculateStopLoss($signal, $currentPrice);
        $takeProfit = $this->calculateTakeProfit($signal, $currentPrice);
        
        $this->logger->info("🎯 [RISK MANAGEMENT] Calculated Stop Loss: {$stopLoss}, Take Profit: {$takeProfit}");
        
        // Validate risk/reward ratio
        $riskRewardRatio = $this->calculateRiskRewardRatio($currentPrice, $stopLoss, $takeProfit);
        $this->logger->info("📊 [RISK/REWARD] Calculated ratio: {$riskRewardRatio}");
        
        // Use bot's minimum risk/reward ratio configuration
        $minRiskReward = $this->bot->min_risk_reward_ratio;
        
        if ($riskRewardRatio < $minRiskReward) {
            $this->logger->info("❌ [RISK/REWARD] Risk/reward ratio too low: {$riskRewardRatio} (minimum: {$minRiskReward}) - skipping trade");
            return;
        }
        
        $this->logger->info("✅ [RISK/REWARD] Risk/reward ratio check passed");
        
        // Place the futures order with stop loss and take profit
        $this->logger->info("📤 [ORDER] Attempting to place futures order...");
        $order = $this->placeFuturesOrder($signal, $positionSize, $stopLoss, $takeProfit);
        
        if ($order) {
            $this->logger->info("✅ [ORDER] Futures order placed successfully: " . json_encode($order));
            
            // Save trade to database
            $this->logger->info("💾 [DATABASE] Saving trade to database...");
            $this->saveFuturesTrade($signal, $order, $currentPrice, $stopLoss, $takeProfit);
            
            // Save signal
            $this->logger->info("💾 [DATABASE] Saving signal to database...");
            $this->saveFuturesSignal($signal, $currentPrice, $stopLoss, $takeProfit, $riskRewardRatio);
            
            $this->logger->info("🎉 [SUCCESS] Complete trade process finished successfully");
        } else {
            $this->logger->error("❌ [ORDER] Failed to place futures order");
        }
    }

    /**
     * Handle existing position for futures - IMPROVED VERSION
     */
    private function handleExistingPosition(FuturesTrade $trade, array $signal, float $currentPrice): void
    {
        // Log current position status
        $unrealizedPnL = $trade->calculateUnrealizedPnL($currentPrice);
        $pnlPercentage = $trade->calculatePnLPercentage();
        
        // CRITICAL FIX: Use persistent PnL if available
        $persistentPnL = $this->getPersistentPnL($trade->id);
        if ($persistentPnL !== null) {
            $unrealizedPnL = $persistentPnL;
            $this->logger->info("💾 [PERSISTENT PNL] Using persistent PnL: {$unrealizedPnL}");
        }
        
        $this->logger->info("📊 [POSITION] Monitoring existing {$trade->side} position:");
        $this->logger->info("   Entry Price: {$trade->entry_price}");
        $this->logger->info("   Current Price: {$currentPrice}");
        $this->logger->info("   Stop Loss: {$trade->stop_loss}");
        $this->logger->info("   Take Profit: {$trade->take_profit}");
        $this->logger->info("   Unrealized PnL: {$unrealizedPnL}");
        $this->logger->info("   PnL %: {$pnlPercentage}%");
        
        // CRITICAL FIX: Save current PnL to persistent storage
        $this->savePersistentPnL($trade->id, $unrealizedPnL);
        
        // Update trade with current PnL
        $trade->update([
            'unrealized_pnl' => $unrealizedPnL,
            'pnl_percentage' => $pnlPercentage,
        ]);
        
        // Check if we should close the position
        $shouldClose = $this->shouldClosePosition($trade, $signal, $currentPrice);
        
        if ($shouldClose) {
            $this->logger->info("🔴 [CLOSE] Position closing conditions met - closing position");
            $this->closePosition($trade, $currentPrice);
        } else {
            $this->logger->info("✅ [HOLD] Position conditions stable - continuing to monitor");
            
            // CRITICAL FIX: Don't process new signals when we have an existing position
            $this->logger->info("🚫 [MULTIPLE TRADES PREVENTION] Skipping new signal processing - position already open");
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
        
        // Map signal direction to position side for comparison
        $signalPositionSide = ($direction === 'bullish' || $direction === 'long') ? 'long' : 'short';
        
        return $this->bot->position_side === $signalPositionSide;
    }

    /**
     * Calculate position size based on risk management for futures
     */
    private function calculatePositionSize(float $currentPrice): float
    {
        $balance = $this->exchangeService->getFuturesBalance();
        $usdtBalance = 0;
        
        foreach ($balance as $bal) {
            // Handle both Binance (asset) and KuCoin (currency) formats
            $currency = $bal['currency'] ?? $bal['asset'] ?? null;
            $available = $bal['available'] ?? $bal['free'] ?? 0;
            
            if ($currency === 'USDT' && $available > 0) {
                $usdtBalance = (float) $available;
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
        
        // Ensure minimum notional value from bot configuration
        $minNotionalValue = $this->bot->min_order_value + 0.5; // Add small buffer for rounding
        $currentNotional = $positionSize * $currentPrice;
        
        if ($currentNotional < $minNotionalValue) {
            $minPositionSize = ceil(($minNotionalValue / $currentPrice) * 10) / 10; // Round up to nearest 0.1
            $this->logger->info("Position notional below minimum ({$currentNotional} USDT), adjusting position size from {$positionSize} to {$minPositionSize} (min order value: {$this->bot->min_order_value} USDT)");
            $positionSize = $minPositionSize;
        }
        
        return $positionSize;
    }

    /**
     * Calculate stop loss for futures using SMC levels
     */
    private function calculateStopLoss(array $signal, float $currentPrice): float
    {
        // Get SMC levels for better stop loss placement
        $smcLevels = $this->getSMCLevels();
        
        if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
            // For long positions, find the nearest support level below current price
            $supportLevels = array_filter($smcLevels, function($level) use ($currentPrice) {
                return $level['type'] === 'support' && $level['price'] < $currentPrice;
            });
            
            if (!empty($supportLevels)) {
                // Sort by price (highest first) and take the closest support
                usort($supportLevels, function($a, $b) {
                    return $b['price'] <=> $a['price'];
                });
                $nearestSupport = $supportLevels[0]['price'];
                
                // Add a small buffer below the support level
                $stopLoss = $nearestSupport * 0.995; // 0.5% below support
                
                $this->logger->info("SMC Stop Loss for long: Using support level at {$nearestSupport}, stop loss set at {$stopLoss}");
                return $stopLoss;
            }
        } else {
            // For short positions, find the nearest resistance level above current price
            $resistanceLevels = array_filter($smcLevels, function($level) use ($currentPrice) {
                return $level['type'] === 'resistance' && $level['price'] > $currentPrice;
            });
            
            if (!empty($resistanceLevels)) {
                // Sort by price (lowest first) and take the closest resistance
                usort($resistanceLevels, function($a, $b) {
                    return $a['price'] <=> $b['price'];
                });
                $nearestResistance = $resistanceLevels[0]['price'];
                
                // Add a small buffer above the resistance level
                $stopLoss = $nearestResistance * 1.005; // 0.5% above resistance
                
                $this->logger->info("SMC Stop Loss for short: Using resistance level at {$nearestResistance}, stop loss set at {$stopLoss}");
                return $stopLoss;
            }
        }
        
        // Fallback to percentage-based stop loss if no SMC levels found
        $stopLossPercentage = $this->bot->stop_loss_percentage / 100;
        
        if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
            $fallbackStopLoss = $currentPrice * (1 - $stopLossPercentage);
            $this->logger->info("Using fallback percentage stop loss: {$fallbackStopLoss}");
            return $fallbackStopLoss;
        } else {
            $fallbackStopLoss = $currentPrice * (1 + $stopLossPercentage);
            $this->logger->info("Using fallback percentage stop loss: {$fallbackStopLoss}");
            return $fallbackStopLoss;
        }
    }

    /**
     * Calculate take profit for futures using SMC levels
     */
    private function calculateTakeProfit(array $signal, float $currentPrice): float
    {
        // Get SMC levels for better take profit placement
        $smcLevels = $this->getSMCLevels();
        
        if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
            // For long positions, find the nearest resistance level above current price
            $resistanceLevels = array_filter($smcLevels, function($level) use ($currentPrice) {
                return $level['type'] === 'resistance' && $level['price'] > $currentPrice;
            });
            
            if (!empty($resistanceLevels)) {
                // Sort by price (lowest first) and take the closest resistance
                usort($resistanceLevels, function($a, $b) {
                    return $a['price'] <=> $b['price'];
                });
                $nearestResistance = $resistanceLevels[0]['price'];
                
                // Check if the resistance level provides a reasonable reward (at least 0.5% from current price)
                $minRewardDistance = $currentPrice * 0.005; // 0.5% minimum reward
                if (($nearestResistance - $currentPrice) >= $minRewardDistance) {
                    $this->logger->info("SMC Take Profit for long: Using resistance level at {$nearestResistance}");
                    return $nearestResistance;
                } else {
                    $this->logger->info("SMC resistance level too close ({$nearestResistance}), using percentage-based take profit");
                }
            }
        } else {
            // For short positions, find the nearest support level below current price
            $supportLevels = array_filter($smcLevels, function($level) use ($currentPrice) {
                return $level['type'] === 'support' && $level['price'] < $currentPrice;
            });
            
            if (!empty($supportLevels)) {
                // Sort by price (highest first) and take the closest support
                usort($supportLevels, function($a, $b) {
                    return $b['price'] <=> $a['price'];
                });
                $nearestSupport = $supportLevels[0]['price'];
                
                // Check if the support level provides a reasonable reward (at least 0.5% from current price)
                $minRewardDistance = $currentPrice * 0.005; // 0.5% minimum reward
                if (($currentPrice - $nearestSupport) >= $minRewardDistance) {
                    $this->logger->info("SMC Take Profit for short: Using support level at {$nearestSupport}");
                    return $nearestSupport;
                } else {
                    $this->logger->info("SMC support level too close ({$nearestSupport}), using percentage-based take profit");
                }
            }
        }
        
        // Fallback to percentage-based take profit if no SMC levels found
        $takeProfitPercentage = $this->bot->take_profit_percentage / 100;
        
        if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
            $fallbackTakeProfit = $currentPrice * (1 + $takeProfitPercentage);
            $this->logger->info("Using fallback percentage take profit: {$fallbackTakeProfit}");
            return $fallbackTakeProfit;
        } else {
            $fallbackTakeProfit = $currentPrice * (1 - $takeProfitPercentage);
            $this->logger->info("Using fallback percentage take profit: {$fallbackTakeProfit}");
            return $fallbackTakeProfit;
        }
    }

    /**
     * Get SMC levels for stop loss and take profit calculation
     */
    private function getSMCLevels(): array
    {
        $levels = [];
        
        // Get candlestick data for SMC analysis - optimized for micro trading
        $timeframe = $this->bot->timeframes[0];
        $candleLimit = $this->getOptimalCandleLimit($timeframe);
        $candles = $this->exchangeService->getCandles($this->bot->symbol, $timeframe, $candleLimit);
        
        if (empty($candles)) {
            $this->logger->warning("No candlestick data available for SMC analysis");
            return $levels;
        }
        
        // Create SMC service instance
        $smcService = new SmartMoneyConceptsService($candles);
        
        // Get all SMC levels
        $levels = $smcService->getSupportResistanceLevels();
        
        $this->logger->info("Retrieved " . count($levels) . " SMC levels for analysis using {$candleLimit} candles");
        
        return $levels;
    }

    /**
     * Close any existing position before placing new one
     */
    private function closeExistingPosition(): void
    {
        $openTrade = $this->getOpenTrade();
        
        if ($openTrade) {
            $this->logger->info("🔄 [CLOSE EXISTING] Found open position - closing before new trade");
            
            // Get current price
            $currentPrice = $this->exchangeService->getCurrentPrice($this->bot->symbol);
            
            if ($currentPrice) {
                $this->closePosition($openTrade, $currentPrice);
                $this->logger->info("✅ [CLOSE EXISTING] Position closed successfully");
            } else {
                $this->logger->error("❌ [CLOSE EXISTING] Failed to get current price for closing position");
            }
        } else {
            $this->logger->info("✅ [CLOSE EXISTING] No existing position to close");
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
     * Place futures order - MARKET ORDER ONLY
     */
    private function placeFuturesOrder(array $signal, float $positionSize, float $stopLoss, float $takeProfit): ?array
    {
        try {
            // Map signal direction to order side
            $side = ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') ? 'buy' : 'sell';
            
            $this->logger->info("📤 [ORDER] Placing MARKET order: {$side} {$this->bot->symbol} Qty: {$positionSize}");
            
            // Always use market orders for immediate execution
            $orderResult = $this->exchangeService->placeFuturesOrder(
                $this->bot->symbol,
                $side,
                $positionSize,
                $this->bot->leverage,
                $this->bot->margin_type,
                $stopLoss,
                $takeProfit,
                'market', // Force market order
                0 // No limit buffer needed for market orders
            );
            
            // If main order is successful, place SL/TP orders
            if ($orderResult && $orderResult['order_id']) {
                $this->logger->info("✅ [ORDER] Market order placed successfully, now placing SL/TP orders");
                $slTpResult = $this->placeStopLossAndTakeProfitOrders($orderResult, $stopLoss, $takeProfit);
                
                // Update the order result with SL/TP order IDs
                $orderResult['stop_loss_order_id'] = $slTpResult['stop_loss_order_id'] ?? null;
                $orderResult['take_profit_order_id'] = $slTpResult['take_profit_order_id'] ?? null;
            }
            
            return $orderResult;
        } catch (\Exception $e) {
            $this->logger->error("❌ [ORDER] Failed to place futures order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Place stop loss and take profit orders
     */
    private function placeStopLossAndTakeProfitOrders(array $mainOrder, float $stopLoss, float $takeProfit): array
    {
        try {
            $this->logger->info("Placing SL/TP orders for order ID: {$mainOrder['order_id']}");
            
            // Wait a moment for the main order to be processed
            sleep(2);
            
            $stopLossOrderId = null;
            $takeProfitOrderId = null;
            
            // Place stop loss order
            if ($stopLoss !== null) {
                $this->logger->info("Placing stop loss order at price: {$stopLoss}");
                $stopLossOrderId = $this->exchangeService->placeStopLossOrder(
                    $this->bot->symbol,
                    $mainOrder['side'],
                    $mainOrder['quantity'],
                    $stopLoss
                );
                $this->logger->info("Stop loss order result: " . ($stopLossOrderId ? $stopLossOrderId : 'FAILED'));
            }
            
            // Place take profit order
            if ($takeProfit !== null) {
                $this->logger->info("Placing take profit order at price: {$takeProfit}");
                $takeProfitOrderId = $this->exchangeService->placeTakeProfitOrder(
                    $this->bot->symbol,
                    $mainOrder['side'],
                    $mainOrder['quantity'],
                    $takeProfit
                );
                $this->logger->info("Take profit order result: " . ($takeProfitOrderId ? $takeProfitOrderId : 'FAILED'));
            }
            
            return [
                'stop_loss_order_id' => $stopLossOrderId,
                'take_profit_order_id' => $takeProfitOrderId
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to place SL/TP orders: " . $e->getMessage());
            return [
                'stop_loss_order_id' => null,
                'take_profit_order_id' => null
            ];
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
            'stop_loss_order_id' => $order['stop_loss_order_id'] ?? null,
            'take_profit_order_id' => $order['take_profit_order_id'] ?? null,
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
        // Map signal direction to database enum values
        $direction = $signal['direction'] === 'bullish' ? 'long' : 'short';
        
        FuturesSignal::create([
            'futures_trading_bot_id' => $this->bot->id,
            'symbol' => $this->bot->symbol,
            'timeframe' => $signal['timeframe'],
            'direction' => $direction,
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
     * Learn from trading history and apply improvements
     */
    private function learnFromTradingHistory(): void
    {
        try {
            $this->logger->info("🧠 [LEARNING] Starting trading performance analysis...");
            
            // Get learning summary first
            $summary = $this->learningService->getLearningSummary();
            
            if (isset($summary['message'])) {
                $this->logger->info("ℹ️ [LEARNING] {$summary['message']}");
                return;
            }
            
            $this->logger->info("📊 [LEARNING] Trading Summary: {$summary['total_trades']} trades, {$summary['win_rate']}% win rate, {$summary['total_pnl']} total PnL");
            
            // Only run full analysis if we have enough data
            if ($summary['total_trades'] >= 5) {
                $analysis = $this->learningService->analyzeAndLearn();
                
                if (!empty($analysis['recommendations'])) {
                    $this->logger->info("💡 [LEARNING] Recommendations:");
                    foreach ($analysis['recommendations'] as $recommendation) {
                        $this->logger->info("   - {$recommendation}");
                    }
                }
                
                if (!empty($analysis['risk_adjustments'])) {
                    $this->logger->info("⚙️ [LEARNING] Risk adjustments applied based on performance");
                }
            } else {
                $this->logger->info("⏳ [LEARNING] Need at least 5 trades for meaningful analysis (current: {$summary['total_trades']})");
            }
            
        } catch (\Exception $e) {
            $this->logger->error("❌ [LEARNING] Error during learning process: " . $e->getMessage());
        }
    }

    /**
     * Sync positions with exchange - IMPROVED VERSION
     */
    private function syncPositionsWithExchange(): void
    {
        try {
            $this->logger->info("🔄 [SYNC] Starting position synchronization with exchange...");
            
            // Get actual open positions from exchange
            $exchangePositions = $this->exchangeService->getOpenPositions($this->bot->symbol);
            
            if (empty($exchangePositions)) {
                $this->logger->info("✅ [SYNC] No open positions found on exchange");
                
                // Check if we have any trades marked as open in database
                $openTrades = $this->getOpenTrade();
                if ($openTrades) {
                    $this->logger->warning("⚠️ [SYNC] Found trade marked as open in database but no position on exchange");
                    
                    // Check order status
                    $orderStatus = $this->exchangeService->getOrderStatus($openTrades->symbol, $openTrades->order_id);
                    
                    if ($orderStatus) {
                        if ($orderStatus['status'] === 'FILLED') {
                            $this->logger->info("✅ [SYNC] Order was filled but no position on exchange - position was likely closed by SL/TP");
                            
                            // Get current price to calculate PnL
                            $currentPrice = $this->exchangeService->getCurrentPrice($openTrades->symbol);
                            
                            if ($currentPrice) {
                                // Calculate PnL based on entry and current price
                                if ($openTrades->side === 'long') {
                                    $pnl = ($currentPrice - $openTrades->entry_price) * $openTrades->quantity;
                                } else {
                                    $pnl = ($openTrades->entry_price - $currentPrice) * $openTrades->quantity;
                                }
                                
                                $this->logger->info("📊 [SYNC] Calculated PnL: {$pnl} (Entry: {$openTrades->entry_price}, Current: {$currentPrice})");
                                
                                // CRITICAL FIX: Save PnL to persistent storage before closing
                                $this->savePersistentPnL($openTrades->id, $pnl);
                                
                                $openTrades->update([
                                    'status' => 'closed',
                                    'exit_price' => $currentPrice,
                                    'realized_pnl' => $pnl,
                                    'closed_at' => now()
                                ]);
                                
                                $this->logger->info("✅ [SYNC] Updated trade as closed with calculated PnL");
                            } else {
                                $this->logger->warning("⚠️ [SYNC] Could not get current price - using entry price as exit");
                                $openTrades->update([
                                    'status' => 'closed',
                                    'exit_price' => $openTrades->entry_price,
                                    'realized_pnl' => 0,
                                    'closed_at' => now()
                                ]);
                            }
                        } elseif (in_array($orderStatus['status'], ['CANCELED', 'REJECTED', 'EXPIRED'])) {
                            $this->logger->info("❌ [SYNC] Order was {$orderStatus['status']} - updating trade status to cancelled");
                            $openTrades->update([
                                'status' => 'cancelled',
                                'exit_price' => $openTrades->entry_price,
                                'realized_pnl' => 0,
                                'closed_at' => now()
                            ]);
                        } else {
                            $this->logger->info("⏳ [SYNC] Order status: {$orderStatus['status']} - keeping as is");
                        }
                    } else {
                        $this->logger->warning("⚠️ [SYNC] Order not found on exchange - marking as cancelled");
                        $openTrades->update([
                            'status' => 'cancelled',
                            'exit_price' => $openTrades->entry_price,
                            'realized_pnl' => 0,
                            'closed_at' => now()
                        ]);
                    }
                }
            } else {
                $this->logger->info("📈 [SYNC] Found " . count($exchangePositions) . " open position(s) on exchange");
                
                foreach ($exchangePositions as $position) {
                    // Normalize symbol for comparison
                    $dbSymbol = str_replace('USDT', '-USDT', $position['symbol']);
                    
                    // Check if we have a corresponding trade in database
                    $trade = FuturesTrade::where('futures_trading_bot_id', $this->bot->id)
                        ->where('symbol', $dbSymbol)
                        ->where('side', $position['side'])
                        ->where('status', 'open')
                        ->first();
                    
                    if ($trade) {
                        $this->logger->info("✅ [SYNC] Found matching open trade in database (ID: {$trade->id})");
                        
                        // CRITICAL FIX: Save current PnL to persistent storage
                        $this->savePersistentPnL($trade->id, $position['unrealized_pnl']);
                        
                        // Update trade with current position data
                        $trade->update([
                            'quantity' => $position['quantity'],
                            'entry_price' => $position['entry_price'],
                            'unrealized_pnl' => $position['unrealized_pnl'],
                            'leverage' => $position['leverage'],
                            'margin_type' => $position['margin_type']
                        ]);
                        
                        $this->logger->info("📝 [SYNC] Updated trade with current position data");
                    } else {
                        $this->logger->warning("⚠️ [SYNC] No matching open trade found in database - creating new trade record");
                        
                        // Create new trade record for position found on exchange
                        $newTrade = FuturesTrade::create([
                            'futures_trading_bot_id' => $this->bot->id,
                            'symbol' => $dbSymbol,
                            'side' => $position['side'],
                            'quantity' => $position['quantity'],
                            'entry_price' => $position['entry_price'],
                            'unrealized_pnl' => $position['unrealized_pnl'],
                            'leverage' => $position['leverage'],
                            'margin_type' => $position['margin_type'],
                            'status' => 'open',
                            'opened_at' => now(),
                        ]);
                        
                        $this->logger->info("✅ [SYNC] Created new trade record for exchange position (ID: {$newTrade->id})");
                    }
                }
            }
            
            $this->logger->info("✅ [SYNC] Position synchronization completed");
            
        } catch (\Exception $e) {
            $this->logger->error("❌ [SYNC] Error during position synchronization: " . $e->getMessage());
            $this->logger->error("🔍 [STACK] " . $e->getTraceAsString());
        }
    }
    
    /**
     * Save PnL to persistent storage to prevent data loss during session flushes
     */
    private function savePersistentPnL(int $tradeId, float $pnl): void
    {
        try {
            // Save to a dedicated PnL tracking table or cache
            $cacheKey = "trade_pnl_{$tradeId}";
            cache()->put($cacheKey, $pnl, now()->addDays(30)); // Cache for 30 days
            
            // Also save to database for permanent storage
            DB::table('futures_trade_pnl_history')->updateOrInsert(
                ['futures_trade_id' => $tradeId],
                [
                    'pnl_value' => $pnl,
                    'updated_at' => now()
                ]
            );
            
            $this->logger->info("💾 [PERSISTENT PNL] Saved PnL {$pnl} for trade {$tradeId} to persistent storage");
        } catch (\Exception $e) {
            $this->logger->error("❌ [PERSISTENT PNL] Failed to save PnL: " . $e->getMessage());
        }
    }
    
    /**
     * Get persistent PnL from storage
     */
    private function getPersistentPnL(int $tradeId): ?float
    {
        try {
            // Try cache first
            $cacheKey = "trade_pnl_{$tradeId}";
            $cachedPnL = cache()->get($cacheKey);
            
            if ($cachedPnL !== null) {
                return $cachedPnL;
            }
            
            // Fallback to database
            $dbPnL = DB::table('futures_trade_pnl_history')
                ->where('futures_trade_id', $tradeId)
                ->value('pnl_value');
            
            if ($dbPnL !== null) {
                // Restore to cache
                cache()->put($cacheKey, $dbPnL, now()->addDays(30));
                return $dbPnL;
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error("❌ [PERSISTENT PNL] Failed to get PnL: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Final position sync to ensure database accuracy after all operations
     */
    private function finalPositionSync(): void
    {
        try {
            $this->logger->info("🔄 [FINAL SYNC] Performing final position synchronization...");
            
            // Get all open trades for this bot
            $openTrades = FuturesTrade::where('futures_trading_bot_id', $this->bot->id)
                ->where('status', 'open')
                ->get();
            
            if ($openTrades->isEmpty()) {
                $this->logger->info("✅ [FINAL SYNC] No open trades to sync");
                return;
            }
            
            $this->logger->info("📊 [FINAL SYNC] Checking " . $openTrades->count() . " open trades...");
            
            foreach ($openTrades as $trade) {
                $this->logger->info("🔍 [FINAL SYNC] Checking trade ID: {$trade->id}");
                
                // Check if order still exists on exchange
                if ($trade->order_id) {
                    $orderStatus = $this->exchangeService->getOrderStatus($trade->symbol, $trade->order_id);
                    
                    if (!$orderStatus) {
                        $this->logger->warning("⚠️ [FINAL SYNC] Order not found on exchange - marking as cancelled");
                        $trade->update([
                            'status' => 'cancelled',
                            'exit_price' => $trade->entry_price,
                            'realized_pnl' => 0,
                            'closed_at' => now()
                        ]);
                        continue;
                    }
                    
                    // Check if order was cancelled/rejected
                    if (in_array($orderStatus['status'], ['CANCELED', 'REJECTED', 'EXPIRED'])) {
                        $this->logger->info("❌ [FINAL SYNC] Order was {$orderStatus['status']} - updating trade status");
                        $trade->update([
                            'status' => 'cancelled',
                            'exit_price' => $trade->entry_price,
                            'realized_pnl' => 0,
                            'closed_at' => now()
                        ]);
                        continue;
                    }
                }
                
                // Check if position still exists on exchange
                $exchangePositions = $this->exchangeService->getOpenPositions($this->bot->symbol);
                $positionExists = false;
                
                foreach ($exchangePositions as $position) {
                    $dbSymbol = str_replace('USDT', '-USDT', $position['symbol']);
                    if ($dbSymbol === $trade->symbol && $position['side'] === $trade->side) {
                        $positionExists = true;
                        
                        // Update trade with latest position data
                        $trade->update([
                            'quantity' => $position['quantity'],
                            'entry_price' => $position['entry_price'],
                            'unrealized_pnl' => $position['unrealized_pnl']
                        ]);
                        
                        $this->logger->info("✅ [FINAL SYNC] Updated trade with current position data");
                        break;
                    }
                }
                
                if (!$positionExists) {
                    $this->logger->warning("⚠️ [FINAL SYNC] Position not found on exchange - position was likely closed");
                    
                    // Get current price to calculate PnL
                    $currentPrice = $this->exchangeService->getCurrentPrice($trade->symbol);
                    
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
                        
                        $this->logger->info("✅ [FINAL SYNC] Closed trade with calculated PnL: {$pnl}");
                    } else {
                        $trade->update([
                            'status' => 'closed',
                            'exit_price' => $trade->entry_price,
                            'realized_pnl' => 0,
                            'closed_at' => now()
                        ]);
                        
                        $this->logger->info("✅ [FINAL SYNC] Closed trade with zero PnL (no current price)");
                    }
                }
            }
            
            $this->logger->info("✅ [FINAL SYNC] Final position synchronization completed");
            
        } catch (\Exception $e) {
            $this->logger->error("❌ [FINAL SYNC] Error during final position synchronization: " . $e->getMessage());
        }
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
        
        // Only close on stop loss or take profit - no opposite signal closing
        // This prevents flip-flopping between positions
        return false;
    }

    /**
     * Close position - IMPROVED VERSION
     */
    public function closePosition(FuturesTrade $trade, float $currentPrice): void
    {
        try {
            $this->logger->info("🔴 [CLOSE POSITION] Starting position closure for trade ID: {$trade->id}");
            
            // CRITICAL FIX: Save current PnL before closing
            $finalPnL = $trade->calculateUnrealizedPnL($currentPrice);
            $pnlPercentage = $trade->calculatePnLPercentage();
            
            $this->logger->info("📊 [CLOSE POSITION] Final PnL: {$finalPnL}, PnL%: {$pnlPercentage}%");
            
            // Save to persistent storage
            $this->savePersistentPnL($trade->id, $finalPnL);
            
            $side = $trade->isLong() ? 'sell' : 'buy';
            
            $orderResult = $this->exchangeService->closeFuturesPosition(
                $trade->symbol,
                $side,
                $trade->quantity,
                $trade->order_id
            );
            
            if ($orderResult) {
                $this->logger->info("✅ [CLOSE POSITION] Position closed successfully on exchange");
                
                // Update trade with final PnL
                $trade->update([
                    'exit_price' => $currentPrice,
                    'realized_pnl' => $finalPnL,
                    'pnl_percentage' => $pnlPercentage,
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
                
                $this->logger->info("💾 [CLOSE POSITION] Trade updated in database with final PnL");
                
                // Set cooldown period after closing position
                $this->setCooldownPeriod();
                
                $this->logger->info("⏰ [COOLDOWN] Cooldown period activated - no new positions for 30 minutes");
            } else {
                $this->logger->error("❌ [CLOSE POSITION] Failed to close position on exchange");
                
                // Even if exchange close fails, update with current PnL for tracking
                $trade->update([
                    'unrealized_pnl' => $finalPnL,
                    'pnl_percentage' => $pnlPercentage,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error("❌ [CLOSE POSITION] Error closing position: " . $e->getMessage());
            $this->logger->error("🔍 [STACK] " . $e->getTraceAsString());
            
            // Save PnL even if close fails
            try {
                $finalPnL = $trade->calculateUnrealizedPnL($currentPrice);
                $this->savePersistentPnL($trade->id, $finalPnL);
                $this->logger->info("💾 [CLOSE POSITION] Saved PnL despite close failure: {$finalPnL}");
            } catch (\Exception $saveError) {
                $this->logger->error("❌ [CLOSE POSITION] Failed to save PnL: " . $saveError->getMessage());
            }
        }
    }

    /**
     * Update existing positions with current PnL and check for time-based exits
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
            
            // Check for time-based exit (micro trading: max 2 hours)
            $maxTradeDuration = config('micro_trading.signal_settings.max_trade_duration_hours', 2);
            $tradeAge = now()->diffInHours($trade->opened_at);
            
            if ($tradeAge >= $maxTradeDuration) {
                $this->logger->info("⏰ [TIME EXIT] Trade {$trade->id} reached maximum duration ({$maxTradeDuration}h) - closing position");
                $this->closePosition($trade, $currentPrice);
            }
        }
    }

    /**
     * Set cooldown period after closing a position
     */
    private function setCooldownPeriod(): void
    {
        $this->bot->update([
            'last_position_closed_at' => now(),
        ]);
    }

    /**
     * Check if bot is in cooldown period after closing a position
     */
    private function isInCooldownPeriod(): bool
    {
        if (!$this->bot->last_position_closed_at) {
            return false;
        }
        
        // Micro trading: shorter cooldown for faster re-entry
        $cooldownMinutes = config('micro_trading.trading_sessions.cooldown_minutes', 10);
        $cooldownEnd = $this->bot->last_position_closed_at->addMinutes($cooldownMinutes);
        
        return now()->lt($cooldownEnd);
    }

    /**
     * Check if it's a good time to place a new trade (micro trading optimized)
     */
    private function isGoodTimeForNewTrade(): bool
    {
        // Check cooldown period
        if ($this->isInCooldownPeriod()) {
            $this->logger->info("⏰ [TIMING] Bot is in cooldown period - waiting for re-entry");
            return false;
        }
        
        // Check trading session hours
        $sessionHours = config('micro_trading.trading_sessions.session_hours', ['start' => 0, 'end' => 24]);
        $currentHour = now()->hour;
        
        if ($currentHour < $sessionHours['start'] || $currentHour >= $sessionHours['end']) {
            $this->logger->info("⏰ [TIMING] Outside trading session hours ({$sessionHours['start']}:00 - {$sessionHours['end']}:00)");
            return false;
        }
        
        // Check max trades per hour limit
        $maxTradesPerHour = config('micro_trading.trading_sessions.max_trades_per_hour', 5);
        $tradesThisHour = FuturesTrade::where('futures_trading_bot_id', $this->bot->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($tradesThisHour >= $maxTradesPerHour) {
            $this->logger->info("⏰ [TIMING] Max trades per hour reached ({$tradesThisHour}/{$maxTradesPerHour})");
            return false;
        }
        
        // Check if we have open positions (micro trading: prefer single position)
        $openTrades = $this->getOpenTrade();
        if ($openTrades) {
            $this->logger->info("⏰ [TIMING] Already have open position - micro trading prefers single position management");
            return false;
        }
        
        $this->logger->info("✅ [TIMING] Good time for new trade - all conditions met");
        return true;


    }
}

