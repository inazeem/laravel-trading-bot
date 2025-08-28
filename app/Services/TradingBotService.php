<?php

namespace App\Services;

use App\Models\TradingBot;
use App\Models\Trade;
use App\Models\Signal;
use App\Models\UserAssetHolding;
use App\Models\Asset;
use Illuminate\Support\Facades\Log;
use App\Services\TradingBotLogger;
use App\Services\AssetHoldingsService;
use Illuminate\Support\Facades\DB;

class TradingBotService
{
    private TradingBot $bot;
    private ExchangeService $exchangeService;
    private SmartMoneyConceptsService $smcService;
    private TradingBotLogger $logger;
    private AssetHoldingsService $holdingsService;
    private array $timeframeIntervals = [
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
                '1h' => '1hour',
                '4h' => '4hour',
                '1d' => '1day'
            ];
            return $kucoinIntervals[$timeframe] ?? $timeframe;
        }
        
        // Binance and other exchanges use standard formats
        return $this->timeframeIntervals[$timeframe] ?? $timeframe;
    }

    public function __construct(TradingBot $bot)
    {
        $this->bot = $bot->load('apiKey');
        $this->exchangeService = new ExchangeService($bot->apiKey);
        $this->logger = new TradingBotLogger($bot);
        $this->holdingsService = new AssetHoldingsService();
    }

    /**
     * Run the trading bot
     */
    public function run(): void
    {
        try {
            $this->logger->info("🚀 [BOT START] Trading bot '{$this->bot->name}' starting execution");
            $this->logger->info("📊 [CONFIG] Symbol: {$this->bot->symbol}, Exchange: {$this->bot->exchange}");
            $this->logger->info("⚙️ [CONFIG] Risk: {$this->bot->risk_percentage}%, Max Position: {$this->bot->max_position_size}");
            $this->logger->info("⏰ [CONFIG] Timeframes: " . implode(', ', $this->bot->timeframes));
            
            // Update bot status
            $this->bot->update(['status' => 'running', 'last_run_at' => now()]);
            
            // Sync assets with exchange first
            $this->logger->info("🔄 [ASSET SYNC] Starting asset synchronization with exchange...");
            $this->syncAssetsWithExchange();
            
            // Check USDT balance before proceeding
            $usdtBalance = $this->getUSDTBalance();
            $this->logger->info("💰 [USDT BALANCE] Current USDT balance: {$usdtBalance}");
            
            if ($usdtBalance <= 0) {
                $this->logger->warning("⚠️ [USDT BALANCE] No USDT balance available - skipping signal processing");
                $this->bot->update(['status' => 'idle']);
                return;
            }
            
            // Get current price
            $this->logger->info("💰 [PRICE] Fetching current price for {$this->bot->symbol}...");
            $currentPrice = $this->exchangeService->getCurrentPrice($this->bot->symbol);
            if (!$currentPrice) {
                $this->logger->error("❌ [PRICE] Failed to get current price for {$this->bot->symbol}");
                return;
            }
            $this->logger->info("✅ [PRICE] Current price: $currentPrice");
            
            // Check if we're in cooldown period
            if ($this->isInCooldownPeriod()) {
                $this->logger->info("⏰ [COOLDOWN] Bot is in 3-hour cooldown period - skipping signal processing");
                $this->bot->update(['status' => 'idle']);
                return;
            }
            
            // Analyze all timeframes
            $this->logger->info("🔍 [ANALYSIS] Starting Smart Money Concepts analysis...");
            $signals = $this->analyzeAllTimeframes($currentPrice);
            
            // Process signals
            $this->logger->info("📈 [SIGNALS] Processing " . count($signals) . " total signals...");
            $this->processSignals($signals, $currentPrice);
            
            // Update bot status
            $this->bot->update(['status' => 'idle']);
            
            $this->logger->info("✅ [BOT END] Trading bot '{$this->bot->name}' completed successfully");
            
        } catch (\Exception $e) {
            $this->logger->error("❌ [ERROR] Error running trading bot {$this->bot->name}: " . $e->getMessage());
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
        
        $this->logger->info("📊 [TIMEFRAMES] Analyzing " . count($this->bot->timeframes) . " timeframes...");
        
        foreach ($this->bot->timeframes as $timeframe) {
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
            
            $this->logger->info("📊 [SIGNALS] Generated " . count($signals) . " signals for {$timeframe} timeframe");
            
            // Log signal details
            foreach ($signals as $index => $signal) {
                $price = $signal['price'] ?? $signal['level'] ?? 'N/A';
                $this->logger->info("📋 [SIGNAL {$index}] Type: {$signal['type']}, Direction: {$signal['direction']}, Strength: {$signal['strength']}, Price: {$price}");
                
                // Add timeframe to signal
                $signal['timeframe'] = $timeframe;
                $allSignals[] = $signal;
            }

            // Forecast high/low range for short-term horizon and log it
            try {
                $forecastHorizon = (int) config('micro_trading.signal_settings.max_trade_duration_hours', 4);
                $forecastService = new \App\Services\HighLowForecastService($candles);
                $forecast = $forecastService->forecastRange(max(1, $forecastHorizon));
                $this->logger->info("🔮 [FORECAST] {$timeframe} horizon {$forecastHorizon} -> Low: {$forecast['min']}, High: {$forecast['max']}, Conf: {$forecast['confidence']}");
            } catch (\Throwable $e) {
                $this->logger->warning("⚠️ [FORECAST] Forecast failed for {$timeframe}: " . $e->getMessage());
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
     * Process trading signals with enhanced strength-based filtering
     */
    private function processSignals(array $signals, float $currentPrice): void
    {
        if (empty($signals)) {
            $this->logger->info("📭 [SIGNALS] No trading signals generated - no action needed");
            return;
        }
        
        $requiredStrength = config('micro_trading.signal_settings.high_strength_requirement', 0.70);
        $this->logger->info("🔍 [FILTER] Filtering and ranking " . count($signals) . " signals with " . ($requiredStrength * 100) . "%+ strength requirement...");
        
        // Log all signals before filtering
        foreach ($signals as $index => $signal) {
            $this->logger->info("🔍 [PRE-FILTER] Signal {$index}: Type={$signal['type']}, Direction={$signal['direction']}, Strength={$signal['strength']}, Timeframe={$signal['timeframe']}");
        }
        
        // Filter signals with 70%+ strength requirement
        $filteredSignals = $this->filterSignalsByStrength($signals);
        
        $this->logger->info("✅ [FILTER] " . count($filteredSignals) . " signals passed required strength");
        
        // Log filtered signals
        foreach ($filteredSignals as $index => $signal) {
            $this->logger->info("✅ [FILTERED] Signal {$index}: Type={$signal['type']}, Direction={$signal['direction']}, Strength={$signal['strength']}, Timeframe={$signal['timeframe']}");
        }
        
        // Process only the strongest signal in each direction
        if (!empty($filteredSignals)) {
            $strongestSignal = $this->getStrongestSignal($filteredSignals);
            $this->logger->info("🎯 [PROCESS] Processing strongest signal with strength: {$strongestSignal['strength']}");
            $this->processSignal($strongestSignal, $currentPrice);
        } else {
            $this->logger->info("⚠️ [FILTER] No signals met the required strength - no trades will be placed");
            // Record the top candidate below threshold for diagnostics
            if (!empty($signals)) {
                usort($signals, function($a, $b) {
                    $sa = $a['strength'] ?? 0; $sb = $b['strength'] ?? 0;
                    return $sb <=> $sa;
                });
                $top = $signals[0];
                $this->logger->info("📝 [DIAG] Top rejected signal: type={$top['type']}, dir={$top['direction']}, strength=" . ($top['strength'] ?? 0) . ", timeframe=" . ($top['timeframe'] ?? 'n/a'));
            }
        }
    }

    /**
     * Filter signals by strength - only accept signals with 70%+ strength
     */
    private function filterSignalsByStrength(array $signals): array
    {
        $filtered = [];
        $minStrength = (float) config('micro_trading.signal_settings.high_strength_requirement', 0.70);
        
        foreach ($signals as $signal) {
            $strength = $signal['strength'] ?? 0;
            
            // Handle different strength formats (normalized 0-1 or percentage 0-100)
            $normalizedStrength = $strength;
            if ($strength > 1 && $strength <= 100) {
                // If strength is a percentage (0-100), normalize to 0-1
                $normalizedStrength = $strength / 100;
            } elseif ($strength > 100) {
                // If strength is a very large number, use a minimum threshold
                $normalizedStrength = 0.1; // Minimum threshold for very large values
            }
            
            $this->logger->info("🔍 [STRENGTH CHECK] Signal strength: {$strength} (normalized: {$normalizedStrength})");
            
            // Only accept signals meeting configured strength
            if ($normalizedStrength >= $minStrength) {
                $signal['normalized_strength'] = $normalizedStrength;
                $filtered[] = $signal;
                $this->logger->info("✅ [STRENGTH CHECK] Signal passed strength requirement: {$normalizedStrength} >= {$minStrength}");
            } else {
                $this->logger->info("❌ [STRENGTH CHECK] Signal rejected - strength too low: {$normalizedStrength} < {$minStrength}");
            }
        }
        
        // Sort by strength (highest first)
        usort($filtered, function($a, $b) {
            return ($b['normalized_strength'] ?? 0) <=> ($a['normalized_strength'] ?? 0);
        });
        
        return $filtered;
    }

    /**
     * Get the strongest signal from the filtered list
     */
    private function getStrongestSignal(array $signals): array
    {
        if (empty($signals)) {
            return [];
        }
        
        // Return the first signal (already sorted by strength)
        return $signals[0];
    }

    /**
     * Process individual signal with 10% position sizing
     */
    private function processSignal(array $signal, float $currentPrice): void
    {
        $this->logger->info("🔄 [PROCESS SIGNAL] Processing signal: " . json_encode($signal));
        
        // Check if we already have an open position
        $openTrade = $this->getOpenTrade();
        
        if ($openTrade) {
            $this->logger->info("📊 [EXISTING POSITION] Found open trade - handling existing position");
            $this->handleExistingPosition($openTrade, $signal, $currentPrice);
        } else {
            $this->logger->info("🆕 [NO OPEN POSITION] No open trade found - handling new signal");
            $this->handleNewSignal($signal, $currentPrice);
        }
    }

    /**
     * Handle new trading signal with 10% position sizing
     */
    private function handleNewSignal(array $signal, float $currentPrice): void
    {
        $this->logger->info("🚀 [NEW SIGNAL] Starting to process new signal with 70%+ strength: " . json_encode($signal));
        
        // Check if we're in cooldown period after closing a position
        if ($this->isInCooldownPeriod()) {
            $this->logger->info("⏰ [COOLDOWN] Skipping new signal - bot is in 3-hour cooldown period after recent position closure");
            return;
        }
        
        $this->logger->info("✅ [COOLDOWN] Not in cooldown period - proceeding");
        
        // Check balance based on signal direction
        if ($signal['direction'] === 'bullish') {
            // For bullish signals, check USDT balance for buying
            $usdtBalance = $this->getUSDTBalance();
            if ($usdtBalance <= 0) {
                $this->logger->warning("❌ [USDT BALANCE] No USDT balance available for buy order - skipping bullish signal");
                return;
            }
            $this->logger->info("✅ [USDT BALANCE] USDT balance available for buy: {$usdtBalance}");
        } else {
            // For bearish signals, check if we have enough asset to sell
            $assetSymbol = $this->extractAssetSymbol($this->bot->symbol);
            $userHolding = $this->holdingsService->getCurrentHoldings($this->bot->user_id, $assetSymbol);
            
            if (!$userHolding || $userHolding->quantity <= 0) {
                $this->logger->warning("❌ [ASSET BALANCE] No {$assetSymbol} holdings available for sell order - skipping bearish signal");
                return;
            }
            $this->logger->info("✅ [ASSET BALANCE] {$assetSymbol} holdings available for sell: {$userHolding->quantity}");
        }
        
        // Calculate 10% position size based on signal direction
        $positionSize = $this->calculateTenPercentPositionSize($currentPrice, $signal['direction']);
        
        if ($positionSize <= 0) {
            $this->logger->warning("❌ [POSITION SIZE] Insufficient balance or holdings for 10% trade - Position size calculated as: {$positionSize}");
            return;
        }
        
        $this->logger->info("✅ [POSITION SIZE] 10% position size calculated: {$positionSize}");
        
        // Calculate stop loss and take profit
        $stopLoss = $this->calculateStopLoss($signal, $currentPrice);
        $takeProfit = $this->calculateTakeProfit($signal, $currentPrice);
        
        $this->logger->info("📊 [RISK MANAGEMENT] Stop Loss: {$stopLoss}, Take Profit: {$takeProfit}");
        
        // Validate risk/reward ratio
        $riskRewardRatio = $this->calculateRiskRewardRatio($currentPrice, $stopLoss, $takeProfit);
        $this->logger->info("📈 [RISK/REWARD] Risk/Reward Ratio: {$riskRewardRatio}");
        
        if ($riskRewardRatio < 1.5) {
            $this->logger->info("❌ [RISK/REWARD] Risk/reward ratio too low: {$riskRewardRatio} (minimum: 1.5)");
            return;
        }
        
        $this->logger->info("✅ [RISK/REWARD] Risk/reward ratio acceptable, placing order...");
        
        // Place the order
        $order = $this->placeOrder($signal, $positionSize);
        
        if ($order) {
            // Save trade to database
            $this->saveTrade($signal, $order, $currentPrice, $stopLoss, $takeProfit);
            
            // Save signal
            $this->saveSignal($signal, $currentPrice, $stopLoss, $takeProfit, $riskRewardRatio);
            
            $this->logger->info("✅ [ORDER] Order placed successfully: {$order['order_id']}");
            
            // Set cooldown period after placing trade
            $this->setCooldownPeriod();
            $this->logger->info("⏰ [COOLDOWN] 3-hour cooldown period activated after placing trade");
        } else {
            $this->logger->error("❌ [ORDER] Failed to place order");
        }
    }

    /**
     * Calculate 10% position size based on signal direction and available balance
     */
    private function calculateTenPercentPositionSize(float $currentPrice, string $signalDirection = null): float
    {
        $assetSymbol = $this->extractAssetSymbol($this->bot->symbol);
        
        if ($signalDirection === 'bullish') {
            // For bullish signals, calculate 10% of USDT balance
            return $this->calculateBuyPositionSize($currentPrice);
        } elseif ($signalDirection === 'bearish') {
            // For bearish signals, calculate 10% of current asset holdings
            $userHolding = $this->holdingsService->getCurrentHoldings($this->bot->user_id, $assetSymbol);
            
            if (!$userHolding || $userHolding->quantity <= 0) {
                $this->logger->warning("No {$assetSymbol} holdings available for sell order");
                return 0;
            }
            
            $currentHoldings = $userHolding->quantity;
            $this->logger->info("Current holdings for sell: {$currentHoldings} {$assetSymbol}");
            
            // Calculate 10% of current holdings
            $tenPercentSize = $currentHoldings * 0.10;
            $this->logger->info("10% sell position size: {$currentHoldings} * 0.10 = {$tenPercentSize} {$assetSymbol}");
            
            // Ensure minimum order size
            $minOrderSize = $this->getMinimumOrderSize();
            if ($tenPercentSize < $minOrderSize) {
                $this->logger->info("Sell position size below minimum ({$minOrderSize}), using minimum: {$minOrderSize}");
                $tenPercentSize = $minOrderSize;
            }
            
            // Ensure we don't exceed current holdings
            if ($tenPercentSize > $currentHoldings) {
                $this->logger->info("Sell position size exceeds holdings, using current holdings: {$currentHoldings}");
                $tenPercentSize = $currentHoldings;
            }
            
            return $tenPercentSize;
        } else {
            // Fallback for unknown direction
            $this->logger->warning("Unknown signal direction for position sizing");
            return 0;
        }
        
        return $tenPercentSize;
    }

    /**
     * Calculate buy position size using USDT balance
     */
    private function calculateBuyPositionSize(float $currentPrice): float
    {
        $usdtBalance = $this->getUSDTBalance();
        
        $this->logger->info("USDT Balance calculation: USDT Balance = {$usdtBalance}, Current Price = {$currentPrice}");
        
        if ($usdtBalance <= 0) {
            $this->logger->warning("No USDT balance available for buy order");
            return 0;
        }
        
        // Calculate 10% of USDT balance
        $tenPercentUsdt = $usdtBalance * 0.10;
        $positionSize = $tenPercentUsdt / $currentPrice;
        
        $this->logger->info("Buy position size calculation: 10% USDT = {$tenPercentUsdt} USDT, Position Size = {$positionSize}");
        
        // Apply maximum position size limit
        $maxPositionSize = $this->bot->max_position_size;
        if ($positionSize > $maxPositionSize) {
            $positionSize = $maxPositionSize;
            $this->logger->info("Position size limited by max position size: {$positionSize}");
        }
        
        return $positionSize;
    }

    /**
     * Extract asset symbol from trading pair (e.g., "BTC-USDT" -> "BTC")
     */
    private function extractAssetSymbol(string $tradingPair): string
    {
        return explode('-', $tradingPair)[0];
    }

    /**
     * Check if we should buy the asset (bullish signal)
     */
    private function shouldBuyAsset(): bool
    {
        // This will be determined by the signal direction
        return true; // Default to true, will be overridden by signal processing
    }

    /**
     * Check if we should sell the asset (bearish signal)
     */
    private function shouldSellAsset(): bool
    {
        // This will be determined by the signal direction
        return false; // Default to false, will be overridden by signal processing
    }

    /**
     * Get minimum order size for the exchange
     */
    private function getMinimumOrderSize(): float
    {
        // Default minimum order sizes (can be configured per exchange)
        $minSizes = [
            'BTC' => 0.001,
            'ETH' => 0.01,
            'USDT' => 10,
        ];
        
        $assetSymbol = $this->extractAssetSymbol($this->bot->symbol);
        return $minSizes[$assetSymbol] ?? 0.001; // Default to 0.001
    }

    /**
     * Handle existing position
     */
    private function handleExistingPosition(Trade $trade, array $signal, float $currentPrice): void
    {
        $this->logger->info("📊 [EXISTING POSITION] Monitoring existing position: " . json_encode($trade->toArray()));
        
        // Check if we should close the position
        $shouldClose = $this->shouldClosePosition($trade, $signal, $currentPrice);
        
        if ($shouldClose) {
            $this->logger->info("🔴 [CLOSE] Position closing conditions met - closing position");
            $this->closePosition($trade, $currentPrice);
        } else {
            $this->logger->info("✅ [HOLD] Position conditions stable - continuing to monitor");
        }
    }

    /**
     * Calculate stop loss level
     */
    private function calculateStopLoss(array $signal, float $currentPrice): float
    {
        $supportLevels = $this->smcService->getSupportResistanceLevels();
        $supportLevels = array_filter($supportLevels, fn($level) => $level['type'] === 'support');
        
        if ($signal['direction'] === 'bullish') {
            // Find nearest support below current price
            $nearestSupport = null;
            foreach ($supportLevels as $level) {
                if ($level['price'] < $currentPrice && (!$nearestSupport || $level['price'] > $nearestSupport['price'])) {
                    $nearestSupport = $level;
                }
            }
            
            return $nearestSupport ? $nearestSupport['price'] : $currentPrice * 0.95;
        } else {
            // For bearish signals, use a percentage above current price
            return $currentPrice * 1.05;
        }
    }

    /**
     * Calculate take profit level
     */
    private function calculateTakeProfit(array $signal, float $currentPrice): float
    {
        $resistanceLevels = $this->smcService->getSupportResistanceLevels();
        $resistanceLevels = array_filter($resistanceLevels, fn($level) => $level['type'] === 'resistance');
        
        if ($signal['direction'] === 'bullish') {
            // Find nearest resistance above current price
            $nearestResistance = null;
            foreach ($resistanceLevels as $level) {
                if ($level['price'] > $currentPrice && (!$nearestResistance || $level['price'] < $nearestResistance['price'])) {
                    $nearestResistance = $level;
                }
            }
            
            return $nearestResistance ? $nearestResistance['price'] : $currentPrice * 1.15;
        } else {
            // For bearish signals, use a percentage below current price
            return $currentPrice * 0.85;
        }
    }

    /**
     * Calculate risk/reward ratio
     */
    private function calculateRiskRewardRatio(float $entryPrice, float $stopLoss, float $takeProfit): float
    {
        $risk = abs($entryPrice - $stopLoss);
        $reward = abs($takeProfit - $entryPrice);
        
        if ($risk == 0) {
            return 0;
        }
        
        return $reward / $risk;
    }

    /**
     * Place order on exchange
     */
    private function placeOrder(array $signal, float $quantity): ?array
    {
        $side = $signal['direction'] === 'bullish' ? 'buy' : 'sell';
        
        $this->logger->info("📤 [ORDER] Placing {$side} order for {$quantity} {$this->bot->symbol}");
        
        return $this->exchangeService->placeMarketOrder(
            $this->bot->symbol,
            $side,
            $quantity
        );
    }

    /**
     * Save trade to database
     */
    private function saveTrade(array $signal, array $order, float $price, float $stopLoss, float $takeProfit): void
    {
        $trade = Trade::create([
            'trading_bot_id' => $this->bot->id,
            'exchange_order_id' => $order['order_id'],
            'side' => $order['side'],
            'symbol' => $order['symbol'],
            'quantity' => $order['quantity'],
            'price' => $price,
            'total' => $order['quantity'] * $price,
            'status' => 'open',
            'signal_type' => $signal['type'],
            'entry_time' => now(),
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'notes' => json_encode($signal)
        ]);
        
        // Update asset holdings after trade
        $this->holdingsService->updateHoldingsAfterTrade($trade);
    }

    /**
     * Save signal to database
     */
    private function saveSignal(array $signal, float $price, float $stopLoss, float $takeProfit, float $riskRewardRatio): void
    {
        Signal::create([
            'trading_bot_id' => $this->bot->id,
            'signal_type' => $signal['type'],
            'timeframe' => $signal['timeframe'],
            'symbol' => $this->bot->symbol,
            'price' => $price,
            'strength' => $signal['strength'] ?? 0,
            'direction' => $signal['direction'],
            'support_level' => $signal['support_level'] ?? null,
            'resistance_level' => $signal['resistance_level'] ?? null,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'risk_reward_ratio' => $riskRewardRatio,
            'is_executed' => true,
            'executed_at' => now(),
            'notes' => json_encode($signal)
        ]);
    }

    /**
     * Get open trade for this bot
     */
    private function getOpenTrade(): ?Trade
    {
        return Trade::where('trading_bot_id', $this->bot->id)
            ->where('status', 'open')
            ->latest()
            ->first();
    }

    /**
     * Check if position should be closed
     */
    private function shouldClosePosition(Trade $trade, array $signal, float $currentPrice): bool
    {
        // Check stop loss
        if ($trade->side === 'buy' && $currentPrice <= $trade->stop_loss) {
            $this->logger->info("🔴 [STOP LOSS] Stop loss triggered for buy position at {$currentPrice}");
            return true;
        }
        
        if ($trade->side === 'sell' && $currentPrice >= $trade->stop_loss) {
            $this->logger->info("🔴 [STOP LOSS] Stop loss triggered for sell position at {$currentPrice}");
            return true;
        }
        
        // Check take profit
        if ($trade->side === 'buy' && $currentPrice >= $trade->take_profit) {
            $this->logger->info("🟢 [TAKE PROFIT] Take profit triggered for buy position at {$currentPrice}");
            return true;
        }
        
        if ($trade->side === 'sell' && $currentPrice <= $trade->take_profit) {
            $this->logger->info("🟢 [TAKE PROFIT] Take profit triggered for sell position at {$currentPrice}");
            return true;
        }
        
        // Check for opposite signal (optional - can be disabled for longer holds)
        if ($signal['direction'] !== ($trade->side === 'buy' ? 'bullish' : 'bearish')) {
            $this->logger->info("🔄 [OPPOSITE SIGNAL] Opposite signal detected - considering position closure");
            // Only close on opposite signal if it's also strong (70%+)
            if (($signal['strength'] ?? 0) >= 0.70) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Close position
     */
    private function closePosition(Trade $trade, float $currentPrice): void
    {
        $this->logger->info("🔴 [CLOSE POSITION] Starting position closure for trade ID: {$trade->id}");
        
        $side = $trade->side === 'buy' ? 'sell' : 'buy';
        
        $order = $this->exchangeService->placeMarketOrder(
            $trade->symbol,
            $side,
            $trade->quantity
        );
        
        if ($order) {
            $profitLoss = ($currentPrice - $trade->price) * $trade->quantity;
            if ($trade->side === 'sell') {
                $profitLoss = -$profitLoss;
            }
            
            $profitLossPercentage = ($profitLoss / ($trade->price * $trade->quantity)) * 100;
            
            $trade->update([
                'status' => 'closed',
                'exit_time' => now(),
                'profit_loss' => $profitLoss,
                'profit_loss_percentage' => $profitLossPercentage
            ]);
            
            $this->logger->info("✅ [CLOSE POSITION] Position closed successfully: P&L = {$profitLoss} ({$profitLossPercentage}%)");
            
            // Set cooldown period after closing position
            $this->setCooldownPeriod();
            $this->logger->info("⏰ [COOLDOWN] 3-hour cooldown period activated after closing position");
        } else {
            $this->logger->error("❌ [CLOSE POSITION] Failed to close position on exchange");
        }
    }

    /**
     * Set cooldown period after placing or closing a position
     */
    private function setCooldownPeriod(): void
    {
        $this->bot->update([
            'last_trade_at' => now(),
        ]);
    }

    /**
     * Check if bot is in cooldown period (3 hours)
     */
    private function isInCooldownPeriod(): bool
    {
        if (!$this->bot->last_trade_at) {
            return false;
        }
        
        $cooldownHours = 3; // 3-hour cooldown as requested
        $cooldownEnd = $this->bot->last_trade_at->addHours($cooldownHours);
        
        $isInCooldown = now()->lt($cooldownEnd);
        
        if ($isInCooldown) {
            $remainingMinutes = now()->diffInMinutes($cooldownEnd);
            $this->logger->info("⏰ [COOLDOWN] Bot is in cooldown period - {$remainingMinutes} minutes remaining");
        }
        
        return $isInCooldown;
    }

    /**
     * Sync assets with the exchange to get the latest holdings and USDT balance.
     */
    private function syncAssetsWithExchange(): void
    {
        $this->logger->info("🔄 [ASSET SYNC] Syncing assets with exchange for user ID: {$this->bot->user_id}");
        $this->holdingsService->syncAssetsWithExchange($this->bot->user_id);
        $this->logger->info("✅ [ASSET SYNC] Asset synchronization complete.");
    }

    /**
     * Get the current USDT balance from the exchange.
     */
    private function getUSDTBalance(): float
    {
        $this->logger->info("💰 [USDT BALANCE] Fetching current USDT balance from exchange for user ID: {$this->bot->user_id}");
        $balance = $this->exchangeService->getBalance();
        $usdtBalance = 0;
        foreach ($balance as $bal) {
            $currency = $bal['currency'] ?? $bal['asset'] ?? null;
            if ($currency === 'USDT') {
                $usdtBalance = (float) ($bal['available'] ?? $bal['free'] ?? 0);
                break;
            }
        }
        $this->logger->info("💰 [USDT BALANCE] Current USDT balance: {$usdtBalance}");
        return $usdtBalance;
    }
}
