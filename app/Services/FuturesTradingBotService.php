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
            // KuCoin spot API interval formats
            $kucoinIntervals = [
                '1m' => '1min',
                '5m' => '5min',
                '15m' => '15min',
                '30m' => '30min',
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
            
            // Learning system disabled as requested by user
            // $this->learnFromTradingHistory();
            
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
     * Filter and rank signals based on strength and confluence - ULTRA HIGH STRENGTH REQUIREMENT
     */
    private function filterSignals(array $signals): array
    {
        $filtered = [];
        $this->logger->info("🔍 [FILTER] Starting to filter " . count($signals) . " signals with ULTRA HIGH STRENGTH requirement...");
        
        foreach ($signals as $index => $signal) {
            $this->logger->info("🔍 [FILTER] Processing signal {$index}: " . json_encode($signal));
            
            // SIMPLIFIED: Use configurable strength requirement for more trading opportunities
            $requiredStrength = config('micro_trading.signal_settings.high_strength_requirement', 0.70); // 70% strength requirement
            $signalStrength = $signal['strength'] ?? 0;
            
            // Special priority for engulfing patterns - they get slightly lower threshold
            if (in_array($signal['type'], ['Engulfing_Bullish', 'Engulfing_Bearish'])) {
                $requiredStrength = 0.90; // 90% for engulfing patterns
                $this->logger->info("🕯️ [FILTER] Engulfing pattern detected - using 90% threshold");
            }
            
            if ($signalStrength < $requiredStrength) {
                $this->logger->info("❌ [FILTER] Signal {$index} rejected - strength too low: {$signalStrength} (required: {$requiredStrength} = " . ($requiredStrength * 100) . "%)");
                continue;
            }
            
            $this->logger->info("✅ [FILTER] Signal {$index} passed ULTRA HIGH STRENGTH requirement: {$signalStrength} >= {$requiredStrength} (" . ($requiredStrength * 100) . "%)");
            
            // Enhanced confluence calculation with time-based weighting
            $confluence = $this->calculateEnhancedSignalConfluence($signal, $signals);
            $this->logger->info("🔗 [FILTER] Signal {$index} enhanced confluence: {$confluence}");
            
            // Updated confluence requirements based on analysis
            $minConfluence = config('micro_trading.signal_settings.min_confluence', 2);
            
            // Special handling for engulfing patterns - they can trade with less confluence
            if (in_array($signal['type'], ['Engulfing_Bullish', 'Engulfing_Bearish'])) {
                $minConfluence = 1; // Engulfing patterns need less confluence
                $this->logger->info("🕯️ [FILTER] Engulfing pattern - reduced confluence requirement to 1");
            }
            
            // Additional quality checks
            if (!$this->passesQualityChecks($signal)) {
                $this->logger->info("❌ [FILTER] Signal {$index} failed quality checks");
                continue;
            }
            
            if ($confluence >= $minConfluence) {
                $signal['confluence'] = $confluence;
                $signal['filter_score'] = $this->calculateFilterScore($signal);
                $filtered[] = $signal;
                $this->logger->info("✅ [FILTER] Signal {$index} accepted (strength: {$signalStrength}, confluence: {$confluence}, filter_score: {$signal['filter_score']})");
            } else {
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
     * Calculate enhanced signal confluence across timeframes with time-based weighting
     */
    private function calculateEnhancedSignalConfluence(array $signal, array $allSignals): int
    {
        $confluence = 0;
        
        foreach ($allSignals as $otherSignal) {
            // Same direction signals from different timeframes
            if ($otherSignal['direction'] === $signal['direction'] &&
                $otherSignal['timeframe'] !== $signal['timeframe']) {
                
                // Different signal types can still provide confluence if they agree on direction
                if ($otherSignal['type'] === $signal['type']) {
                    $confluence += 2; // Same type = stronger confluence
                } else {
                    $confluence += 1; // Different type but same direction = weaker confluence
                }
            }
        }
        
        return $confluence;
    }
    
    /**
     * Calculate signal confluence across timeframes (legacy method)
     */
    private function calculateSignalConfluence(array $signal, array $allSignals): int
    {
        return $this->calculateEnhancedSignalConfluence($signal, $allSignals);
    }
    
    /**
     * Additional quality checks for signals
     */
    private function passesQualityChecks(array $signal): bool
    {
        // Check 1: Signal must have a valid strength
        if (!isset($signal['strength']) || $signal['strength'] <= 0) {
            $this->logger->info("❌ [QUALITY] Signal missing or invalid strength");
            return false;
        }
        
        // Check 2: Signal must have a valid direction
        if (!isset($signal['direction']) || !in_array($signal['direction'], ['bullish', 'bearish', 'long', 'short'])) {
            $this->logger->info("❌ [QUALITY] Signal missing or invalid direction");
            return false;
        }
        
        // Check 3: Signal must have a valid price/level
        if (!isset($signal['price']) && !isset($signal['level'])) {
            $this->logger->info("❌ [QUALITY] Signal missing price/level information");
            return false;
        }
        
        // Check 4: For engulfing patterns, verify engulfing data integrity
        if (in_array($signal['type'], ['Engulfing_Bullish', 'Engulfing_Bearish'])) {
            if (!isset($signal['engulfing_data']) || !is_array($signal['engulfing_data'])) {
                $this->logger->info("❌ [QUALITY] Engulfing signal missing required data");
                return false;
            }
            
            $bodyRatio = $signal['engulfing_data']['body_ratio'] ?? 0;
            if ($bodyRatio < config('micro_trading.signal_settings.engulfing_min_body_ratio', 0.7)) {
                $this->logger->info("❌ [QUALITY] Engulfing body ratio too low: {$bodyRatio}");
                return false;
            }
        }
        
        $this->logger->info("✅ [QUALITY] Signal passed all quality checks");
        return true;
    }
    
    /**
     * Calculate overall filter score for signal ranking
     */
    private function calculateFilterScore(array $signal): float
    {
        $score = 0;
        
        // Base score from signal strength (50% weight)
        $score += ($signal['strength'] ?? 0) * 0.5;
        
        // Confluence bonus (20% weight)
        $confluenceScore = min(1.0, ($signal['confluence'] ?? 0) / 3); // Max 3 confluence
        $score += $confluenceScore * 0.2;
        
        // Signal type priority (20% weight)
        $typePriority = $this->getSignalTypePriority($signal['type']);
        $score += $typePriority * 0.2;
        
        // Quality factors (10% weight)
        if (isset($signal['quality_factors']) && is_array($signal['quality_factors'])) {
            $qualityScore = 0;
            $factorCount = 0;
            
            foreach ($signal['quality_factors'] as $factor => $value) {
                if (is_bool($value)) {
                    $qualityScore += $value ? 1 : 0;
                } elseif (is_numeric($value)) {
                    $qualityScore += min(1.0, max(0.0, $value));
                }
                $factorCount++;
            }
            
            if ($factorCount > 0) {
                $score += ($qualityScore / $factorCount) * 0.1;
            }
        }
        
        return min(1.0, $score);
    }
    
    /**
     * Get signal type priority for ranking
     */
    private function getSignalTypePriority(string $type): float
    {
        $priorities = [
            'Engulfing_Bullish' => 1.0,      // Highest priority - user requested
            'Engulfing_Bearish' => 1.0,      // Highest priority - user requested
            'OrderBlock_Support' => 0.9,     // High priority
            'OrderBlock_Resistance' => 0.9,  // High priority
            'OrderBlock_Breakout' => 0.8,    // Good priority
            'BOS' => 0.7,                    // Medium priority
            'CHoCH' => 0.7,                  // Medium priority
        ];
        
        return $priorities[$type] ?? 0.5;
    }

    /**
     * Place manual trade with automatic SL/TP calculation
     */
    public function placeManualTrade(string $direction, float $currentPrice): void
    {
        // Create a manual signal
        $manualSignal = [
            'direction' => $direction === 'long' ? 'bullish' : 'bearish',
            'type' => 'Manual_Trade',
            'strength' => 100, // Maximum strength for manual trades
            'timeframe' => '1h', // Default timeframe
            'price' => $currentPrice,
            'confidence' => 1.0, // Maximum confidence
            'manual' => true // Flag to indicate this is manual
        ];

        $this->logger->info("📋 [MANUAL TRADE] Placing manual {$direction} trade at price {$currentPrice}");
        
        // Process the manual signal
        $this->processSignal($manualSignal, $currentPrice);
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
        
        // Multi-timeframe breakout confirmation and trend filter using 1h levels with 15m/30m confirmations
        if (config('micro_trading.mtf_confirmation.enable', true)) {
            $direction = ($signal['direction'] === 'long' || $signal['direction'] === 'bullish') ? 'bullish' : 'bearish';

            // 1) Trend filter on 1h (and optional 30m)
            if (!$this->passesHigherTimeframeTrendFilter($direction)) {
                $this->logger->info("🚫 [TREND FILTER] Higher timeframe trend not aligned - skipping trade");
                return;
            }

            // 2) Determine the recently broken 1h level in the trade direction
            $keyLevel = $this->findNearestBrokenHigherTimeframeLevel($direction, $currentPrice);
            if ($keyLevel === null) {
                $this->logger->info("🚫 [LEVEL] No recently broken 1h level found for {$direction} - skipping trade");
                return;
            }

            // 3) Prevent repeated re-entries at the same level
            if ($this->hasLevelBeenConsumed($keyLevel)) {
                $this->logger->info("⛔ [RE-ENTRY GUARD] Level {$keyLevel} was already traded recently - skipping");
                return;
            }

            // 4) Confirm breakout on 15m and 30m closes
            if (!$this->confirmBreakoutOnLowerTimeframes($keyLevel, $direction)) {
                $this->logger->info("🚫 [CONFIRMATION] Breakout not confirmed on 15m and 30m - skipping trade");
                return;
            }

            // Attach key level to signal for downstream SL/TP tagging and persistence
            $signal['higher_tf_level'] = $keyLevel;
            $this->logger->info("✅ [MTF] Using 1h key level {$keyLevel} with 15m/30m confirmations");
        } else {
            $this->logger->info("ℹ️ [MTF] Multi-timeframe confirmation disabled by config");
        }

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

        // Calculate position size with signal-based dynamic sizing
        $positionSize = $this->calculatePositionSize($currentPrice, $signal);
        
        $this->logger->info("💰 [POSITION SIZE] Calculated position size: {$positionSize}");
        
        if ($positionSize <= 0) {
            $this->logger->warning("❌ [POSITION SIZE] Insufficient balance for futures trade - Position size calculated as: {$positionSize}");
            return;
        }
        
        $this->logger->info("✅ [POSITION SIZE] Position size check passed");
        
        // Calculate stop loss and take profit (now supports multi-TP) with SMC rejection
        try {
            $stopLoss = $this->calculateStopLoss($signal, $currentPrice);
            $takeProfitLevels = $this->calculateTakeProfit($signal, $currentPrice);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'SMC_') === 0) {
                // SMC rejection - log and skip this trade
                $this->logger->warning("🚫 [TRADE REJECTED] {$e->getMessage()} - Waiting for better setup with adequate risk/reward");
                return;
            } else {
                // Other error - re-throw
                throw $e;
            }
        }
        
        // Log the multi-TP setup
        if (count($takeProfitLevels) > 1) {
            $this->logger->info("🎯 [MULTI-TP] Calculated Stop Loss: {$stopLoss}");
            foreach ($takeProfitLevels as $tpLevel) {
                $this->logger->info("🎯 [MULTI-TP] {$tpLevel['level']}: Price {$tpLevel['price']} ({$tpLevel['target_percentage']}%), Close {$tpLevel['position_percentage']}%");
            }
        } else {
            $this->logger->info("🎯 [RISK MANAGEMENT] Calculated Stop Loss: {$stopLoss}, Take Profit: {$takeProfitLevels[0]['price']}");
        }
        
        // Validate risk/reward ratio using the first (closest) take profit
        $primaryTakeProfit = $takeProfitLevels[0]['price'];
        $riskRewardRatio = $this->calculateRiskRewardRatio($currentPrice, $stopLoss, $primaryTakeProfit);
        $this->logger->info("📊 [RISK/REWARD] Calculated ratio (TP1): {$riskRewardRatio}");
        
        // Use price-based minimum risk/reward ratio configuration
        $minRiskReward = $this->getMinimumRiskRewardForContext($signal, $currentPrice);
        
        $this->logger->info("🎯 [RR] Minimum required R/R for context: {$minRiskReward}");
        
        if ($riskRewardRatio < $minRiskReward) {
            $this->logger->info("❌ [RISK/REWARD] Risk/reward ratio too low: {$riskRewardRatio} (minimum: {$minRiskReward}) - skipping trade");
            return;
        }
        
        $this->logger->info("✅ [RISK/REWARD] Risk/reward ratio check passed");
        
        // Place the futures order with stop loss and take profit
        $this->logger->info("📤 [ORDER] Attempting to place futures order...");
        
        // Handle multi-level vs single take profit
        if (is_array($takeProfitLevels) && !empty($takeProfitLevels)) {
            // For multi-level TP, use the first level for the main order
            $primaryTakeProfit = $takeProfitLevels[0]['price'];
            $this->logger->info("📤 [ORDER] Using multi-level TP, primary TP: {$primaryTakeProfit}");
        } else {
            $primaryTakeProfit = null;
            $this->logger->info("📤 [ORDER] No take profit levels calculated");
        }
        
        $order = $this->placeFuturesOrder($signal, $positionSize, $stopLoss, $primaryTakeProfit);
        
        if ($order) {
            $this->logger->info("✅ [ORDER] Futures order placed successfully: " . json_encode($order));
            
            // Save trade to database
            $this->logger->info("💾 [DATABASE] Saving trade to database...");
            $this->saveFuturesTrade($signal, $order, $currentPrice, $stopLoss, $takeProfitLevels);
            
            // Save signal
            $this->logger->info("💾 [DATABASE] Saving signal to database...");
            $this->saveFuturesSignal($signal, $currentPrice, $stopLoss, $takeProfitLevels, $riskRewardRatio);
            
            // Mark the consumed level to avoid immediate re-entries at the same price level
            if (isset($signal['higher_tf_level'])) {
                $this->markLevelConsumed($signal['higher_tf_level']);
            }
            
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
     * Calculate position size based on enhanced risk management - IMPROVED VERSION
     */
    private function calculatePositionSize(float $currentPrice, array $signal = null): float
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
        
        $this->logger->info("💰 [BALANCE] USDT Balance = {$usdtBalance}, Current Price = {$currentPrice}");
        
        if ($usdtBalance <= 0) {
            $this->logger->warning("❌ [BALANCE] No USDT balance available for futures");
            return 0;
        }
        
        // Enhanced position sizing based on signal quality and market conditions
        $baseRiskPercentage = $this->bot->risk_percentage;
        $adjustedRiskPercentage = $this->calculateDynamicRisk($baseRiskPercentage, $signal);
        
        $this->logger->info("📊 [RISK] Base risk: {$baseRiskPercentage}%, Adjusted risk: {$adjustedRiskPercentage}%");
        
        // Calculate position size with dynamic risk
        $riskAmount = $usdtBalance * ($adjustedRiskPercentage / 100);
        $positionValue = $riskAmount * $this->bot->leverage;
        $positionSize = $positionValue / $currentPrice;
        
        $this->logger->info("📏 [CALCULATION] Risk Amount = {$riskAmount} USDT, Leverage = {$this->bot->leverage}x, Initial Position Size = {$positionSize}");
        
        // Apply volatility adjustment if enabled
        if (config('micro_trading.risk_management.volatility_adjustment', true)) {
            $volatilityMultiplier = $this->getVolatilityAdjustment($currentPrice);
            $positionSize *= $volatilityMultiplier;
            $this->logger->info("📊 [VOLATILITY] Applied volatility multiplier: {$volatilityMultiplier}, Adjusted position: {$positionSize}");
        }
        
        // Use ONLY the bot's configured max position size - no overrides
        $maxPositionSize = $this->bot->max_position_size;
        $minNotionalValue = ($this->bot->min_order_value ?? 5) + 0.5;
        $requiredMinPosition = $minNotionalValue / $currentPrice;
        
        $this->logger->info("📏 [LIMITS] Config max position: {$maxPositionSize}, Exchange min position: {$requiredMinPosition}");
        
        // Validate configuration
        $maxPositionNotional = $maxPositionSize * $currentPrice;
        if ($maxPositionNotional < $minNotionalValue) {
            $this->logger->error("❌ [CONFIG ERROR] Max position size ({$maxPositionSize}) creates notional value ({$maxPositionNotional} USDT) below exchange minimum ({$minNotionalValue} USDT)");
            $this->logger->error("❌ [TRADE SKIPPED] Increase max_position_size to at least " . ceil($requiredMinPosition * 1.1));
            return 0;
        }
        
        // Apply limits - RESPECT USER'S MAX POSITION SIZE
        $positionSize = min($positionSize, $maxPositionSize);
        
        // Check if the configured max position meets exchange minimum
        if ($maxPositionSize < $requiredMinPosition) {
            $this->logger->error("❌ [CONFIG ERROR] User's max_position_size ({$maxPositionSize}) is below exchange minimum ({$requiredMinPosition})");
            $this->logger->error("❌ [TRADE SKIPPED] Please increase max_position_size to at least {$requiredMinPosition}");
            return 0; // Don't trade if user's config is below minimum
        }
        
        // Check if calculated position size meets minimum notional value
        $finalNotionalValue = $positionSize * $currentPrice;
        if ($finalNotionalValue < $minNotionalValue) {
            $this->logger->error("❌ [NOTIONAL ERROR] Calculated position creates notional value ({$finalNotionalValue} USDT) below exchange minimum ({$minNotionalValue} USDT)");
            $this->logger->error("❌ [SUGGESTION] Increase risk_percentage or reduce leverage to meet minimum order size");
            return 0; // Don't trade if notional value is too low
        }
        
        $this->logger->info("✅ [FINAL POSITION] Position size: {$positionSize} (notional: {$finalNotionalValue} USDT, max: {$maxPositionSize}, min: {$requiredMinPosition})");
        
        return $positionSize;
    }
    
    /**
     * Calculate dynamic risk percentage based on signal quality
     */
    private function calculateDynamicRisk(float $baseRisk, array $signal = null): float
    {
        if (!$signal || !config('micro_trading.risk_management.dynamic_sizing', true)) {
            return $baseRisk;
        }
        
        $multiplier = 1.0;
        
        // Factor 1: Signal strength (higher strength = higher risk)
        $signalStrength = $signal['strength'] ?? 0;
        if ($signalStrength >= 0.95) {
            $multiplier *= 1.2; // +20% for ultra-high quality signals
        } elseif ($signalStrength >= 0.90) {
            $multiplier *= 1.1; // +10% for high quality signals
        } elseif ($signalStrength < 0.85) {
            $multiplier *= 0.8; // -20% for lower quality signals
        }
        
        // Factor 2: Signal type priority
        if (isset($signal['type'])) {
            $typePriority = $this->getSignalTypePriority($signal['type']);
            if ($typePriority >= 1.0) {
                $multiplier *= 1.15; // +15% for highest priority signals (engulfing)
            } elseif ($typePriority >= 0.9) {
                $multiplier *= 1.05; // +5% for high priority signals
            }
        }
        
        // Factor 3: Confluence bonus
        $confluence = $signal['confluence'] ?? 0;
        if ($confluence >= 3) {
            $multiplier *= 1.1; // +10% for very high confluence
        } elseif ($confluence >= 2) {
            $multiplier *= 1.05; // +5% for good confluence
        }
        
        // Factor 4: Quality factors
        if (isset($signal['quality_factors']) && is_array($signal['quality_factors'])) {
            $qualityScore = 0;
            $factorCount = 0;
            
            foreach ($signal['quality_factors'] as $factor => $value) {
                if (is_bool($value) && $value) {
                    $qualityScore += 1;
                } elseif (is_numeric($value)) {
                    $qualityScore += min(1.0, max(0.0, $value));
                }
                $factorCount++;
            }
            
            if ($factorCount > 0) {
                $avgQuality = $qualityScore / $factorCount;
                if ($avgQuality >= 0.8) {
                    $multiplier *= 1.05; // +5% for high quality factors
                }
            }
        }
        
        // Apply conservative limits (don't risk more than 2x or less than 0.5x)
        $multiplier = min(2.0, max(0.5, $multiplier));
        
        $adjustedRisk = $baseRisk * $multiplier;
        
        // Cap at reasonable maximum (based on analysis showing issues with high frequency)
        $maxRisk = config('micro_trading.risk_management.max_dynamic_risk', 3.0);
        $adjustedRisk = min($adjustedRisk, $maxRisk);
        
        $this->logger->info("🎯 [DYNAMIC RISK] Multiplier: {$multiplier}, Adjusted risk: {$adjustedRisk}% (base: {$baseRisk}%)");
        
        return $adjustedRisk;
    }
    
    /**
     * Get volatility adjustment multiplier
     */
    private function getVolatilityAdjustment(float $currentPrice): float
    {
        // Get recent price volatility
        try {
            $recentCandles = $this->exchangeService->getCandles(
                $this->bot->symbol, 
                '5m', 
                12 // Last hour of 5m candles
            );
            
            if (count($recentCandles) < 5) {
                return 1.0; // No adjustment if insufficient data
            }
            
            // Calculate price volatility (standard deviation of returns)
            $returns = [];
            for ($i = 1; $i < count($recentCandles); $i++) {
                $prevClose = $recentCandles[$i-1]['close'];
                $currentClose = $recentCandles[$i]['close'];
                if ($prevClose > 0) {
                    $returns[] = (($currentClose - $prevClose) / $prevClose) * 100;
                }
            }
            
            if (empty($returns)) {
                return 1.0;
            }
            
            $avgReturn = array_sum($returns) / count($returns);
            $variance = array_sum(array_map(function($return) use ($avgReturn) {
                return pow($return - $avgReturn, 2);
            }, $returns)) / count($returns);
            $volatility = sqrt($variance);
            
            $this->logger->info("📊 [VOLATILITY] Calculated volatility: {$volatility}%");
            
            // Adjust position size based on volatility
            if ($volatility > 2.0) {
                return 0.7; // High volatility: reduce position by 30%
            } elseif ($volatility > 1.0) {
                return 0.85; // Medium volatility: reduce position by 15%
            } elseif ($volatility < 0.3) {
                return 1.15; // Low volatility: increase position by 15%
            }
            
            return 1.0; // Normal volatility: no adjustment
            
        } catch (\Exception $e) {
            $this->logger->warning("⚠️ [VOLATILITY] Failed to calculate volatility adjustment: " . $e->getMessage());
            return 1.0;
        }
    }

    /**
     * Get price-based adjustment for SL/TP based on asset price
     */
    private function getPriceBasedAdjustment(float $currentPrice): array
    {
        $priceAdjustmentConfig = config('micro_trading.risk_management.price_based_adjustment');
        
        if (!$priceAdjustmentConfig['enable']) {
            // Return defaults if price adjustment is disabled
            return [
                'stop_loss_percentage' => $this->bot->stop_loss_percentage,
                'take_profit_percentage' => $this->bot->take_profit_percentage,
                'min_risk_reward_ratio' => $this->bot->min_risk_reward_ratio,
                'tier' => 'disabled'
            ];
        }
        
        $priceTiers = $priceAdjustmentConfig['price_tiers'];
        
        // Find the appropriate price tier
        foreach ($priceTiers as $tierName => $tierConfig) {
            $min = $tierConfig['price_range']['min'];
            $max = $tierConfig['price_range']['max'];
            
            if ($currentPrice >= $min && $currentPrice < $max) {
                $this->logger->info("💎 [PRICE TIER] Asset price \${$currentPrice} matched tier '{$tierName}': {$tierConfig['description']}");
                return [
                    'stop_loss_percentage' => $tierConfig['stop_loss_percentage'],
                    'take_profit_percentage' => $tierConfig['take_profit_percentage'],
                    'min_risk_reward_ratio' => $tierConfig['min_risk_reward_ratio'],
                    'tier' => $tierName,
                    'description' => $tierConfig['description']
                ];
            }
        }
        
        // Fallback to bot's configured values if no tier matches
        $this->logger->warning("⚠️ [PRICE TIER] No price tier matched for \${$currentPrice}, using bot defaults");
        return [
            'stop_loss_percentage' => $this->bot->stop_loss_percentage,
            'take_profit_percentage' => $this->bot->take_profit_percentage,
            'min_risk_reward_ratio' => $this->bot->min_risk_reward_ratio,
            'tier' => 'fallback'
        ];
    }

    /**
     * Calculate stop loss for futures using SMC levels with price-based adjustment
     */
    private function calculateStopLoss(array $signal, float $currentPrice): float
    {
        // Prefer 1h S/R-derived SL when multi-timeframe confirmation is enabled
        if (config('micro_trading.mtf_confirmation.enable', true)) {
            $higherLevel = $signal['higher_tf_level'] ?? null;
            if ($higherLevel !== null) {
                if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
                    // SL below the 1h level
                    $bufferPct = config('micro_trading.mtf_confirmation.sl_buffer_pct', 0.003); // 0.3%
                    $sl = $higherLevel * (1 - $bufferPct);
                    $this->logger->info("🛡️ [SL 1H] Using 1h level {$higherLevel} with buffer => SL {$sl}");
                    return $sl;
                } else {
                    // SL above the 1h level
                    $bufferPct = config('micro_trading.mtf_confirmation.sl_buffer_pct', 0.003);
                    $sl = $higherLevel * (1 + $bufferPct);
                    $this->logger->info("🛡️ [SL 1H] Using 1h level {$higherLevel} with buffer => SL {$sl}");
                    return $sl;
                }
            }
        }

        // Get price-based adjustment first
        $priceAdjustment = $this->getPriceBasedAdjustment($currentPrice);
        $minSlPercentage = $priceAdjustment['stop_loss_percentage'] / 100;
        
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
                $smcStopLoss = $nearestSupport * 0.995; // 0.5% below support
                
                // Check if SMC level respects our minimum distance requirement
                $slDistance = ($currentPrice - $smcStopLoss) / $currentPrice;
                
                // If distance is too small, add a 5% buffer to make it acceptable (increased from 2%)
                if ($slDistance < $minSlPercentage) {
                    $originalDistance = $slDistance;
                    $bufferedDistance = $slDistance + 0.05; // Increased buffer from 2% to 5%
                    
                    if ($bufferedDistance >= $minSlPercentage) {
                        // Apply the buffer by adjusting the stop loss further away
                        $smcStopLoss = $currentPrice * (1 - $bufferedDistance);
                        $this->logger->info("🔧 [SMC BUFFER] Applied 5% buffer: " . round($originalDistance*100, 2) . "% -> " . round($bufferedDistance*100, 2) . "%");
                        $this->logger->info("✅ SMC Stop Loss for long: Using buffered support level, stop loss set at {$smcStopLoss} (" . round($bufferedDistance*100, 2) . "%)");
                        return $smcStopLoss;
                    } else {
                        // Instead of rejecting, use a more lenient approach - fall back to percentage-based SL
                        $this->logger->warning("⚠️ [SMC WARNING] Support level too close even with 5% buffer (" . round($bufferedDistance*100, 2) . "%) - falling back to percentage-based SL");
                        // Don't throw exception, let it fall through to percentage-based calculation
                        $smcStopLoss = null;
                    }
                } else {
                    $this->logger->info("✅ SMC Stop Loss for long: Using support level at {$nearestSupport}, stop loss set at {$smcStopLoss} (" . round($slDistance*100, 2) . "%)");
                    return $smcStopLoss;
                }
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
                $smcStopLoss = $nearestResistance * 1.005; // 0.5% above resistance
                
                // Check if SMC level respects our minimum distance requirement
                $slDistance = ($smcStopLoss - $currentPrice) / $currentPrice;
                
                // If distance is too small, add a 5% buffer to make it acceptable (increased from 2%)
                if ($slDistance < $minSlPercentage) {
                    $originalDistance = $slDistance;
                    $bufferedDistance = $slDistance + 0.05; // Increased buffer from 2% to 5%
                    
                    if ($bufferedDistance >= $minSlPercentage) {
                        // Apply the buffer by adjusting the stop loss further away
                        $smcStopLoss = $currentPrice * (1 + $bufferedDistance);
                        $this->logger->info("🔧 [SMC BUFFER] Applied 5% buffer: " . round($originalDistance*100, 2) . "% -> " . round($bufferedDistance*100, 2) . "%");
                        $this->logger->info("✅ SMC Stop Loss for short: Using buffered resistance level, stop loss set at {$smcStopLoss} (" . round($bufferedDistance*100, 2) . "%)");
                        return $smcStopLoss;
                    } else {
                        // Instead of rejecting, use a more lenient approach - fall back to percentage-based SL
                        $this->logger->warning("⚠️ [SMC WARNING] Resistance level too close even with 5% buffer (" . round($bufferedDistance*100, 2) . "%) - falling back to percentage-based SL");
                        // Don't throw exception, let it fall through to percentage-based calculation
                        $smcStopLoss = null;
                    }
                } else {
                    $this->logger->info("✅ SMC Stop Loss for short: Using resistance level at {$nearestResistance}, stop loss set at {$smcStopLoss} (" . round($slDistance*100, 2) . "%)");
                    return $smcStopLoss;
                }
            }
        }
        
        // Enhanced fallback to percentage-based stop loss with PRICE-BASED adjustment
        $priceAdjustment = $this->getPriceBasedAdjustment($currentPrice);
        $baseStopLossPercentage = $priceAdjustment['stop_loss_percentage'] / 100;
        $stopLossBuffer = config('micro_trading.risk_management.stop_loss_buffer', 0.5) / 100;
        
        $this->logger->info("🎯 [PRICE-BASED SL] Using {$priceAdjustment['tier']} tier: {$priceAdjustment['stop_loss_percentage']}% SL for \${$currentPrice} asset");
        
        // Apply buffer for market noise (addresses tight stop loss issue)
        $adjustedStopLossPercentage = $baseStopLossPercentage + $stopLossBuffer;
        
        // Apply volatility adjustment to stop loss (more conservative)
        $volatilityMultiplier = $this->getVolatilityAdjustment($currentPrice);
        
        // For high volatility, widen stop loss more aggressively; for low volatility, keep reasonable distance
        if ($volatilityMultiplier < 0.8) { // High volatility detected
            $adjustedStopLossPercentage *= 1.5; // Widen stop loss by 50% (increased from 30%)
            $this->logger->info("🌪️ [VOLATILITY SL] High volatility detected - widening stop loss by 50%");
        } elseif ($volatilityMultiplier > 1.1) { // Low volatility detected
            $adjustedStopLossPercentage *= 1.0; // No tightening in low volatility (was 0.9)
            $this->logger->info("🔒 [VOLATILITY SL] Low volatility detected - maintaining standard stop loss");
        }
        
        // Special handling for engulfing patterns - less aggressive tightening
        if (isset($signal['type']) && in_array($signal['type'], ['Engulfing_Bullish', 'Engulfing_Bearish'])) {
            $adjustedStopLossPercentage *= 0.95; // Only 5% tighter for engulfing patterns (was 15%)
            $this->logger->info("🕯️ [ENGULFING SL] Engulfing pattern - using slightly tighter stop loss");
        }
        
        // Ensure minimum stop loss distance (prevent too tight stops) - varies by price tier
        $minStopLossPercentage = max(0.015, $baseStopLossPercentage * 0.5); // Minimum 1.5% or 50% of base SL
        $adjustedStopLossPercentage = max($adjustedStopLossPercentage, $minStopLossPercentage);
        
        $this->logger->info("🔒 [SL PROTECTION] Min SL: " . ($minStopLossPercentage * 100) . "%, Final SL: " . ($adjustedStopLossPercentage * 100) . "%");
        
        if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
            $fallbackStopLoss = $currentPrice * (1 - $adjustedStopLossPercentage);
            $this->logger->info("📉 [SL LONG] Enhanced stop loss: {$fallbackStopLoss} (adjusted: " . ($adjustedStopLossPercentage * 100) . "%)");
            return $fallbackStopLoss;
        } else {
            $fallbackStopLoss = $currentPrice * (1 + $adjustedStopLossPercentage);
            $this->logger->info("📈 [SL SHORT] Enhanced stop loss: {$fallbackStopLoss} (adjusted: " . ($adjustedStopLossPercentage * 100) . "%)");
            return $fallbackStopLoss;
        }
    }

    /**
     * Calculate take profit for futures using SMC levels
     */
    private function calculateTakeProfit(array $signal, float $currentPrice): array
    {
        $isMultiTpEnabled = config('micro_trading.risk_management.multi_take_profit', false);
        
        if (!$isMultiTpEnabled) {
            // Legacy single take profit logic
            return $this->calculateSingleTakeProfit($signal, $currentPrice);
        }
        
        // NEW: Multi-level take profit system with position scaling
        $takeProfitLevels = config('micro_trading.risk_management.take_profit_levels', []);
        $multiTakeProfits = [];
        
        $this->logger->info("🎯 [MULTI-TP] Calculating multi-level take profits with position scaling");
        
        // Apply volatility and signal adjustments
        $volatilityMultiplier = $this->getVolatilityAdjustment($currentPrice);
        $volatilityAdjustment = 1.0;
        
        if ($volatilityMultiplier < 0.8) { // High volatility
            $volatilityAdjustment = 1.2; // Widen all TPs by 20%
            $this->logger->info("🌪️ [MULTI-TP] High volatility - widening all TPs by 20%");
        } elseif ($volatilityMultiplier > 1.1) { // Low volatility
            $volatilityAdjustment = 0.95; // Slightly tighten all TPs by 5%
            $this->logger->info("🔒 [MULTI-TP] Low volatility - tightening all TPs by 5%");
        }
        
        // Special handling for engulfing patterns - wider targets
        $engulfingMultiplier = 1.0;
        if (isset($signal['type']) && in_array($signal['type'], ['Engulfing_Bullish', 'Engulfing_Bearish'])) {
            $engulfingMultiplier = 1.15; // 15% wider for engulfing patterns
            $this->logger->info("🕯️ [MULTI-TP] Engulfing pattern - increasing all TPs by 15%");
        }
        
        foreach ($takeProfitLevels as $level => $config) {
            $percentage = ($config['percentage'] / 100) * $volatilityAdjustment * $engulfingMultiplier;
            $positionClose = $config['position_close'];
            
            if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
                $tpPrice = $currentPrice * (1 + $percentage);
            } else {
                $tpPrice = $currentPrice * (1 - $percentage);
            }
            
            $multiTakeProfits[] = [
                'level' => $level,
                'price' => $tpPrice,
                'position_percentage' => $positionClose,
                'target_percentage' => $percentage * 100,
                'original_percentage' => $config['percentage']
            ];
            
            $this->logger->info("📈 [MULTI-TP] {$level}: Price {$tpPrice} (" . round($percentage * 100, 2) . "%), Close {$positionClose}% of position");
        }
        
        // Add summary
        $totalPositionClose = array_sum(array_column($multiTakeProfits, 'position_percentage'));
        $this->logger->info("🎯 [MULTI-TP] Summary: {" . count($multiTakeProfits) . "} levels, {$totalPositionClose}% total position closure");
        
        return $multiTakeProfits;
    }
    
    /**
     * Legacy single take profit calculation
     */
    private function calculateSingleTakeProfit(array $signal, float $currentPrice): array
    {
        // Prefer 1h S/R-derived TP when multi-timeframe confirmation is enabled
        if (config('micro_trading.mtf_confirmation.enable', true)) {
            $higherLevel = $signal['higher_tf_level'] ?? null;
            if ($higherLevel !== null) {
                $tpBufferPct = config('micro_trading.mtf_confirmation.tp_extension_pct', 0.006); // 0.6%
                if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
                    // First TP at or just beyond next 1h resistance above level; fallback extend from level
                    $tp = $this->findNextHigherTimeframeTarget($higherLevel, 'bullish') ?? ($higherLevel * (1 + $tpBufferPct));
                    $this->logger->info("🎯 [TP 1H] Using 1h target {$tp} based on level {$higherLevel}");
                    return [['level' => 'single', 'price' => $tp, 'position_percentage' => 100, 'target_percentage' => (($tp / $currentPrice) - 1) * 100]];
                } else {
                    $tp = $this->findNextHigherTimeframeTarget($higherLevel, 'bearish') ?? ($higherLevel * (1 - $tpBufferPct));
                    $this->logger->info("🎯 [TP 1H] Using 1h target {$tp} based on level {$higherLevel}");
                    return [['level' => 'single', 'price' => $tp, 'position_percentage' => 100, 'target_percentage' => (1 - ($tp / $currentPrice)) * 100]];
                }
            }
        }

        // Get price-based adjustment first
        $priceAdjustment = $this->getPriceBasedAdjustment($currentPrice);
        $minTpPercentage = $priceAdjustment['take_profit_percentage'] / 100;
        
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
                
                // Check if the resistance level provides adequate reward based on price tier
                $tpDistance = ($nearestResistance - $currentPrice) / $currentPrice;
                
                // If distance is too small, add a 5% buffer to make it acceptable (increased from 2%)
                if ($tpDistance < $minTpPercentage) {
                    $originalDistance = $tpDistance;
                    $bufferedDistance = $tpDistance + 0.05; // Increased buffer from 2% to 5%
                    
                    if ($bufferedDistance >= $minTpPercentage) {
                        // Apply the buffer by adjusting the take profit further away
                        $bufferedTakeProfit = $currentPrice * (1 + $bufferedDistance);
                        $this->logger->info("🔧 [SMC BUFFER] Applied 5% buffer to TP: " . round($originalDistance*100, 2) . "% -> " . round($bufferedDistance*100, 2) . "%");
                        $this->logger->info("✅ SMC Take Profit for long: Using buffered resistance level at {$bufferedTakeProfit} (" . round($bufferedDistance*100, 2) . "%)");
                        $targetPercentage = $bufferedDistance * 100;
                        return [['level' => 'single', 'price' => $bufferedTakeProfit, 'position_percentage' => 100, 'target_percentage' => $targetPercentage]];
                    } else {
                        // Instead of rejecting, use a more lenient approach - fall back to percentage-based TP
                        $this->logger->warning("⚠️ [SMC WARNING] Resistance level too close even with 5% buffer (" . round($bufferedDistance*100, 2) . "%) - falling back to percentage-based TP");
                        // Don't throw exception, let it fall through to percentage-based calculation
                        $this->logger->info("🔄 [FALLBACK] Using percentage-based take profit instead of SMC levels");
                    }
                } else {
                    $this->logger->info("✅ SMC Take Profit for long: Using resistance level at {$nearestResistance} (" . round($tpDistance*100, 2) . "%)");
                    $targetPercentage = $tpDistance * 100;
                    return [['level' => 'single', 'price' => $nearestResistance, 'position_percentage' => 100, 'target_percentage' => $targetPercentage]];
                }
            } else {
                $this->logger->info("🔄 [FALLBACK] No SMC resistance levels found - using percentage-based take profit");
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
                
                // Check if the support level provides adequate reward based on price tier
                $tpDistance = ($currentPrice - $nearestSupport) / $currentPrice;
                
                // If distance is too small, add a 5% buffer to make it acceptable (increased from 2%)
                if ($tpDistance < $minTpPercentage) {
                    $originalDistance = $tpDistance;
                    $bufferedDistance = $tpDistance + 0.05; // Increased buffer from 2% to 5%
                    
                    if ($bufferedDistance >= $minTpPercentage) {
                        // Apply the buffer by adjusting the take profit further away
                        $bufferedTakeProfit = $currentPrice * (1 - $bufferedDistance);
                        $this->logger->info("🔧 [SMC BUFFER] Applied 5% buffer to TP: " . round($originalDistance*100, 2) . "% -> " . round($bufferedDistance*100, 2) . "%");
                        $this->logger->info("✅ SMC Take Profit for short: Using buffered support level at {$bufferedTakeProfit} (" . round($bufferedDistance*100, 2) . "%)");
                        $targetPercentage = $bufferedDistance * 100;
                        return [['level' => 'single', 'price' => $bufferedTakeProfit, 'position_percentage' => 100, 'target_percentage' => $targetPercentage]];
                    } else {
                        // Instead of rejecting, use a more lenient approach - fall back to percentage-based TP
                        $this->logger->warning("⚠️ [SMC WARNING] Support level too close even with 5% buffer (" . round($bufferedDistance*100, 2) . "%) - falling back to percentage-based TP");
                        // Don't throw exception, let it fall through to percentage-based calculation
                        $this->logger->info("🔄 [FALLBACK] Using percentage-based take profit instead of SMC levels");
                    }
                } else {
                    $this->logger->info("✅ SMC Take Profit for short: Using support level at {$nearestSupport} (" . round($tpDistance*100, 2) . "%)");
                    $targetPercentage = $tpDistance * 100;
                    return [['level' => 'single', 'price' => $nearestSupport, 'position_percentage' => 100, 'target_percentage' => $targetPercentage]];
                }
            } else {
                $this->logger->info("🔄 [FALLBACK] No SMC support levels found - using percentage-based take profit");
            }
        }
        
        // Fallback to percentage-based take profit with PRICE-BASED adjustment
        $priceAdjustment = $this->getPriceBasedAdjustment($currentPrice);
        $baseTakeProfitPercentage = $priceAdjustment['take_profit_percentage'] / 100;
        
        $this->logger->info("🎯 [PRICE-BASED TP] Using {$priceAdjustment['tier']} tier: {$priceAdjustment['take_profit_percentage']}% TP for \${$currentPrice} asset");
        
        if ($signal['direction'] === 'bullish' || $signal['direction'] === 'long') {
            $fallbackTakeProfit = $currentPrice * (1 + $baseTakeProfitPercentage);
        } else {
            $fallbackTakeProfit = $currentPrice * (1 - $baseTakeProfitPercentage);
        }
        
        return [['level' => 'single', 'price' => $fallbackTakeProfit, 'position_percentage' => 100, 'target_percentage' => $priceAdjustment['take_profit_percentage']]];
    }

    /**
     * Get SMC levels for stop loss and take profit calculation
     */
    private function getSMCLevels(): array
    {
        $levels = [];
        
        // Always derive SL/TP S/R from 1h timeframe for stability
        $timeframe = '1h';
        $candleLimit = $this->getOptimalCandleLimit($timeframe);
        $interval = $this->getExchangeInterval($timeframe);
        $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, $candleLimit);
        
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
     * Confirm breakout on 15m and 30m closes relative to a key 1h level
     */
    private function confirmBreakoutOnLowerTimeframes(float $level, string $direction): bool
    {
        $requiredTimeframes = ['15m', '30m'];
        foreach ($requiredTimeframes as $tf) {
            $interval = $this->getExchangeInterval($tf);
            $limit = 3; // last few candles to confirm a close beyond the level
            $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, $limit);
            if (empty($candles)) {
                $this->logger->info("❌ [CONFIRM] No candles for {$tf}");
                return false;
            }
            $last = $candles[count($candles) - 1];
            if ($direction === 'bullish') {
                if (!($last['close'] > $level)) {
                    $this->logger->info("❌ [CONFIRM] {$tf} close not above level {$level}");
                    return false;
                }
            } else {
                if (!($last['close'] < $level)) {
                    $this->logger->info("❌ [CONFIRM] {$tf} close not below level {$level}");
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Higher timeframe trend filter using 1h (and optional 30m) structure slope
     */
    private function passesHigherTimeframeTrendFilter(string $direction): bool
    {
        $useThirty = config('micro_trading.mtf_confirmation.include_30m_trend', true);
        $requiredAgreement = $useThirty ? 2 : 1;
        $agreements = 0;

        foreach (['1h', '30m'] as $tf) {
            if ($tf === '30m' && !$useThirty) continue;
            $interval = $this->getExchangeInterval($tf);
            $limit = 12; // 12 candles to estimate slope
            $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, $limit);
            if (empty($candles)) continue;
            $first = $candles[0]['close'];
            $last = $candles[count($candles) - 1]['close'];
            $isBull = $last > $first;
            if (($direction === 'bullish' && $isBull) || ($direction === 'bearish' && !$isBull)) {
                $agreements++;
            }
        }

        return $agreements >= $requiredAgreement;
    }

    /**
     * Find nearest recently broken 1h S/R level in the trade direction
     */
    private function findNearestBrokenHigherTimeframeLevel(string $direction, float $currentPrice): ?float
    {
        // Get 1h levels
        $timeframe = '1h';
        $interval = $this->getExchangeInterval($timeframe);
        $candleLimit = $this->getOptimalCandleLimit($timeframe);
        $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, $candleLimit);
        if (empty($candles)) return null;
        $smc = new SmartMoneyConceptsService($candles);
        $levels = $smc->getSupportResistanceLevels();

        // Determine which side and select nearest level beyond/around current price
        if ($direction === 'bullish') {
            // Look for resistance just above current price as breakout level
            $res = array_filter($levels, fn($l) => $l['type'] === 'resistance' && $l['price'] >= $currentPrice * 0.98);
            if (empty($res)) return null;
            usort($res, fn($a, $b) => $a['price'] <=> $b['price']);
            return $res[0]['price'];
        } else {
            // Look for support just below current price as breakdown level
            $sup = array_filter($levels, fn($l) => $l['type'] === 'support' && $l['price'] <= $currentPrice * 1.02);
            if (empty($sup)) return null;
            usort($sup, fn($a, $b) => $b['price'] <=> $a['price']);
            return $sup[0]['price'];
        }
    }

    /**
     * Find next target on 1h in the trade direction from a base level
     */
    private function findNextHigherTimeframeTarget(float $baseLevel, string $direction): ?float
    {
        $timeframe = '1h';
        $interval = $this->getExchangeInterval($timeframe);
        $candleLimit = $this->getOptimalCandleLimit($timeframe);
        $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, $candleLimit);
        if (empty($candles)) return null;
        $smc = new SmartMoneyConceptsService($candles);
        $levels = $smc->getSupportResistanceLevels();

        if ($direction === 'bullish') {
            $res = array_filter($levels, fn($l) => $l['type'] === 'resistance' && $l['price'] > $baseLevel);
            if (empty($res)) return null;
            usort($res, fn($a, $b) => $a['price'] <=> $b['price']);
            return $res[0]['price'];
        } else {
            $sup = array_filter($levels, fn($l) => $l['type'] === 'support' && $l['price'] < $baseLevel);
            if (empty($sup)) return null;
            usort($sup, fn($a, $b) => $b['price'] <=> $a['price']);
            return $sup[0]['price'];
        }
    }

    /**
     * Persist and check consumed 1h levels to avoid repeated re-entries
     */
    private function hasLevelBeenConsumed(float $level): bool
    {
        $key = 'consumed_levels_' . $this->bot->id;
        $levels = cache()->get($key, []);
        $tolerance = config('micro_trading.mtf_confirmation.level_tolerance_pct', 0.0005); // 0.05%
        foreach ($levels as $stored) {
            if (abs($stored - $level) / $level <= $tolerance) {
                return true;
            }
        }
        return false;
    }

    private function markLevelConsumed(float $level): void
    {
        $key = 'consumed_levels_' . $this->bot->id;
        $levels = cache()->get($key, []);
        $levels[] = $level;
        $ttl = now()->addMinutes(config('micro_trading.mtf_confirmation.consumed_ttl_minutes', 180));
        cache()->put($key, $levels, $ttl);
        $this->logger->info("🧷 [LEVEL] Marked level {$level} as consumed for {$this->bot->name}");
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
     * Minimum RR helper favoring stricter RR when using 1h S/R
     */
    private function getMinimumRiskRewardForContext(array $signal, float $currentPrice): float
    {
        if (config('micro_trading.mtf_confirmation.enable', true)) {
            $baseAdj = $this->getPriceBasedAdjustment($currentPrice);
            $base = $baseAdj['min_risk_reward_ratio'] ?? 1.5;
            return max($base, 1.6);
        }
        $baseAdj = $this->getPriceBasedAdjustment($currentPrice);
        return $baseAdj['min_risk_reward_ratio'] ?? 1.5;
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
    private function saveFuturesTrade(array $signal, array $order, float $currentPrice, float $stopLoss, $takeProfit): void
    {
        // Map signal direction to trade side
        $tradeSide = $signal['direction'] === 'bullish' ? 'long' : 'short';
        
        // Handle multi-level vs single take profit for database storage
        $primaryTakeProfit = null;
        if (is_array($takeProfit) && !empty($takeProfit)) {
            $primaryTakeProfit = $takeProfit[0]['price'];
            $this->logger->info("💾 [DATABASE] Storing primary TP from multi-level: {$primaryTakeProfit}");
        } elseif (is_numeric($takeProfit)) {
            $primaryTakeProfit = $takeProfit;
        }
        
        $trade = FuturesTrade::create([
            'futures_trading_bot_id' => $this->bot->id,
            'symbol' => $this->bot->symbol,
            'side' => $tradeSide,
            'quantity' => $order['quantity'] ?? 0,
            'entry_price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $primaryTakeProfit,
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
    private function saveFuturesSignal(array $signal, float $currentPrice, float $stopLoss, $takeProfit, float $riskRewardRatio): void
    {
        // Map signal direction to database enum values
        $direction = $signal['direction'] === 'bullish' ? 'long' : 'short';
        
        // Handle multi-level vs single take profit for signal storage
        $primaryTakeProfit = null;
        if (is_array($takeProfit) && !empty($takeProfit)) {
            $primaryTakeProfit = $takeProfit[0]['price'];
            $this->logger->info("💾 [SIGNAL] Storing primary TP from multi-level: {$primaryTakeProfit}");
        } elseif (is_numeric($takeProfit)) {
            $primaryTakeProfit = $takeProfit;
        }
        
        FuturesSignal::create([
            'futures_trading_bot_id' => $this->bot->id,
            'symbol' => $this->bot->symbol,
            'timeframe' => $signal['timeframe'],
            'direction' => $direction,
            'signal_type' => $signal['type'],
            'strength' => $signal['strength'] ?? 0,
            'price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $primaryTakeProfit,
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
     * Close position - IMPROVED VERSION WITH MARGIN ERROR HANDLING
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
            
            // Check if position actually exists on exchange before trying to close
            $exchangePositions = $this->exchangeService->getOpenPositions($trade->symbol);
            $positionExists = false;
            
            foreach ($exchangePositions as $position) {
                $dbSymbol = str_replace('USDT', '-USDT', $position['symbol']);
                if ($dbSymbol === $trade->symbol && $position['side'] === $trade->side) {
                    $positionExists = true;
                    $this->logger->info("✅ [CLOSE POSITION] Confirmed position exists on exchange");
                    break;
                }
            }
            
            if (!$positionExists) {
                $this->logger->warning("⚠️ [CLOSE POSITION] Position no longer exists on exchange - marking as closed");
                
                // Update trade as closed since position doesn't exist on exchange
                $trade->update([
                    'exit_price' => $currentPrice,
                    'realized_pnl' => $finalPnL,
                    'pnl_percentage' => $pnlPercentage,
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
                
                // Cancel any remaining open SL/TP orders and clear IDs
                $this->cancelProtectiveOrders($trade);

                $this->logger->info("💾 [CLOSE POSITION] Trade marked as closed (position already closed on exchange)");
                $this->setCooldownPeriod();
                return;
            }
            
            // Attempt to close position on exchange
            $side = $trade->side; // Use the actual trade side (long/short)
            
            try {
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
                    
                    // Cancel any remaining open SL/TP orders and clear IDs
                    $this->cancelProtectiveOrders($trade);

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
                
            } catch (\Exception $closeError) {
                $this->logger->error("❌ [CLOSE POSITION] Exception during close: " . $closeError->getMessage());
                
                // Handle specific margin insufficient error
                if (strpos($closeError->getMessage(), 'Insufficient margin') !== false) {
                    $this->logger->warning("⚠️ [MARGIN ERROR] Position may have been already closed by stop loss or liquidation");
                    
                    // Mark position as closed since it likely doesn't exist anymore
                    $trade->update([
                        'exit_price' => $currentPrice,
                        'realized_pnl' => $finalPnL,
                        'pnl_percentage' => $pnlPercentage,
                        'status' => 'closed',
                        'closed_at' => now(),
                    ]);
                    
                    // Cancel any remaining open SL/TP orders and clear IDs
                    $this->cancelProtectiveOrders($trade);

                    $this->logger->info("💾 [MARGIN ERROR] Position marked as closed due to margin error");
                    $this->setCooldownPeriod();
                } else {
                    // For other errors, just update PnL but keep position open
                    $trade->update([
                        'unrealized_pnl' => $finalPnL,
                        'pnl_percentage' => $pnlPercentage,
                    ]);
                    
                    $this->logger->error("🔄 [RETRY] Will retry closing position on next run");
                }
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

    /**
     * Cancel remaining protective orders (SL/TP) for a trade and clear IDs
     */
    private function cancelProtectiveOrders(FuturesTrade $trade): void
    {
        try {
            // Attempt cancel-all for safety
            $this->exchangeService->cancelAllOpenOrdersForSymbol($trade->symbol);

            // Also try targeted cancellations if IDs exist
            if (!empty($trade->stop_loss_order_id)) {
                $this->exchangeService->cancelOrder($trade->symbol, $trade->stop_loss_order_id);
            }
            if (!empty($trade->take_profit_order_id)) {
                $this->exchangeService->cancelOrder($trade->symbol, $trade->take_profit_order_id);
            }

            // Clear IDs in DB to avoid manual cleanup
            $trade->update([
                'stop_loss_order_id' => null,
                'take_profit_order_id' => null,
            ]);

            $this->logger->info("🧹 [CLEANUP] Protective orders cancelled and IDs cleared for trade {$trade->id}");
        } catch (\Exception $e) {
            $this->logger->error("❌ [CLEANUP] Failed cancelling protective orders: " . $e->getMessage());
        }
    }
}

