<?php

namespace App\Services;

use App\Models\ScalpingTradingBot;
use App\Models\ScalpingTrade;
use App\Models\ScalpingSignal;
use App\Services\ExchangeService;
use App\Services\SmartMoneyConceptsService;
use Illuminate\Support\Facades\Log;
use Exception;

class ScalpingTradingBotService
{
    private $bot;
    private $exchangeService;
    private $smcService;
    private $logger;
    private $config;

    public function __construct(ScalpingTradingBot $bot)
    {
        $this->bot = $bot;
        $this->exchangeService = new ExchangeService($bot->apiKey);
        $this->logger = Log::channel('scalping');
        $this->config = config('scalping_trading');
    }

    /**
     * Main scalping execution method
     */
    public function executeScalpingStrategy(): void
    {
        try {
            $this->logger->info("🚀 [SCALPING] Starting scalping analysis for {$this->bot->name} ({$this->bot->symbol})");

            // Check if bot is active and within trading hours
            if (!$this->shouldTrade()) {
                return;
            }

            // Get current market price
            $currentPrice = $this->exchangeService->getCurrentPrice($this->bot->symbol);
            if (!$currentPrice) {
                $this->logger->error("❌ [SCALPING] Failed to get current price for {$this->bot->symbol}");
                return;
            }

            $this->logger->info("💰 [SCALPING] Current price: {$currentPrice}");

            // Check market conditions (spread, volume, etc.)
            if (!$this->isMarketSuitableForScalping($currentPrice)) {
                $this->logger->info("⏸️ [SCALPING] Market conditions not suitable for scalping");
                return;
            }

            // Analyze all scalping timeframes
            $signals = $this->analyzeScalpingTimeframes($currentPrice);

            if (empty($signals)) {
                $this->logger->info("📊 [SCALPING] No scalping signals generated");
                return;
            }

            // Process the strongest signal
            $bestSignal = $this->getBestScalpingSignal($signals);
            
            if ($bestSignal) {
                $this->logger->info("⚡ [SCALPING] Best signal: {$bestSignal['direction']} with strength {$bestSignal['strength']}");
                
                // Execute scalping trade
                $this->executeScalpingTrade($bestSignal, $currentPrice);
            }

            // Monitor existing positions for quick exits
            $this->monitorExistingPositions($currentPrice);

        } catch (Exception $e) {
            $this->logger->error("🚨 [SCALPING] Error in scalping strategy: " . $e->getMessage());
        }
    }

    /**
     * Analyze multiple timeframes for scalping opportunities
     */
    private function analyzeScalpingTimeframes(float $currentPrice): array
    {
        $allSignals = [];
        $timeframes = $this->config['scalping_timeframes'];

        // Primary scalping timeframes (5m, 15m)
        foreach ($timeframes['primary'] as $timeframe) {
            $signals = $this->analyzeTimeframeForScalping($timeframe, $currentPrice, 'primary');
            $allSignals = array_merge($allSignals, $signals);
        }

        // Confirmation timeframe (30m)
        foreach ($timeframes['confirmation'] as $timeframe) {
            $signals = $this->analyzeTimeframeForScalping($timeframe, $currentPrice, 'confirmation');
            $allSignals = array_merge($allSignals, $signals);
        }

        // Trend timeframe (1h) - for bias only
        foreach ($timeframes['trend'] as $timeframe) {
            $bias = $this->getTrendBias($timeframe, $currentPrice);
            $this->logger->info("📈 [TREND BIAS] {$timeframe}: {$bias}");
        }

        return $allSignals;
    }

    /**
     * Analyze specific timeframe for scalping signals
     */
    private function analyzeTimeframeForScalping(string $timeframe, float $currentPrice, string $type): array
    {
        $this->logger->info("⏰ [SCALPING] Analyzing {$timeframe} timeframe for {$type} signals...");

        // Get optimized candle data
        $interval = $this->getExchangeInterval($timeframe);
        $candleLimit = $this->config['candle_limits'][$timeframe] ?? 60;
        
        $candles = $this->exchangeService->getCandles($this->bot->symbol, $interval, $candleLimit);
        
        if (empty($candles)) {
            $this->logger->warning("⚠️ [SCALPING] No candle data for {$timeframe}");
            return [];
        }

        // Initialize SMC service for this timeframe
        $this->smcService = new SmartMoneyConceptsService($candles);
        
        // Generate SMC signals
        $smcSignals = $this->smcService->generateSignals($currentPrice);
        
        // Apply scalping-specific filtering
        $scalpingSignals = $this->filterSignalsForScalping($smcSignals, $timeframe, $type);
        
        // Add scalping-specific signals
        $momentumSignals = $this->generateMomentumScalpingSignals($candles, $currentPrice);
        $priceActionSignals = $this->generatePriceActionScalpingSignals($candles, $currentPrice);
        
        return array_merge($scalpingSignals, $momentumSignals, $priceActionSignals);
    }

