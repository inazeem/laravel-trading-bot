<?php

namespace App\Services;

use App\Models\TradingStrategy;
use App\Models\BotStrategy;
use App\Models\FuturesTradingBot;
use App\Models\TradingBot;
use Illuminate\Support\Facades\Log;

class StrategyService
{
    /**
     * Execute strategy logic for a bot
     */
    public function executeStrategy($bot): array
    {
        $botStrategies = $this->getActiveBotStrategies($bot);
        $results = [];
        
        foreach ($botStrategies as $botStrategy) {
            try {
                $strategyResult = $this->executeStrategyLogic($botStrategy, $bot);
                $results[] = [
                    'strategy' => $botStrategy->strategy->name,
                    'result' => $strategyResult,
                    'success' => true
                ];
            } catch (\Exception $e) {
                Log::error("Strategy execution failed for {$botStrategy->strategy->name}: " . $e->getMessage());
                $results[] = [
                    'strategy' => $botStrategy->strategy->name,
                    'result' => null,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get active strategies for a bot
     */
    public function getActiveBotStrategies($bot): \Illuminate\Database\Eloquent\Collection
    {
        $botType = $bot instanceof FuturesTradingBot ? 'App\Models\FuturesTradingBot' : 'App\Models\TradingBot';
        
        return BotStrategy::with(['strategy', 'strategy.parameters'])
            ->where('bot_type', $botType)
            ->where('bot_id', $bot->id)
            ->where('is_active', true)
            ->ordered()
            ->get();
    }

    /**
     * Execute specific strategy logic
     */
    private function executeStrategyLogic(BotStrategy $botStrategy, $bot): array
    {
        $strategy = $botStrategy->strategy;
        $parameters = $botStrategy->getMergedParameters();
        
        // Get current market data
        $marketData = $this->getMarketData($bot);
        
        // Execute strategy based on type
        switch ($strategy->type) {
            case 'trend_following':
                return $this->executeTrendFollowingStrategy($parameters, $marketData, $bot);
                
            case 'mean_reversion':
                return $this->executeMeanReversionStrategy($parameters, $marketData, $bot);
                
            case 'momentum':
                return $this->executeMomentumStrategy($parameters, $marketData, $bot);
                
            case 'scalping':
                return $this->executeScalpingStrategy($parameters, $marketData, $bot);
                
            case 'swing_trading':
                return $this->executeSwingTradingStrategy($parameters, $marketData, $bot);
                
            case 'grid_trading':
                return $this->executeGridTradingStrategy($parameters, $marketData, $bot);
                
            case 'dca':
                return $this->executeDCAStrategy($parameters, $marketData, $bot);
                
            case 'custom':
                return $this->executeCustomStrategy($parameters, $marketData, $bot);
                
            case 'smart_money_concept':
                return $this->executeSMCStrategy($parameters, $marketData, $bot);
                
            default:
                throw new \Exception("Unknown strategy type: {$strategy->type}");
        }
    }

    /**
     * Get market data for strategy analysis
     */
    private function getMarketData($bot): array
    {
        $exchangeService = new ExchangeService($bot->apiKey);
        
        return [
            'current_price' => $exchangeService->getCurrentPrice($bot->symbol),
            'timeframes' => $bot->timeframes ?? ['1h', '4h', '1d'],
            'symbol' => $bot->symbol,
            'exchange' => $bot->exchange,
            'balance' => $exchangeService->getBalance(),
            'futures_balance' => method_exists($exchangeService, 'getFuturesBalance') ? 
                $exchangeService->getFuturesBalance() : null,
        ];
    }

    /**
     * Trend Following Strategy
     */
    private function executeTrendFollowingStrategy(array $params, array $marketData, $bot): array
    {
        $signalStrength = $params['signal_strength'] ?? 70;
        $trendPeriod = $params['trend_period'] ?? 20;
        $rsiOversold = $params['rsi_oversold'] ?? 30;
        $rsiOverbought = $params['rsi_overbought'] ?? 70;
        
        // Simple trend following logic
        $trendDirection = $this->calculateTrendDirection($marketData, $trendPeriod);
        $rsi = $this->calculateRSI($marketData, 14);
        
        $action = 'hold';
        $confidence = 0;
        
        if ($trendDirection === 'up' && $rsi < $rsiOverbought) {
            $action = 'buy';
            $confidence = min(100, $signalStrength + ($rsiOverbought - $rsi));
        } elseif ($trendDirection === 'down' && $rsi > $rsiOversold) {
            $action = 'sell';
            $confidence = min(100, $signalStrength + ($rsi - $rsiOversold));
        }
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'reason' => "Trend: {$trendDirection}, RSI: {$rsi}",
            'parameters' => [
                'trend_direction' => $trendDirection,
                'rsi' => $rsi,
                'signal_strength' => $signalStrength
            ]
        ];
    }

    /**
     * Mean Reversion Strategy
     */
    private function executeMeanReversionStrategy(array $params, array $marketData, $bot): array
    {
        $bollingerPeriod = $params['bollinger_period'] ?? 20;
        $bollingerStd = $params['bollinger_std'] ?? 2;
        $rsiOversold = $params['rsi_oversold'] ?? 30;
        $rsiOverbought = $params['rsi_overbought'] ?? 70;
        
        $bollinger = $this->calculateBollingerBands($marketData, $bollingerPeriod, $bollingerStd);
        $rsi = $this->calculateRSI($marketData, 14);
        $currentPrice = $marketData['current_price'];
        
        $action = 'hold';
        $confidence = 0;
        
        if ($currentPrice <= $bollinger['lower'] && $rsi < $rsiOversold) {
            $action = 'buy';
            $confidence = 85; // Strong mean reversion signal
        } elseif ($currentPrice >= $bollinger['upper'] && $rsi > $rsiOverbought) {
            $action = 'sell';
            $confidence = 85;
        }
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'reason' => "Price: {$currentPrice}, BB Lower: {$bollinger['lower']}, BB Upper: {$bollinger['upper']}, RSI: {$rsi}",
            'parameters' => [
                'bollinger_bands' => $bollinger,
                'rsi' => $rsi,
                'current_price' => $currentPrice
            ]
        ];
    }

    /**
     * Momentum Strategy
     */
    private function executeMomentumStrategy(array $params, array $marketData, $bot): array
    {
        $momentumPeriod = $params['momentum_period'] ?? 10;
        $volumeThreshold = $params['volume_threshold'] ?? 1.5;
        
        $momentum = $this->calculateMomentum($marketData, $momentumPeriod);
        $volumeRatio = $this->calculateVolumeRatio($marketData, $momentumPeriod);
        
        $action = 'hold';
        $confidence = 0;
        
        if ($momentum > 0 && $volumeRatio > $volumeThreshold) {
            $action = 'buy';
            $confidence = min(100, 60 + ($momentum * 10) + ($volumeRatio * 20));
        } elseif ($momentum < 0 && $volumeRatio > $volumeThreshold) {
            $action = 'sell';
            $confidence = min(100, 60 + (abs($momentum) * 10) + ($volumeRatio * 20));
        }
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'reason' => "Momentum: {$momentum}, Volume Ratio: {$volumeRatio}",
            'parameters' => [
                'momentum' => $momentum,
                'volume_ratio' => $volumeRatio
            ]
        ];
    }

    /**
     * Scalping Strategy
     */
    private function executeScalpingStrategy(array $params, array $marketData, $bot): array
    {
        $emaFast = $params['ema_fast'] ?? 5;
        $emaSlow = $params['ema_slow'] ?? 20;
        $profitTarget = $params['profit_target'] ?? 0.5; // 0.5%
        
        $emaFastValue = $this->calculateEMA($marketData, $emaFast);
        $emaSlowValue = $this->calculateEMA($marketData, $emaSlow);
        $currentPrice = $marketData['current_price'];
        
        $action = 'hold';
        $confidence = 0;
        
        if ($emaFastValue > $emaSlowValue && $currentPrice > $emaFastValue) {
            $action = 'buy';
            $confidence = 75;
        } elseif ($emaFastValue < $emaSlowValue && $currentPrice < $emaFastValue) {
            $action = 'sell';
            $confidence = 75;
        }
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'reason' => "EMA Fast: {$emaFastValue}, EMA Slow: {$emaSlowValue}",
            'parameters' => [
                'ema_fast' => $emaFastValue,
                'ema_slow' => $emaSlowValue,
                'profit_target' => $profitTarget
            ]
        ];
    }

