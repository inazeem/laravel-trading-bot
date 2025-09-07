<?php

namespace App\Services;

use App\Models\TradingStrategy;
use App\Models\StrategyParameter;
use App\Models\BotStrategy;
use Illuminate\Support\Facades\DB;

class StrategyFactory
{
    /**
     * Create default system strategies
     */
    public static function createSystemStrategies(): void
    {
        $strategies = [
            [
                'name' => 'Trend Following',
                'description' => 'Follows market trends using moving averages and RSI',
                'type' => 'trend_following',
                'market_type' => 'both',
                'default_parameters' => [
                    'signal_strength' => 70,
                    'trend_period' => 20,
                    'rsi_oversold' => 30,
                    'rsi_overbought' => 70,
                    'stop_loss_percentage' => 2.0,
                    'take_profit_percentage' => 4.0
                ],
                'required_indicators' => ['sma', 'rsi'],
                'supported_timeframes' => ['1h', '4h', '1d'],
                'parameters' => [
                    ['name' => 'signal_strength', 'type' => 'integer', 'default_value' => 70, 'min_value' => 50, 'max_value' => 95, 'is_required' => true, 'description' => 'Minimum signal strength required'],
                    ['name' => 'trend_period', 'type' => 'integer', 'default_value' => 20, 'min_value' => 5, 'max_value' => 50, 'is_required' => true, 'description' => 'Period for trend calculation'],
                    ['name' => 'rsi_oversold', 'type' => 'integer', 'default_value' => 30, 'min_value' => 20, 'max_value' => 40, 'is_required' => false, 'description' => 'RSI oversold threshold'],
                    ['name' => 'rsi_overbought', 'type' => 'integer', 'default_value' => 70, 'min_value' => 60, 'max_value' => 80, 'is_required' => false, 'description' => 'RSI overbought threshold'],
                    ['name' => 'stop_loss_percentage', 'type' => 'float', 'default_value' => 2.0, 'min_value' => 0.5, 'max_value' => 10.0, 'is_required' => false, 'description' => 'Stop loss percentage'],
                    ['name' => 'take_profit_percentage', 'type' => 'float', 'default_value' => 4.0, 'min_value' => 1.0, 'max_value' => 20.0, 'is_required' => false, 'description' => 'Take profit percentage']
                ]
            ],
            [
                'name' => 'Mean Reversion',
                'description' => 'Trades against the trend when price deviates from mean',
                'type' => 'mean_reversion',
                'market_type' => 'both',
                'default_parameters' => [
                    'bollinger_period' => 20,
                    'bollinger_std' => 2,
                    'rsi_oversold' => 30,
                    'rsi_overbought' => 70,
                    'stop_loss_percentage' => 1.5,
                    'take_profit_percentage' => 3.0
                ],
                'required_indicators' => ['bollinger_bands', 'rsi'],
                'supported_timeframes' => ['1h', '4h', '1d'],
                'parameters' => [
                    ['name' => 'bollinger_period', 'type' => 'integer', 'default_value' => 20, 'min_value' => 10, 'max_value' => 50, 'is_required' => true, 'description' => 'Bollinger Bands period'],
                    ['name' => 'bollinger_std', 'type' => 'float', 'default_value' => 2.0, 'min_value' => 1.0, 'max_value' => 3.0, 'is_required' => true, 'description' => 'Bollinger Bands standard deviation'],
                    ['name' => 'rsi_oversold', 'type' => 'integer', 'default_value' => 30, 'min_value' => 20, 'max_value' => 40, 'is_required' => false, 'description' => 'RSI oversold threshold'],
                    ['name' => 'rsi_overbought', 'type' => 'integer', 'default_value' => 70, 'min_value' => 60, 'max_value' => 80, 'is_required' => false, 'description' => 'RSI overbought threshold']
                ]
            ],
            [
                'name' => 'Momentum Trading',
                'description' => 'Trades in the direction of strong momentum with volume confirmation',
                'type' => 'momentum',
                'market_type' => 'both',
                'default_parameters' => [
                    'momentum_period' => 10,
                    'volume_threshold' => 1.5,
                    'signal_strength' => 75,
                    'stop_loss_percentage' => 2.5,
                    'take_profit_percentage' => 5.0
                ],
                'required_indicators' => ['momentum', 'volume'],
                'supported_timeframes' => ['15m', '1h', '4h'],
                'parameters' => [
                    ['name' => 'momentum_period', 'type' => 'integer', 'default_value' => 10, 'min_value' => 5, 'max_value' => 30, 'is_required' => true, 'description' => 'Momentum calculation period'],
                    ['name' => 'volume_threshold', 'type' => 'float', 'default_value' => 1.5, 'min_value' => 1.0, 'max_value' => 3.0, 'is_required' => true, 'description' => 'Volume ratio threshold'],
                    ['name' => 'signal_strength', 'type' => 'integer', 'default_value' => 75, 'min_value' => 60, 'max_value' => 90, 'is_required' => false, 'description' => 'Minimum signal strength']
                ]
            ],
            [
                'name' => 'Scalping Strategy',
                'description' => 'Quick trades with small profits using fast EMAs',
                'type' => 'scalping',
                'market_type' => 'futures',
                'default_parameters' => [
                    'ema_fast' => 5,
                    'ema_slow' => 20,
                    'profit_target' => 0.5,
                    'stop_loss_percentage' => 0.3,
                    'signal_strength' => 60
                ],
                'required_indicators' => ['ema'],
                'supported_timeframes' => ['1m', '5m', '15m'],
                'parameters' => [
                    ['name' => 'ema_fast', 'type' => 'integer', 'default_value' => 5, 'min_value' => 3, 'max_value' => 10, 'is_required' => true, 'description' => 'Fast EMA period'],
                    ['name' => 'ema_slow', 'type' => 'integer', 'default_value' => 20, 'min_value' => 10, 'max_value' => 50, 'is_required' => true, 'description' => 'Slow EMA period'],
                    ['name' => 'profit_target', 'type' => 'float', 'default_value' => 0.5, 'min_value' => 0.1, 'max_value' => 2.0, 'is_required' => true, 'description' => 'Profit target percentage']
                ]
            ],
            [
                'name' => 'Swing Trading',
                'description' => 'Medium-term trades based on swing highs and lows',
                'type' => 'swing_trading',
                'market_type' => 'both',
                'default_parameters' => [
                    'swing_period' => 14,
                    'atr_period' => 14,
                    'atr_multiplier' => 2,
                    'stop_loss_percentage' => 3.0,
                    'take_profit_percentage' => 6.0
                ],
                'required_indicators' => ['atr', 'swing_points'],
                'supported_timeframes' => ['4h', '1d'],
                'parameters' => [
                    ['name' => 'swing_period', 'type' => 'integer', 'default_value' => 14, 'min_value' => 7, 'max_value' => 30, 'is_required' => true, 'description' => 'Swing calculation period'],
                    ['name' => 'atr_period', 'type' => 'integer', 'default_value' => 14, 'min_value' => 7, 'max_value' => 30, 'is_required' => true, 'description' => 'ATR calculation period'],
                    ['name' => 'atr_multiplier', 'type' => 'float', 'default_value' => 2.0, 'min_value' => 1.0, 'max_value' => 4.0, 'is_required' => true, 'description' => 'ATR multiplier for levels']
                ]
            ],
            [
                'name' => 'Grid Trading',
                'description' => 'Places buy and sell orders at regular intervals',
                'type' => 'grid_trading',
                'market_type' => 'futures',
                'default_parameters' => [
                    'grid_size' => 0.5,
                    'grid_levels' => 10,
                    'max_position_size' => 1000,
                    'profit_per_grid' => 0.3
                ],
                'required_indicators' => [],
                'supported_timeframes' => ['1m', '5m', '15m'],
                'parameters' => [
                    ['name' => 'grid_size', 'type' => 'float', 'default_value' => 0.5, 'min_value' => 0.1, 'max_value' => 2.0, 'is_required' => true, 'description' => 'Grid size percentage'],
                    ['name' => 'grid_levels', 'type' => 'integer', 'default_value' => 10, 'min_value' => 5, 'max_value' => 20, 'is_required' => true, 'description' => 'Number of grid levels'],
                    ['name' => 'profit_per_grid', 'type' => 'float', 'default_value' => 0.3, 'min_value' => 0.1, 'max_value' => 1.0, 'is_required' => false, 'description' => 'Profit per grid level']
                ]
            ],
            [
                'name' => 'Dollar Cost Averaging',
                'description' => 'Systematic buying at regular intervals',
                'type' => 'dca',
                'market_type' => 'spot',
                'default_parameters' => [
                    'dca_interval' => 24,
                    'dca_amount' => 100,
                    'max_dca_periods' => 12
                ],
                'required_indicators' => [],
                'supported_timeframes' => ['1d'],
                'parameters' => [
                    ['name' => 'dca_interval', 'type' => 'integer', 'default_value' => 24, 'min_value' => 1, 'max_value' => 168, 'is_required' => true, 'description' => 'DCA interval in hours'],
                    ['name' => 'dca_amount', 'type' => 'float', 'default_value' => 100, 'min_value' => 10, 'max_value' => 10000, 'is_required' => true, 'description' => 'DCA amount in USD'],
                    ['name' => 'max_dca_periods', 'type' => 'integer', 'default_value' => 12, 'min_value' => 1, 'max_value' => 100, 'is_required' => false, 'description' => 'Maximum DCA periods']
                ]
            ],
            [
                'name' => 'Smart Money Concept',
                'description' => 'Trades based on discount, equilibrium, and premium price zones using SMC methodology',
                'type' => 'smart_money_concept',
                'market_type' => 'both',
                'default_parameters' => [
                    'signal_strength' => 75,
                    'timeframe' => '1h',
                    'min_range_percentage' => 0.5,
                    'discount_threshold' => 0.5,
                    'premium_threshold' => 0.5,
                    'stop_loss_percentage' => 2.0,
                    'take_profit_percentage' => 4.0
                ],
                'required_indicators' => ['swing_points', 'price_zones'],
                'supported_timeframes' => ['15m', '30m', '1h', '4h', '1d'],
                'parameters' => [
                    ['name' => 'signal_strength', 'type' => 'integer', 'default_value' => 75, 'min_value' => 50, 'max_value' => 95, 'is_required' => true, 'description' => 'Minimum signal strength required'],
                    ['name' => 'timeframe', 'type' => 'select', 'default_value' => '1h', 'options' => ['15m', '30m', '1h', '4h', '1d'], 'is_required' => true, 'description' => 'Timeframe for SMC analysis'],
                    ['name' => 'min_range_percentage', 'type' => 'float', 'default_value' => 0.5, 'min_value' => 0.1, 'max_value' => 5.0, 'is_required' => false, 'description' => 'Minimum range percentage for valid signals'],
                    ['name' => 'discount_threshold', 'type' => 'float', 'default_value' => 0.5, 'min_value' => 0.1, 'max_value' => 2.0, 'is_required' => false, 'description' => 'Threshold percentage for discount zone proximity'],
                    ['name' => 'premium_threshold', 'type' => 'float', 'default_value' => 0.5, 'min_value' => 0.1, 'max_value' => 2.0, 'is_required' => false, 'description' => 'Threshold percentage for premium zone proximity'],
                    ['name' => 'stop_loss_percentage', 'type' => 'float', 'default_value' => 2.0, 'min_value' => 0.5, 'max_value' => 10.0, 'is_required' => false, 'description' => 'Stop loss percentage'],
                    ['name' => 'take_profit_percentage', 'type' => 'float', 'default_value' => 4.0, 'min_value' => 1.0, 'max_value' => 20.0, 'is_required' => false, 'description' => 'Take profit percentage']
                ]
            ]
        ];

        DB::transaction(function () use ($strategies) {
            foreach ($strategies as $strategyData) {
                $parameters = $strategyData['parameters'];
                unset($strategyData['parameters']);

                // Create strategy
                $strategy = TradingStrategy::create(array_merge($strategyData, [
                    'is_system' => true,
                    'is_active' => true
                ]));

                // Create parameters
                foreach ($parameters as $index => $paramData) {
                    // Map the parameter data to match database schema
                    $paramData['parameter_name'] = $paramData['name'];
                    $paramData['parameter_type'] = $paramData['type'];
                    
                    // Handle options field for select parameters
                    if (isset($paramData['options'])) {
                        $paramData['options'] = json_encode($paramData['options']);
                    }
                    
                    unset($paramData['name'], $paramData['type']);
                    
                    StrategyParameter::create(array_merge($paramData, [
                        'strategy_id' => $strategy->id,
                        'sort_order' => $index + 1
                    ]));
                }
            }
        });
    }