    /**
     * Filter SMC signals for scalping suitability
     */
    private function filterSignalsForScalping(array $signals, string $timeframe, string $type): array
    {
        $filtered = [];
        $minStrength = $this->config['signal_settings']['min_strength_threshold'];

        foreach ($signals as $signal) {
            // Apply scalping-specific filters
            if ($signal['strength'] >= $minStrength) {
                // Add scalping metadata
                $signal['scalping_type'] = $type;
                $signal['timeframe'] = $timeframe;
                $signal['scalping_score'] = $this->calculateScalpingScore($signal, $timeframe);
                
                if ($signal['scalping_score'] > 0.6) {
                    $filtered[] = $signal;
                }
            }
        }

        $this->logger->info("✅ [SCALPING] Filtered " . count($filtered) . " scalping signals from {$timeframe}");
        return $filtered;
    }

    /**
     * Generate momentum-based scalping signals
     */
    private function generateMomentumScalpingSignals(array $candles, float $currentPrice): array
    {
        if (!$this->config['scalping_features']['momentum_scalping']['enable']) {
            return [];
        }

        $signals = [];
        $period = $this->config['scalping_features']['momentum_scalping']['momentum_period'];
        
        if (count($candles) < $period + 1) {
            return [];
        }

        // Calculate RSI-like momentum
        $rsi = $this->calculateRSI($candles, $period);
        $lastRsi = $rsi[count($rsi) - 1];
        
        $overbought = $this->config['scalping_features']['momentum_scalping']['overbought_threshold'];
        $oversold = $this->config['scalping_features']['momentum_scalping']['oversold_threshold'];

        // Generate signals based on momentum
        if ($lastRsi < $oversold) {
            $signals[] = [
                'type' => 'momentum_scalping',
                'direction' => 'long',
                'strength' => min(0.9, (($oversold - $lastRsi) / $oversold) + 0.6),
                'timeframe' => 'momentum',
                'entry_reason' => 'Oversold momentum reversal',
                'scalping_score' => 0.75,
                'urgency' => 'high'
            ];
        } elseif ($lastRsi > $overbought) {
            $signals[] = [
                'type' => 'momentum_scalping',
                'direction' => 'short',
                'strength' => min(0.9, (($lastRsi - $overbought) / (100 - $overbought)) + 0.6),
                'timeframe' => 'momentum',
                'entry_reason' => 'Overbought momentum reversal',
                'scalping_score' => 0.75,
                'urgency' => 'high'
            ];
        }

        return $signals;
    }

    /**
     * Generate price action scalping signals
     */
    private function generatePriceActionScalpingSignals(array $candles, float $currentPrice): array
    {
        if (!$this->config['scalping_features']['price_action_scalping']['enable']) {
            return [];
        }

        $signals = [];
        $recentCandles = array_slice($candles, -5); // Last 5 candles
        
        foreach ($recentCandles as $index => $candle) {
            // Detect reversal patterns
            if ($this->isHammerPattern($candle)) {
                $signals[] = [
                    'type' => 'price_action_scalping',
                    'direction' => 'long',
                    'strength' => 0.7,
                    'timeframe' => 'price_action',
                    'entry_reason' => 'Hammer reversal pattern',
                    'scalping_score' => 0.8,
                    'urgency' => 'medium'
                ];
            }
            
            if ($this->isShootingStarPattern($candle)) {
                $signals[] = [
                    'type' => 'price_action_scalping',
                    'direction' => 'short',
                    'strength' => 0.7,
                    'timeframe' => 'price_action',
                    'entry_reason' => 'Shooting star reversal pattern',
                    'scalping_score' => 0.8,
                    'urgency' => 'medium'
                ];
            }
        }

        return $signals;
    }

    /**
     * Get the best scalping signal
     */
    private function getBestScalpingSignal(array $signals): ?array
    {
        if (empty($signals)) {
            return null;
        }

        // Sort by scalping score and strength
        usort($signals, function($a, $b) {
            $scoreA = $a['scalping_score'] * $a['strength'];
            $scoreB = $b['scalping_score'] * $b['strength'];
            return $scoreB <=> $scoreA;
        });

        $bestSignal = $signals[0];
        
        // Check confluence
        $confluence = $this->calculateScalpingConfluence($signals, $bestSignal['direction']);
        $bestSignal['confluence'] = $confluence;

        $minConfluence = $this->config['signal_settings']['min_confluence'];
        
        if ($confluence >= $minConfluence) {
            return $bestSignal;
        }

        return null;
    }

