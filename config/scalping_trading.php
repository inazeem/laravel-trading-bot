<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scalping Trading Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings optimized for scalping (30 seconds - 15 minutes trades)
    | Uses higher timeframes for SMC analysis but faster execution
    |
    */

    // Scalping-optimized timeframes for SMC analysis
    'scalping_timeframes' => [
        'primary' => ['5m', '15m'],      // Fast execution signals
        'confirmation' => ['30m'],        // Medium-term bias
        'trend' => ['1h'],               // Overall trend direction
        'structure' => ['4h'],           // Market structure only (no signals)
    ],

    // Candle limits optimized for scalping analysis
    'candle_limits' => [
        '1m' => 120,   // 2 hours - Micro scalping entry/exit
        '5m' => 60,    // 5 hours - Primary scalping signals
        '15m' => 40,   // 10 hours - Confirmation signals
        '30m' => 32,   // 16 hours - Medium-term bias
        '1h' => 24,    // 24 hours - Trend direction
        '4h' => 18,    // 3 days - Structure analysis only
    ],

    // Scalping-specific signal settings
    'signal_settings' => [
        'min_strength_threshold' => 0.65,        // Lower for more opportunities
        'high_strength_requirement' => 0.75,     // Quality threshold
        'min_confluence' => 1,                   // At least 2 timeframes
        'max_trade_duration_minutes' => 15,     // Quick in/out
        'enable_micro_movements' => true,        // Detect smaller moves
        'scalping_momentum_threshold' => 0.3,    // Lower momentum required
        'quick_exit_on_reversal' => true,       // Exit fast on counter-signals
    ],

    // Scalping risk management (tighter controls)
    'risk_management' => [
        'default_stop_loss_percentage' => 1.5,   // Tighter SL for scalping
        'default_take_profit_percentage' => 2.5, // Quick profit target
        'max_position_size' => 0.005,            // Smaller positions
        'min_risk_reward_ratio' => 1.2,          // Lower R:R acceptable
        'trailing_stop' => true,                 // Use trailing stops
        'trailing_distance' => 0.8,              // Tight trailing
        'breakeven_trigger' => 1.0,              // Move to BE at 1% profit
        
        // Dynamic SL/TP based on market volatility
        'volatility_adjustment' => [
            'enable' => true,
            'low_volatility' => [
                'threshold' => 0.5,              // < 0.5% volatility
                'stop_loss_multiplier' => 0.8,   // Tighter SL
                'take_profit_multiplier' => 0.7, // Lower TP
            ],
            'high_volatility' => [
                'threshold' => 2.0,              // > 2% volatility
                'stop_loss_multiplier' => 1.3,   // Wider SL
                'take_profit_multiplier' => 1.5, // Higher TP
            ],
        ],
    ],

    // Scalping session management
    'trading_sessions' => [
        'max_trades_per_hour' => 12,             // Higher frequency
        'cooldown_seconds' => 30,                // Faster cooldown
        'max_concurrent_positions' => 2,         // Multiple positions allowed
        'session_hours' => [
            'start' => 0,   // 24/7 trading
            'end' => 24,
        ],
        'high_activity_hours' => [               // Best scalping hours
            'london_open' => ['start' => 8, 'end' => 12],    // 08:00-12:00 UTC
            'new_york_open' => ['start' => 13, 'end' => 17], // 13:00-17:00 UTC
            'overlap' => ['start' => 13, 'end' => 16],       // London/NY overlap
        ],
    ],

    // SMC patterns optimized for scalping
    'smc_scalping' => [
        'order_block_sensitivity' => 'high',     // Detect smaller OBs
        'liquidity_grab_timeout' => 5,           // Quick liquidity detection
        'bos_minimum_percentage' => 0.3,         // Smaller BOS acceptable
        'choch_sensitivity' => 'medium',         // Balance speed vs accuracy
        'fair_value_gap_minimum' => 0.2,        // Smaller FVGs
        'momentum_shift_detection' => true,      // Detect quick momentum changes
    ],

    // Market condition filters for scalping
    'market_conditions' => [
        'enable_spread_filter' => true,          // Check bid/ask spread
        'max_spread_percentage' => 0.1,          // Max 0.1% spread
        'min_volume_filter' => true,             // Ensure sufficient volume
        'avoid_news_events' => true,             // Pause during high-impact news
        'consolidation_detection' => true,       // Avoid tight ranges
        'breakout_confirmation' => true,         // Wait for clear breakouts
    ],

    // Performance optimization for speed
    'performance' => [
        'enable_caching' => true,
        'cache_duration_seconds' => 30,          // Faster cache refresh
        'max_concurrent_analysis' => 4,          // More parallel processing
        'priority_timeframes' => ['5m', '15m'],  // Process these first
        'skip_structure_signals' => true,        // 4h for bias only, no signals
    ],

    // Scalping-specific features
    'scalping_features' => [
        'momentum_scalping' => [
            'enable' => true,
            'momentum_period' => 14,              // RSI-like momentum
            'overbought_threshold' => 70,
            'oversold_threshold' => 30,
            'divergence_detection' => true,
        ],
        'price_action_scalping' => [
            'enable' => true,
            'doji_detection' => true,             // Indecision candles
            'hammer_detection' => true,           // Reversal patterns
            'shooting_star_detection' => true,
            'engulfing_sensitivity' => 'high',   // Smaller engulfing patterns
        ],
        'smart_money_scalping' => [
            'enable' => true,
            'institutional_candle_size' => 1.5,  // Smaller institutional moves
            'retail_trap_detection' => true,     // False breakouts
            'liquidity_sweep_scalping' => true,  // Quick liquidity grabs
        ],
    ],
];