    /**
     * Create a custom strategy
     */
    public static function createCustomStrategy(array $data, int $userId): TradingStrategy
    {
        $parameters = $data['parameters'] ?? [];
        unset($data['parameters']);

        return DB::transaction(function () use ($data, $parameters, $userId) {
            // Create strategy
            $strategy = TradingStrategy::create(array_merge($data, [
                'is_system' => false,
                'is_active' => true,
                'created_by' => $userId
            ]));

            // Create parameters
            foreach ($parameters as $index => $paramData) {
                // Map the parameter data to match database schema
                $paramData['parameter_name'] = $paramData['name'];
                $paramData['parameter_type'] = $paramData['type'];
                
                // Handle options field for select parameters
                if (isset($paramData['options'])) {
                    $paramData['options'] = json_encode($paramData['options']);
                }
                
                unset($paramData['name'], $paramData['type']);
                
                StrategyParameter::create(array_merge($paramData, [
                    'strategy_id' => $strategy->id,
                    'sort_order' => $index + 1
                ]));
            }

            return $strategy;
        });
    }

    /**
     * Attach strategy to bot
     */
    public static function attachStrategyToBot($bot, int $strategyId, array $parameters = [], int $priority = 1): BotStrategy
    {
        $botType = $bot instanceof \App\Models\FuturesTradingBot ? 
            'App\Models\FuturesTradingBot' : 'App\Models\TradingBot';

        return BotStrategy::create([
            'strategy_id' => $strategyId,
            'bot_type' => $botType,
            'bot_id' => $bot->id,
            'parameters' => $parameters,
            'is_active' => true,
            'priority' => $priority
        ]);
    }