    /**
     * Execute scalping trade
     */
    private function executeScalpingTrade(array $signal, float $currentPrice): void
    {
        $this->logger->info("⚡ [TRADE] Executing scalping trade: {$signal['direction']}");

        // Check if we already have a position in this direction
        if ($this->hasExistingPosition($signal['direction'])) {
            $this->logger->info("⏸️ [TRADE] Already have position in {$signal['direction']} direction");
            return;
        }

        // Calculate scalping position size (smaller than regular trading)
        $positionSize = $this->calculateScalpingPositionSize($currentPrice, $signal);
        
        if ($positionSize <= 0) {
            $this->logger->warning("❌ [TRADE] Insufficient balance for scalping trade");
            return;
        }

        // Calculate tight stop loss and take profit
        $stopLoss = $this->calculateScalpingStopLoss($signal, $currentPrice);
        $takeProfit = $this->calculateScalpingTakeProfit($signal, $currentPrice);

        // Validate risk/reward
        $riskReward = $this->calculateRiskReward($currentPrice, $stopLoss, $takeProfit, $signal['direction']);
        $minRR = $this->config['risk_management']['min_risk_reward_ratio'];

        if ($riskReward < $minRR) {
            $this->logger->warning("❌ [TRADE] Risk/reward ratio {$riskReward} below minimum {$minRR}");
            return;
        }

        try {
            // Place market order for scalping (speed is crucial)
            $orderData = [
                'symbol' => $this->bot->symbol,
                'side' => strtoupper($signal['direction']),
                'type' => 'MARKET',
                'quantity' => $positionSize,
                'leverage' => $this->bot->leverage ?? 10,
            ];

            $orderResponse = $this->exchangeService->placeOrder($orderData);

            if ($orderResponse && isset($orderResponse['orderId'])) {
                // Save trade to database
                $trade = $this->saveScalpingTrade($signal, $currentPrice, $positionSize, $stopLoss, $takeProfit, $orderResponse);
                
                // Place stop loss and take profit orders
                $this->placeScalpingStopOrders($trade, $stopLoss, $takeProfit);
                
                $this->logger->info("✅ [TRADE] Scalping trade executed successfully: Trade ID {$trade->id}");
            }

        } catch (Exception $e) {
            $this->logger->error("🚨 [TRADE] Failed to execute scalping trade: " . $e->getMessage());
        }
    }

    /**
     * Monitor existing positions for quick exits
     */
    private function monitorExistingPositions(float $currentPrice): void
    {
        $openTrades = $this->bot->openTrades;
        
        foreach ($openTrades as $trade) {
            // Check for quick exit conditions
            if ($this->shouldQuickExit($trade, $currentPrice)) {
                $this->logger->info("🚪 [EXIT] Quick exit triggered for trade {$trade->id}");
                $this->closeScalpingPosition($trade, 'quick_exit');
            }
            
            // Update trailing stop if enabled
            if ($this->config['risk_management']['trailing_stop']) {
                $this->updateTralingStop($trade, $currentPrice);
            }
        }
    }

    /**
     * Check if market is suitable for scalping
     */
    private function isMarketSuitableForScalping(float $currentPrice): bool
    {
        $conditions = $this->config['market_conditions'];
        
        // Check spread (if enabled)
        if ($conditions['enable_spread_filter']) {
            $orderBook = $this->exchangeService->getOrderBook($this->bot->symbol, 5);
            if ($orderBook) {
                $spread = (($orderBook['asks'][0][0] - $orderBook['bids'][0][0]) / $currentPrice) * 100;
                $maxSpread = $conditions['max_spread_percentage'];
                
                if ($spread > $maxSpread) {
                    $this->logger->info("⏸️ [MARKET] Spread too wide: {$spread}% (max: {$maxSpread}%)");
                    return false;
                }
            }
        }

        // Check volume (basic implementation)
        if ($conditions['min_volume_filter']) {
            // This would need to be implemented based on your exchange API
            // For now, we'll assume volume is adequate
        }

        return true;
    }