    /**
     * Swing Trading Strategy
     */
    private function executeSwingTradingStrategy(array $params, array $marketData, $bot): array
    {
        $swingPeriod = $params['swing_period'] ?? 14;
        $atrPeriod = $params['atr_period'] ?? 14;
        $atrMultiplier = $params['atr_multiplier'] ?? 2;
        
        $swingHigh = $this->calculateSwingHigh($marketData, $swingPeriod);
        $swingLow = $this->calculateSwingLow($marketData, $swingPeriod);
        $atr = $this->calculateATR($marketData, $atrPeriod);
        $currentPrice = $marketData['current_price'];
        
        $action = 'hold';
        $confidence = 0;
        
        if ($currentPrice > $swingHigh + ($atr * $atrMultiplier)) {
            $action = 'buy';
            $confidence = 80;
        } elseif ($currentPrice < $swingLow - ($atr * $atrMultiplier)) {
            $action = 'sell';
            $confidence = 80;
        }
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'reason' => "Swing High: {$swingHigh}, Swing Low: {$swingLow}, ATR: {$atr}",
            'parameters' => [
                'swing_high' => $swingHigh,
                'swing_low' => $swingLow,
                'atr' => $atr
            ]
        ];
    }

    /**
     * Grid Trading Strategy
     */
    private function executeGridTradingStrategy(array $params, array $marketData, $bot): array
    {
        $gridSize = $params['grid_size'] ?? 0.5; // 0.5%
        $gridLevels = $params['grid_levels'] ?? 10;
        $currentPrice = $marketData['current_price'];
        
        // Calculate grid levels
        $gridPrices = [];
        for ($i = 1; $i <= $gridLevels; $i++) {
            $gridPrices[] = $currentPrice * (1 + ($gridSize / 100) * $i);
            $gridPrices[] = $currentPrice * (1 - ($gridSize / 100) * $i);
        }
        
        $action = 'hold';
        $confidence = 60;
        
        // Simple grid logic - buy on lower levels, sell on higher levels
        $nearestLower = max(array_filter($gridPrices, fn($p) => $p < $currentPrice));
        $nearestUpper = min(array_filter($gridPrices, fn($p) => $p > $currentPrice));
        
        if ($currentPrice <= $nearestLower * 1.001) { // 0.1% tolerance
            $action = 'buy';
        } elseif ($currentPrice >= $nearestUpper * 0.999) {
            $action = 'sell';
        }
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'reason' => "Grid trading with {$gridLevels} levels at {$gridSize}% intervals",
            'parameters' => [
                'grid_prices' => $gridPrices,
                'nearest_lower' => $nearestLower,
                'nearest_upper' => $nearestUpper
            ]
        ];
    }

    /**
     * DCA (Dollar Cost Averaging) Strategy
     */
    private function executeDCAStrategy(array $params, array $marketData, $bot): array
    {
        $dcaInterval = $params['dca_interval'] ?? 24; // hours
        $dcaAmount = $params['dca_amount'] ?? 100; // USD
        $lastDcaTime = $params['last_dca_time'] ?? null;
        
        $action = 'hold';
        $confidence = 50;
        
        // Check if it's time for DCA
        if (!$lastDcaTime || now()->diffInHours($lastDcaTime) >= $dcaInterval) {
            $action = 'buy';
            $confidence = 60; // DCA is a systematic approach
        }
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'reason' => "DCA interval: {$dcaInterval}h, Amount: {$dcaAmount}",
            'parameters' => [
                'dca_interval' => $dcaInterval,
                'dca_amount' => $dcaAmount,
                'last_dca_time' => $lastDcaTime
            ]
        ];
    }

    /**
     * Smart Money Concept Strategy
     */
    public function executeSMCStrategy(array $params, array $marketData, $bot): array
    {
        $signalStrength = $params['signal_strength'] ?? 70;
        $timeframe = $params['timeframe'] ?? '1h';
        $minRangePercentage = $params['min_range_percentage'] ?? 0.5; // Minimum 0.5% range (more flexible)
        $discountThreshold = $params['discount_threshold'] ?? 0.5; // 0.5% within discount zone
        $premiumThreshold = $params['premium_threshold'] ?? 0.5; // 0.5% within premium zone
        
        try {
            // Get candlestick data for SMC analysis
            $exchangeService = new ExchangeService($bot->apiKey);
            $interval = $this->getExchangeInterval($timeframe);
            $candleLimit = $this->getOptimalCandleLimit($timeframe);
            $candles = $exchangeService->getCandles($bot->symbol, $interval, $candleLimit);
            
            if (empty($candles)) {
                return [
                    'action' => 'hold',
                    'confidence' => 0,
                    'reason' => 'No candlestick data available for SMC analysis',
                    'parameters' => $params
                ];
            }
            
            // Create SMC service instance
            $smcService = new \App\Services\SmartMoneyConceptsService($candles);
            
            // Get price zones
            $zones = $smcService->getPriceZones();
            $currentPrice = $marketData['current_price'];
            
            if (!$zones['discount'] || !$zones['premium']) {
                return [
                    'action' => 'hold',
                    'confidence' => 0,
                    'reason' => 'Insufficient swing points for SMC analysis',
                    'parameters' => $params
                ];
            }
            
            // Get current price zone
            $priceZone = $smcService->getCurrentPriceZone($currentPrice);
            
            // Prepare detailed SMC analysis for logging
            $smcAnalysis = [
                'price_zones' => [
                    'discount' => [
                        'min' => $zones['discount'],
                        'max' => $zones['equilibrium']
                    ],
                    'equilibrium' => [
                        'min' => $zones['equilibrium'],
                        'max' => $zones['premium']
                    ],
                    'premium' => [
                        'min' => $zones['premium'],
                        'max' => $zones['premium'] * 1.1 // Extend premium zone
                    ]
                ],
                'current_zone' => [
                    'name' => ucfirst(str_replace('_', ' ', $priceZone['zone'])),
                    'percentage' => $priceZone['percentage'] ?? 0,
                    'min' => $priceZone['min'] ?? 0,
                    'max' => $priceZone['max'] ?? 0,
                    'distance_from_center' => $priceZone['distance_from_center'] ?? 0
                ],
                'swing_points' => [
                    'swing_high' => $zones['swing_high'] ?? 0,
                    'swing_low' => $zones['swing_low'] ?? 0,
                    'range_size' => $zones['range_size'] ?? 0,
                    'range_percentage' => $zones['range_percentage'] ?? 0
                ],
                'signal' => [
                    'action' => 'hold',
                    'strength' => 0,
                    'reason' => "Range too small ({$zones['range_percentage']}% < {$minRangePercentage}%)",
                    'entry_price' => $currentPrice
                ],
                'conditions' => [
                    'range_valid' => $zones['range_percentage'] >= $minRangePercentage,
                    'signal_strong' => false,
                    'zone_proximity' => false
                ],
                'no_trade_reason' => "Range too small ({$zones['range_percentage']}% < {$minRangePercentage}%)"
            ];
            
            // Check if range is significant enough
            if ($zones['range_percentage'] < $minRangePercentage) {
                return [
                    'action' => 'hold',
                    'confidence' => 0,
                    'reason' => "Range too small ({$zones['range_percentage']}% < {$minRangePercentage}%)",
                    'smc_analysis' => $smcAnalysis,
                    'parameters' => $params
                ];
            }
            
            $action = 'hold';
            $confidence = 0;
            $reason = '';
            
            // SMC Trading Logic based on broader market structure
            switch ($priceZone['zone']) {
                case 'discount':
                    // Price is in discount zone = DISCOUNT ZONE (institutions buying)
                    $action = 'buy';
                    $confidence = min(100, $signalStrength + 25); // Strong bonus for discount zone
                    $reason = "Price in DISCOUNT zone ({$currentPrice} < {$zones['discount']}) - Strong BUY signal";
                    break;
                    
                case 'premium':
                    // Price is in premium zone = PREMIUM ZONE (institutions selling)
                    $action = 'sell';
                    $confidence = min(100, $signalStrength + 25); // Strong bonus for premium zone
                    $reason = "Price in PREMIUM zone ({$currentPrice} >= {$zones['premium']}) - Strong SELL signal";
                    break;
                    
                case 'equilibrium':
                    // Price is in equilibrium zone = EQUILIBRIUM ZONE (fair value)
                    // Check proximity to discount or premium zones
                    $distanceToDiscount = $currentPrice - $zones['discount'];
                    $distanceToPremium = $zones['premium'] - $currentPrice;
                    $rangeSize = $zones['premium'] - $zones['discount'];
                    
                    // If closer to discount zone (within threshold)
                    if ($distanceToDiscount <= $rangeSize * ($discountThreshold / 100)) {
                        $action = 'buy';
                        $confidence = $signalStrength;
                        $reason = "Price in EQUILIBRIUM near DISCOUNT zone ({$currentPrice} close to {$zones['discount']}) - BUY signal";
                    }
                    // If closer to premium zone (within threshold)
                    elseif ($distanceToPremium <= $rangeSize * ($premiumThreshold / 100)) {
                        $action = 'sell';
                        $confidence = $signalStrength;
                        $reason = "Price in EQUILIBRIUM near PREMIUM zone ({$currentPrice} close to {$zones['premium']}) - SELL signal";
                    }
                    // Otherwise hold (middle of equilibrium)
                    else {
                        $action = 'hold';
                        $confidence = 0;
                        $reason = "Price in EQUILIBRIUM middle zone - waiting for discount/premium";
                    }
                    break;
            }
            
            // Update SMC analysis with actual trading decision
            $smcAnalysis['signal'] = [
                'action' => $action,
                'strength' => $confidence,
                'reason' => $reason,
                'entry_price' => $currentPrice
            ];
            
            $smcAnalysis['conditions'] = [
                'range_valid' => $zones['range_percentage'] >= $minRangePercentage,
                'signal_strong' => $confidence >= $signalStrength,
                'zone_proximity' => in_array($priceZone['zone'], ['discount', 'premium'])
            ];
            
            // Add no trade reason if applicable
            if ($action === 'hold') {
                if ($priceZone['zone'] === 'equilibrium') {
                    $smcAnalysis['no_trade_reason'] = "Price in equilibrium zone - waiting for discount/premium";
                } else {
                    $smcAnalysis['no_trade_reason'] = "No clear SMC signal - price not in optimal zone";
                }
            }

            return [
                'action' => $action,
                'confidence' => $confidence,
                'reason' => $reason,
                'smc_analysis' => $smcAnalysis,
                'parameters' => [
                    'current_zone' => $priceZone['zone'],
                    'discount_level' => $zones['discount'],
                    'equilibrium_level' => $zones['equilibrium'],
                    'premium_level' => $zones['premium'],
                    'range_size' => $zones['range_size'],
                    'range_percentage' => $zones['range_percentage'],
                    'current_price' => $currentPrice,
                    'signal_strength' => $signalStrength
                ]
            ];
            
        } catch (\Exception $e) {
            \Log::error("SMC Strategy error: " . $e->getMessage());
            return [
                'action' => 'hold',
                'confidence' => 0,
                'reason' => 'SMC analysis failed: ' . $e->getMessage(),
                'parameters' => $params
            ];
        }
    }

    /**
     * Custom Strategy (placeholder for user-defined logic)
     */
    private function executeCustomStrategy(array $params, array $marketData, $bot): array
    {
        // This would be where custom strategy logic is executed
        // For now, return a basic implementation
        return [
            'action' => 'hold',
            'confidence' => 50,
            'reason' => 'Custom strategy - implement your logic here',
            'parameters' => $params
        ];
    }

    // Helper methods for SMC strategy
    
    private function getExchangeInterval(string $timeframe): string
    {
        $mapping = [
            '1m' => '1m',
            '5m' => '5m',
            '15m' => '15m',
            '30m' => '30m',
            '1h' => '1h',
            '4h' => '4h',
            '1d' => '1d'
        ];
        
        return $mapping[$timeframe] ?? '1h';
    }
    
    private function getOptimalCandleLimit(string $timeframe): int
    {
        $limits = [
            '1m' => 250,
            '5m' => 250,
            '15m' => 250,
            '30m' => 250,
            '1h' => 250,
            '4h' => 250,
            '1d' => 250
        ];
        
        return $limits[$timeframe] ?? 250;
    }

    // Technical indicator calculation methods (simplified implementations)
    
    private function calculateTrendDirection(array $marketData, int $period): string
    {
        // Simplified trend calculation
        return 'up'; // Placeholder
    }
    
    private function calculateRSI(array $marketData, int $period): float
    {
        // Simplified RSI calculation
        return 50.0; // Placeholder
    }
    
    private function calculateBollingerBands(array $marketData, int $period, float $std): array
    {
        // Simplified Bollinger Bands calculation
        $currentPrice = $marketData['current_price'];
        return [
            'upper' => $currentPrice * 1.02,
            'middle' => $currentPrice,
            'lower' => $currentPrice * 0.98
        ];
    }
    
    private function calculateMomentum(array $marketData, int $period): float
    {
        // Simplified momentum calculation
        return 0.0; // Placeholder
    }
    
    private function calculateVolumeRatio(array $marketData, int $period): float
    {
        // Simplified volume ratio calculation
        return 1.0; // Placeholder
    }
    
    private function calculateEMA(array $marketData, int $period): float
    {
        // Simplified EMA calculation
        return $marketData['current_price']; // Placeholder
    }
    
    private function calculateSwingHigh(array $marketData, int $period): float
    {
        // Simplified swing high calculation
        return $marketData['current_price'] * 1.01; // Placeholder
    }
    
    private function calculateSwingLow(array $marketData, int $period): float
    {
        // Simplified swing low calculation
        return $marketData['current_price'] * 0.99; // Placeholder
    }
    
    private function calculateATR(array $marketData, int $period): float
    {
        // Simplified ATR calculation
        return $marketData['current_price'] * 0.01; // Placeholder
    }
}