    /**
     * Detach strategy from bot
     */
    public static function detachStrategyFromBot($bot, int $strategyId): bool
    {
        $botType = $bot instanceof \App\Models\FuturesTradingBot ? 
            'App\Models\FuturesTradingBot' : 'App\Models\TradingBot';

        return BotStrategy::where('strategy_id', $strategyId)
            ->where('bot_type', $botType)
            ->where('bot_id', $bot->id)
            ->delete() > 0;
    }

    /**
     * Update bot strategy parameters
     */
    public static function updateBotStrategyParameters($bot, int $strategyId, array $parameters): bool
    {
        $botType = $bot instanceof \App\Models\FuturesTradingBot ? 
            'App\Models\FuturesTradingBot' : 'App\Models\TradingBot';

        return BotStrategy::where('strategy_id', $strategyId)
            ->where('bot_type', $botType)
            ->where('bot_id', $bot->id)
            ->update(['parameters' => $parameters]) > 0;
    }

    /**
     * Get available strategies for a market type
     */
    public static function getAvailableStrategies(string $marketType = 'both'): \Illuminate\Database\Eloquent\Collection
    {
        return TradingStrategy::active()
            ->forMarket($marketType)
            ->with('parameters')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get strategy recommendations for a bot
     */
    public static function getStrategyRecommendations($bot): array
    {
        $marketType = $bot instanceof \App\Models\FuturesTradingBot ? 'futures' : 'spot';
        $timeframes = $bot->timeframes ?? ['1h', '4h', '1d'];
        
        $strategies = self::getAvailableStrategies($marketType);
        $recommendations = [];

        foreach ($strategies as $strategy) {
            $score = 0;
            $reasons = [];

            // Check timeframe compatibility
            $compatibleTimeframes = array_intersect($timeframes, $strategy->supported_timeframes ?? []);
            if (!empty($compatibleTimeframes)) {
                $score += 30;
                $reasons[] = 'Compatible timeframes: ' . implode(', ', $compatibleTimeframes);
            }

            // Check market type compatibility
            if ($strategy->market_type === 'both' || $strategy->market_type === $marketType) {
                $score += 25;
                $reasons[] = 'Compatible with ' . $marketType . ' trading';
            }

            // Strategy-specific recommendations
            switch ($strategy->type) {
                case 'scalping':
                    if (in_array('1m', $timeframes) || in_array('5m', $timeframes)) {
                        $score += 20;
                        $reasons[] = 'Good for short timeframes';
                    }
                    break;
                case 'swing_trading':
                    if (in_array('4h', $timeframes) || in_array('1d', $timeframes)) {
                        $score += 20;
                        $reasons[] = 'Good for longer timeframes';
                    }
                    break;
                case 'dca':
                    if ($marketType === 'spot') {
                        $score += 15;
                        $reasons[] = 'Ideal for spot trading';
                    }
                    break;
            }

            if ($score > 0) {
                $recommendations[] = [
                    'strategy' => $strategy,
                    'score' => $score,
                    'reasons' => $reasons
                ];
            }
        }

        // Sort by score descending
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return $recommendations;
    }
}