    /**
     * Check if should trade based on session and cooldown
     */
    private function shouldTrade(): bool
    {
        if (!$this->bot->is_active) {
            return false;
        }

        // Check trading session hours
        $currentHour = now()->hour;
        $sessionHours = $this->config['trading_sessions']['session_hours'];
        
        if ($currentHour < $sessionHours['start'] || $currentHour >= $sessionHours['end']) {
            return false;
        }

        // Check cooldown
        $cooldownSeconds = $this->config['trading_sessions']['cooldown_seconds'];
        $lastTrade = $this->bot->trades()->latest()->first();
        
        if ($lastTrade && $lastTrade->created_at->diffInSeconds(now()) < $cooldownSeconds) {
            return false;
        }

        // Check maximum trades per hour
        $maxTradesPerHour = $this->config['trading_sessions']['max_trades_per_hour'];
        $tradesLastHour = $this->bot->trades()
            ->where('created_at', '>=', now()->subHour())
            ->count();
            
        if ($tradesLastHour >= $maxTradesPerHour) {
            $this->logger->info("⏸️ [COOLDOWN] Max trades per hour reached: {$tradesLastHour}/{$maxTradesPerHour}");
            return false;
        }

        return true;
    }

    /**
     * Calculate scalping score for a signal based on various factors
     */
    private function calculateScalpingScore(array $signal, string $timeframe): float
    {
        $score = 0.0;
        
        // Base score from signal strength (0.0 - 0.4)
        $score += ($signal['strength'] ?? 0) * 0.4;
        
        // Timeframe preference for scalping (0.0 - 0.2)
        $timeframeScores = [
            '1m' => 0.2,   // Best for scalping
            '5m' => 0.18,  // Very good
            '15m' => 0.15, // Good
            '30m' => 0.10, // Acceptable
            '1h' => 0.05   // Less ideal for scalping
        ];
        $score += $timeframeScores[$timeframe] ?? 0.05;
        
        // Signal type bonus (0.0 - 0.15)
        $signalType = $signal['type'] ?? '';
        $typeScores = [
            'engulfing' => 0.15,     // Strong reversal pattern
            'bos' => 0.12,           // Break of structure
            'choch' => 0.12,         // Change of character
            'order_block' => 0.10,   // Support/resistance
            'liquidity_grab' => 0.08 // Liquidity play
        ];
        $score += $typeScores[$signalType] ?? 0.05;
        
        // Trend alignment bonus (0.0 - 0.1)
        if (isset($signal['trend_alignment']) && $signal['trend_alignment']) {
            $score += 0.1;
        }
        
        // Volume confirmation bonus (0.0 - 0.1)
        if (isset($signal['volume_confirmed']) && $signal['volume_confirmed']) {
            $score += 0.1;
        }
        
        // Multiple timeframe confluence bonus (0.0 - 0.05)
        if (isset($signal['confluence_count']) && $signal['confluence_count'] > 1) {
            $score += min(0.05, $signal['confluence_count'] * 0.02);
        }
        
        // Ensure score is between 0 and 1
        return max(0.0, min(1.0, $score));
    }

    /**
     * Detect hammer candlestick pattern for reversal signals
     */
    private function isHammerPattern(array $candle): bool
    {
        $open = $candle['open'];
        $high = $candle['high'];
        $low = $candle['low'];
        $close = $candle['close'];
        
        // Basic validation
        if ($open <= 0 || $high <= 0 || $low <= 0 || $close <= 0) {
            return false;
        }
        
        // Calculate body and shadow sizes
        $bodySize = abs($close - $open);
        $upperShadow = $high - max($open, $close);
        $lowerShadow = min($open, $close) - $low;
        $totalRange = $high - $low;
        
        // Avoid division by zero
        if ($totalRange <= 0) {
            return false;
        }
        
        // Hammer pattern criteria:
        // 1. Small body (less than 30% of total range)
        // 2. Long lower shadow (at least 60% of total range)
        // 3. Small or no upper shadow (less than 10% of total range)
        // 4. Body should be in upper portion of the range
        
        $bodyRatio = $bodySize / $totalRange;
        $lowerShadowRatio = $lowerShadow / $totalRange;
        $upperShadowRatio = $upperShadow / $totalRange;
        
        // Check hammer criteria
        $isSmallBody = $bodyRatio <= 0.3;
        $isLongLowerShadow = $lowerShadowRatio >= 0.6;
        $isSmallUpperShadow = $upperShadowRatio <= 0.15; // Slightly more lenient
        
        return $isSmallBody && $isLongLowerShadow && $isSmallUpperShadow;
    }

