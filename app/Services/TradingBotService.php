<?php

namespace App\Services;

use App\Models\TradingBot;
use App\Models\Trade;
use App\Models\Signal;
use Illuminate\Support\Facades\Log;
use App\Services\TradingBotLogger;
use Illuminate\Support\Facades\DB;

class TradingBotService
{
    private TradingBot $bot;
    private ExchangeService $exchangeService;
    private SmartMoneyConceptsService $smcService;
    private TradingBotLogger $logger;
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
            
            // Get current price
            $this->logger->info("💰 [PRICE] Fetching current price for {$this->bot->symbol}...");
            $currentPrice = $this->exchangeService->getCurrentPrice($this->bot->symbol);
            if (!$currentPrice) {
                $this->logger->error("❌ [PRICE] Failed to get current price for {$this->bot->symbol}");
                return;
            }
            $this->logger->info("✅ [PRICE] Current price: $currentPrice");
            
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
     * Process trading signals
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
     * Process individual signal
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
     * Handle new trading signal
     */
    private function handleNewSignal(array $signal, float $currentPrice): void
    {
        // Calculate position size
        $positionSize = $this->calculatePositionSize($currentPrice);
        
        if ($positionSize <= 0) {
            Log::warning("Insufficient balance for trade - Position size calculated as: {$positionSize}");
            return;
        }
        
        // Calculate stop loss and take profit
        $stopLoss = $this->calculateStopLoss($signal, $currentPrice);
        $takeProfit = $this->calculateTakeProfit($signal, $currentPrice);
        
        // Validate risk/reward ratio
        $riskRewardRatio = $this->calculateRiskRewardRatio($currentPrice, $stopLoss, $takeProfit);
        if ($riskRewardRatio < 1.5) {
            Log::info("Risk/reward ratio too low: {$riskRewardRatio}");
            return;
        }
        
        // Place the order
        $order = $this->placeOrder($signal, $positionSize);
        
        if ($order) {
            // Save trade to database
            $this->saveTrade($signal, $order, $currentPrice, $stopLoss, $takeProfit);
            
            // Save signal
            $this->saveSignal($signal, $currentPrice, $stopLoss, $takeProfit, $riskRewardRatio);
            
            Log::info("Order placed successfully: {$order['order_id']}");
        }
    }

    /**
     * Handle existing position
     */
    private function handleExistingPosition(Trade $trade, array $signal, float $currentPrice): void
    {
        // Check if we should close the position
        $shouldClose = $this->shouldClosePosition($trade, $signal, $currentPrice);
        
        if ($shouldClose) {
            $this->closePosition($trade, $currentPrice);
        }
    }

    /**
     * Calculate position size based on risk management
     */
    private function calculatePositionSize(float $currentPrice): float
    {
        $balance = $this->exchangeService->getBalance();
        $usdtBalance = 0;
        
        foreach ($balance as $bal) {
            if ($bal['currency'] === 'USDT') {
                $usdtBalance = $bal['available'];
                break;
            }
        }
        
        Log::info("Balance calculation: USDT Balance = {$usdtBalance}, Current Price = {$currentPrice}");
        
        if ($usdtBalance <= 0) {
            Log::warning("No USDT balance available");
            return 0;
        }
        
        // Calculate position size based on risk percentage
        $riskAmount = $usdtBalance * ($this->bot->risk_percentage / 100);
        $positionSize = $riskAmount / $currentPrice;
        
        Log::info("Position size calculation: Risk Amount = {$riskAmount} USDT, Position Size = {$positionSize} BTC");
        
        // Apply maximum position size limit
        $maxPositionSize = $this->bot->max_position_size;
        if ($positionSize > $maxPositionSize) {
            $positionSize = $maxPositionSize;
            Log::info("Position size limited by max position size: {$positionSize}");
        }
        
        // Check if position size is too small (likely below exchange minimum)
        if ($positionSize < 0.001) {
            Log::warning("Position size too small: {$positionSize} BTC. This may be below exchange minimum order size.");
        }
        
        return $positionSize;
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
            // For bearish signals, use a percentage below current price
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
        
        return $reward / $risk;
    }

    /**
     * Place order on exchange
     */
    private function placeOrder(array $signal, float $quantity): ?array
    {
        $side = $signal['direction'] === 'bullish' ? 'buy' : 'sell';
        
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
        Trade::create([
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
            return true;
        }
        
        if ($trade->side === 'sell' && $currentPrice >= $trade->stop_loss) {
            return true;
        }
        
        // Check take profit
        if ($trade->side === 'buy' && $currentPrice >= $trade->take_profit) {
            return true;
        }
        
        if ($trade->side === 'sell' && $currentPrice <= $trade->take_profit) {
            return true;
        }
        
        // Check for opposite signal
        if ($signal['direction'] !== ($trade->side === 'buy' ? 'bullish' : 'bearish')) {
            return true;
        }
        
        return false;
    }

    /**
     * Close position
     */
    private function closePosition(Trade $trade, float $currentPrice): void
    {
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
            
            Log::info("Position closed: P&L = {$profitLoss} ({$profitLossPercentage}%)");
        }
    }
}