    /**
     * Detect shooting star candlestick pattern for reversal signals
     */
    private function isShootingStarPattern(array $candle): bool
    {
        $open = $candle['open'];
        $high = $candle['high'];
        $low = $candle['low'];
        $close = $candle['close'];
        
        // Basic validation
        if ($open <= 0 || $high <= 0 || $low <= 0 || $close <= 0) {
            return false;
        }
        
        // Calculate body and shadow sizes
        $bodySize = abs($close - $open);
        $upperShadow = $high - max($open, $close);
        $lowerShadow = min($open, $close) - $low;
        $totalRange = $high - $low;
        
        // Avoid division by zero
        if ($totalRange <= 0) {
            return false;
        }
        
        // Shooting star pattern criteria:
        // 1. Small body (less than 30% of total range)
        // 2. Long upper shadow (at least 60% of total range)
        // 3. Small or no lower shadow (less than 10% of total range)
        // 4. Body should be in lower portion of the range
        
        $bodyRatio = $bodySize / $totalRange;
        $upperShadowRatio = $upperShadow / $totalRange;
        $lowerShadowRatio = $lowerShadow / $totalRange;
        
        // Check shooting star criteria
        $isSmallBody = $bodyRatio <= 0.3;
        $isLongUpperShadow = $upperShadowRatio >= 0.6;
        $isSmallLowerShadow = $lowerShadowRatio <= 0.15; // Slightly more lenient
        
        return $isSmallBody && $isLongUpperShadow && $isSmallLowerShadow;
    }

    /**
     * Get trend bias for a specific timeframe
     */
    private function getTrendBias(string $timeframe, float $currentPrice): string
    {
        try {
            // Get candle data for trend analysis
            $candles = $this->exchangeService->getCandles($this->bot->symbol, $timeframe, 50);
            
            if (empty($candles) || count($candles) < 20) {
                $this->logger->warning("⚠️ [TREND BIAS] Insufficient candle data for {$timeframe}");
                return 'neutral';
            }
            
            // Use multiple indicators for trend bias
            $bias = $this->calculateTrendBias($candles, $currentPrice);
            
            return $bias;
            
        } catch (\Exception $e) {
            $this->logger->error("❌ [TREND BIAS] Error getting trend bias for {$timeframe}: " . $e->getMessage());
            return 'neutral';
        }
    }

    /**
     * Calculate trend bias using multiple indicators
     */
    private function calculateTrendBias(array $candles, float $currentPrice): string
    {
        $bullishSignals = 0;
        $bearishSignals = 0;
        $totalWeight = 0;
        
        // 1. Moving Average Trend (Weight: 30%)
        $maTrend = $this->getMovingAverageTrend($candles, $currentPrice);
        $maWeight = 30;
        $totalWeight += $maWeight;
        
        if ($maTrend === 'bullish') {
            $bullishSignals += $maWeight;
        } elseif ($maTrend === 'bearish') {
            $bearishSignals += $maWeight;
        }
        
        // 2. Price Action Trend (Weight: 25%)
        $paTrend = $this->getPriceActionTrend($candles);
        $paWeight = 25;
        $totalWeight += $paWeight;
        
        if ($paTrend === 'bullish') {
            $bullishSignals += $paWeight;
        } elseif ($paTrend === 'bearish') {
            $bearishSignals += $paWeight;
        }
        
        // 3. Volume Trend (Weight: 20%)
        $volumeTrend = $this->getVolumeTrend($candles);
        $volumeWeight = 20;
        $totalWeight += $volumeWeight;
        
        if ($volumeTrend === 'bullish') {
            $bullishSignals += $volumeWeight;
        } elseif ($volumeTrend === 'bearish') {
            $bearishSignals += $volumeWeight;
        }
        
        // 4. Higher Highs/Lower Lows (Weight: 15%)
        $swingTrend = $this->getSwingTrend($candles);
        $swingWeight = 15;
        $totalWeight += $swingWeight;
        
        if ($swingTrend === 'bullish') {
            $bullishSignals += $swingWeight;
        } elseif ($swingTrend === 'bearish') {
            $bearishSignals += $swingWeight;
        }
        
        // 5. Momentum (Weight: 10%)
        $momentum = $this->getMomentumTrend($candles);
        $momentumWeight = 10;
        $totalWeight += $momentumWeight;
        
        if ($momentum === 'bullish') {
            $bullishSignals += $momentumWeight;
        } elseif ($momentum === 'bearish') {
            $bearishSignals += $momentumWeight;
        }
        
        // Calculate bias based on weighted signals
        $bullishPercentage = ($bullishSignals / $totalWeight) * 100;
        $bearishPercentage = ($bearishSignals / $totalWeight) * 100;
        
        // Determine final bias with thresholds
        if ($bullishPercentage >= 60) {
            return 'bullish';
        } elseif ($bearishPercentage >= 60) {
            return 'bearish';
        } else {
            return 'neutral';
        }
    }

    /**
     * Get moving average trend
     */
    private function getMovingAverageTrend(array $candles, float $currentPrice): string
    {
        if (count($candles) < 20) {
            return 'neutral';
        }
        
        // Calculate EMA 20
        $prices = array_column($candles, 'close');
        $ema20 = $this->calculateEMA($prices, 20);
        
        if (empty($ema20)) {
            return 'neutral';
        }
        
        $latestEMA = end($ema20);
        
        // Price above EMA = bullish, below = bearish
        if ($currentPrice > $latestEMA * 1.001) { // 0.1% buffer
            return 'bullish';
        } elseif ($currentPrice < $latestEMA * 0.999) {
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Get price action trend (higher highs/lower lows)
     */
    private function getPriceActionTrend(array $candles): string
    {
        if (count($candles) < 10) {
            return 'neutral';
        }
        
        $recentCandles = array_slice($candles, -10);
        $highs = array_column($recentCandles, 'high');
        $lows = array_column($recentCandles, 'low');
        
        // Check for higher highs and higher lows
        $firstHalf = array_slice($highs, 0, 5);
        $secondHalf = array_slice($highs, 5);
        
        $avgFirstHighs = array_sum($firstHalf) / count($firstHalf);
        $avgSecondHighs = array_sum($secondHalf) / count($secondHalf);
        
        $firstLows = array_slice($lows, 0, 5);
        $secondLows = array_slice($lows, 5);
        
        $avgFirstLows = array_sum($firstLows) / count($firstLows);
        $avgSecondLows = array_sum($secondLows) / count($secondLows);
        
        // Higher highs and higher lows = bullish
        if ($avgSecondHighs > $avgFirstHighs && $avgSecondLows > $avgFirstLows) {
            return 'bullish';
        }
        
        // Lower highs and lower lows = bearish
        if ($avgSecondHighs < $avgFirstHighs && $avgSecondLows < $avgFirstLows) {
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Get volume trend
     */
    private function getVolumeTrend(array $candles): string
    {
        if (count($candles) < 10) {
            return 'neutral';
        }
        
        $recentCandles = array_slice($candles, -10);
        $volumes = array_column($recentCandles, 'volume');
        
        $firstHalf = array_slice($volumes, 0, 5);
        $secondHalf = array_slice($volumes, 5);
        
        $avgFirstVolume = array_sum($firstHalf) / count($firstHalf);
        $avgSecondVolume = array_sum($secondHalf) / count($secondHalf);
        
        // Increasing volume = bullish, decreasing = bearish
        if ($avgSecondVolume > $avgFirstVolume * 1.1) { // 10% increase
            return 'bullish';
        } elseif ($avgSecondVolume < $avgFirstVolume * 0.9) { // 10% decrease
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Get swing trend (structural analysis)
     */
    private function getSwingTrend(array $candles): string
    {
        if (count($candles) < 15) {
            return 'neutral';
        }
        
        $recentCandles = array_slice($candles, -15);
        $closes = array_column($recentCandles, 'close');
        
        // Find local highs and lows
        $swingHighs = [];
        $swingLows = [];
        
        for ($i = 2; $i < count($closes) - 2; $i++) {
            // Swing high: higher than 2 candles before and after
            if ($closes[$i] > $closes[$i-1] && $closes[$i] > $closes[$i-2] && 
                $closes[$i] > $closes[$i+1] && $closes[$i] > $closes[$i+2]) {
                $swingHighs[] = $closes[$i];
            }
            
            // Swing low: lower than 2 candles before and after
            if ($closes[$i] < $closes[$i-1] && $closes[$i] < $closes[$i-2] && 
                $closes[$i] < $closes[$i+1] && $closes[$i] < $closes[$i+2]) {
                $swingLows[] = $closes[$i];
            }
        }
        
        // Need at least 2 swings to determine trend
        if (count($swingHighs) >= 2) {
            $lastHigh = end($swingHighs);
            $previousHigh = $swingHighs[count($swingHighs) - 2];
            
            if ($lastHigh > $previousHigh) {
                return 'bullish';
            } elseif ($lastHigh < $previousHigh) {
                return 'bearish';
            }
        }
        
        if (count($swingLows) >= 2) {
            $lastLow = end($swingLows);
            $previousLow = $swingLows[count($swingLows) - 2];
            
            if ($lastLow > $previousLow) {
                return 'bullish';
            } elseif ($lastLow < $previousLow) {
                return 'bearish';
            }
        }
        
        return 'neutral';
    }

    /**
     * Get momentum trend
     */
    private function getMomentumTrend(array $candles): string
    {
        if (count($candles) < 14) {
            return 'neutral';
        }
        
        $rsi = $this->calculateRSI($candles, 14);
        
        if (empty($rsi)) {
            return 'neutral';
        }
        
        $latestRSI = end($rsi);
        
        // RSI momentum interpretation
        if ($latestRSI > 55) {
            return 'bullish';
        } elseif ($latestRSI < 45) {
            return 'bearish';
        }
        
        return 'neutral';
    }

    /**
     * Calculate Exponential Moving Average
     */
    private function calculateEMA(array $prices, int $period): array
    {
        if (count($prices) < $period) {
            return [];
        }
        
        $ema = [];
        $multiplier = 2 / ($period + 1);
        
        // First EMA value is SMA
        $sma = array_sum(array_slice($prices, 0, $period)) / $period;
        $ema[0] = $sma;
        
        // Calculate EMA for rest of the values
        for ($i = 1; $i < count($prices) - $period + 1; $i++) {
            $ema[$i] = ($prices[$i + $period - 1] - $ema[$i - 1]) * $multiplier + $ema[$i - 1];
        }
        
        return $ema;
    }

    /**
     * Calculate confluence between multiple signals for the same direction
     */
    private function calculateScalpingConfluence(array $signals, string $direction): float
    {
        if (empty($signals)) {
            return 0.0;
        }
        
        // Filter signals for the same direction
        $sameDirectionSignals = array_filter($signals, function($signal) use ($direction) {
            return ($signal['direction'] ?? '') === $direction;
        });
        
        if (empty($sameDirectionSignals)) {
            return 0.0;
        }
        
        $confluenceScore = 0.0;
        $maxPossibleScore = 0.0;
        
        // Group signals by type for confluence analysis
        $signalTypes = [];
        foreach ($sameDirectionSignals as $signal) {
            $type = $signal['type'] ?? 'unknown';
            $signalTypes[$type][] = $signal;
        }
        
        // Calculate confluence based on different signal types
        $confluenceFactors = $this->getConfluenceFactors();
        
        foreach ($confluenceFactors as $factor => $weight) {
            $maxPossibleScore += $weight;
            
            switch ($factor) {
                case 'timeframe_confluence':
                    $confluenceScore += $this->calculateTimeframeConfluence($sameDirectionSignals) * $weight;
                    break;
                    
                case 'signal_type_diversity':
                    $confluenceScore += $this->calculateSignalTypeDiversity($signalTypes) * $weight;
                    break;
                    
                case 'signal_strength_alignment':
                    $confluenceScore += $this->calculateStrengthAlignment($sameDirectionSignals) * $weight;
                    break;
                    
                case 'volume_confirmation':
                    $confluenceScore += $this->calculateVolumeConfirmation($sameDirectionSignals) * $weight;
                    break;
                    
                case 'momentum_alignment':
                    $confluenceScore += $this->calculateMomentumAlignment($sameDirectionSignals) * $weight;
                    break;
            }
        }
        
        // Normalize to 0-1 scale
        return $maxPossibleScore > 0 ? min(1.0, $confluenceScore / $maxPossibleScore) : 0.0;
    }

    /**
     * Get confluence factor weights
     */
    private function getConfluenceFactors(): array
    {
        return [
            'timeframe_confluence' => 0.3,      // Multiple timeframes confirming
            'signal_type_diversity' => 0.25,    // Different signal types agreeing
            'signal_strength_alignment' => 0.2, // Strong signals in same direction
            'volume_confirmation' => 0.15,      // Volume supporting the move
            'momentum_alignment' => 0.1         // Momentum indicators aligning
        ];
    }

    /**
     * Calculate timeframe confluence score
     */
    private function calculateTimeframeConfluence(array $signals): float
    {
        $timeframes = [];
        foreach ($signals as $signal) {
            $tf = $signal['timeframe'] ?? 'unknown';
            $timeframes[$tf] = true;
        }
        
        $uniqueTimeframes = count($timeframes);
        
        // More timeframes = higher confluence
        if ($uniqueTimeframes >= 3) {
            return 1.0; // Excellent confluence
        } elseif ($uniqueTimeframes >= 2) {
            return 0.7; // Good confluence
        } elseif ($uniqueTimeframes >= 1) {
            return 0.3; // Basic signal
        }
        
        return 0.0;
    }

    /**
     * Calculate signal type diversity score
     */
    private function calculateSignalTypeDiversity(array $signalTypes): float
    {
        $typeCount = count($signalTypes);
        $diversityScore = 0.0;
        
        // Weight different signal types
        $typeWeights = [
            'engulfing' => 0.25,
            'bos' => 0.25,
            'choch' => 0.25,
            'order_block' => 0.20,
            'smart_money' => 0.30,
            'price_action_scalping' => 0.15,
            'momentum_scalping' => 0.20
        ];
        
        $totalWeight = 0.0;
        foreach ($signalTypes as $type => $typeSignals) {
            $weight = $typeWeights[$type] ?? 0.1;
            $totalWeight += $weight;
            
            // Bonus for multiple signals of same type
            $signalCount = count($typeSignals);
            $multiplier = min(1.0, $signalCount * 0.3);
            $diversityScore += $weight * $multiplier;
        }
        
        // Normalize and apply diversity bonus
        $baseScore = $totalWeight > 0 ? $diversityScore / $totalWeight : 0.0;
        $diversityBonus = min(0.3, ($typeCount - 1) * 0.1); // Bonus for having multiple types
        
        return min(1.0, $baseScore + $diversityBonus);
    }

    /**
     * Calculate signal strength alignment score
     */
    private function calculateStrengthAlignment(array $signals): float
    {
        if (empty($signals)) {
            return 0.0;
        }
        
        $strengths = array_column($signals, 'strength');
        $avgStrength = array_sum($strengths) / count($strengths);
        
        // Calculate how aligned the strengths are (low variance = high alignment)
        $variance = 0.0;
        foreach ($strengths as $strength) {
            $variance += pow($strength - $avgStrength, 2);
        }
        $variance /= count($strengths);
        
        // Convert variance to alignment score (lower variance = higher alignment)
        $alignmentScore = 1.0 - min(1.0, $variance * 4); // Scale variance to 0-1
        
        // Bonus for high average strength
        $strengthBonus = min(0.3, ($avgStrength - 0.5) * 0.6);
        
        return max(0.0, min(1.0, $alignmentScore + $strengthBonus));
    }

    /**
     * Calculate volume confirmation score
     */
    private function calculateVolumeConfirmation(array $signals): float
    {
        $volumeConfirmed = 0;
        $totalSignals = count($signals);
        
        foreach ($signals as $signal) {
            if (isset($signal['volume_confirmed']) && $signal['volume_confirmed']) {
                $volumeConfirmed++;
            }
        }
        
        return $totalSignals > 0 ? $volumeConfirmed / $totalSignals : 0.0;
    }

    /**
     * Calculate momentum alignment score
     */
    private function calculateMomentumAlignment(array $signals): float
    {
        $momentumAligned = 0;
        $totalSignals = count($signals);
        
        foreach ($signals as $signal) {
            // Check various momentum indicators
            $momentum = 0;
            
            if (isset($signal['trend_alignment']) && $signal['trend_alignment']) {
                $momentum += 0.4;
            }
            
            if (isset($signal['momentum_confirmed']) && $signal['momentum_confirmed']) {
                $momentum += 0.3;
            }
            
            if (isset($signal['scalping_score']) && $signal['scalping_score'] > 0.7) {
                $momentum += 0.3;
            }
            
            if ($momentum >= 0.5) {
                $momentumAligned++;
            }
        }
        
        return $totalSignals > 0 ? $momentumAligned / $totalSignals : 0.0;
    }

    // Additional helper methods would go here...
    // (calculateRSI, etc.)
    
    /**
     * Calculate RSI for momentum analysis
     */
    private function calculateRSI(array $candles, int $period = 14): array
    {
        if (count($candles) < $period + 1) {
            return [];
        }

        $rsi = [];
        $gains = [];
        $losses = [];

        // Calculate price changes
        for ($i = 1; $i < count($candles); $i++) {
            $change = $candles[$i]['close'] - $candles[$i-1]['close'];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        // Calculate initial averages
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // Calculate RSI
        for ($i = $period; $i < count($gains); $i++) {
            if ($avgLoss == 0) {
                $rsi[] = 100;
            } else {
                $rs = $avgGain / $avgLoss;
                $rsi[] = 100 - (100 / (1 + $rs));
            }

            // Update averages
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
        }

        return $rsi;
    }

    /**
     * Calculate scalping-specific position size
     */
    private function calculateScalpingPositionSize(float $currentPrice, array $signal): float
    {
        $maxPositionSize = $this->config['risk_management']['max_position_size'];
        $riskPercentage = $this->bot->risk_percentage / 100;
        
        // Get account balance
        $balance = $this->exchangeService->getAccountBalance();
        $availableBalance = $balance['USDT'] ?? 0;
        
        if ($availableBalance <= 0) {
            return 0;
        }

        // Calculate position size based on signal strength and scalping parameters
        $signalMultiplier = $signal['strength'] * $signal['scalping_score'];
        $basePositionSize = ($availableBalance * $riskPercentage * $signalMultiplier) / $currentPrice;
        
        return min($basePositionSize, $maxPositionSize);
    }

    private function getExchangeInterval(string $timeframe): string
    {
        $intervals = [
            '1m' => '1m',
            '5m' => '5m',
            '15m' => '15m',
            '30m' => '30m',
            '1h' => '1h',
            '4h' => '4h',
        ];

        return $intervals[$timeframe] ?? '5m';
    }
}

