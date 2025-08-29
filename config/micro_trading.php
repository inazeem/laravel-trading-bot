<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Micro Trading Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings optimized for micro trading (1-2 hour trades)
    | These settings reduce data usage and improve responsiveness for short-term trading
    |
    */

    // Candle limits for each timeframe (optimized for ultra-fast micro trading)botoptimisation
    'candle_limits' => [
        '1m' => 60,    // 1 hour - Recent price action only
        '5m' => 48,    // 4 hours - Short-term momentum  
        '15m' => 32,   // 8 hours - Medium-term structure
        '30m' => 24,   // 12 hours - Daily session analysis
        '1h' => 24,    // 1 day - Daily trend analysis
        '4h' => 30,    // 5 days - Weekly structure
        '1d' => 30     // 1 month - Monthly overview
    ],

    // Market trend analysis settings
    'trend_analysis' => [
        'candles_for_trend' => 10,  // Number of recent candles to analyze for trend
        'swing_detection_length' => 3,  // Swing point detection sensitivity
    ],

    // Signal generation settings for HIGH-PRECISION trading (IMPROVED)
    'signal_settings' => [
        'min_strength_threshold' => 0.90,  // 90% - Drastically increased for quality
        'high_strength_requirement' => 0.95,  // 95% - Only the strongest signals
        'min_confluence' => 2,  // Require multiple timeframe confirmation
        'max_trade_duration_hours' => 2,  // Shorter duration for micro trading
        'enable_engulfing_pattern' => true,  // Enable engulfing candle detection
        'engulfing_min_body_ratio' => 0.7,  // Minimum body ratio for engulfing pattern
    ],

    // Risk management with MULTI-LEVEL TAKE PROFITS and WIDER STOP LOSS
    'risk_management' => [
        'default_stop_loss_percentage' => 5.0,  // Widened to 5% for breathing room
        'multi_take_profit' => true,  // Enable multiple take profit levels
        'take_profit_levels' => [
            'tp1' => ['percentage' => 3.0, 'position_close' => 40],  // Close 40% at 3% profit
            'tp2' => ['percentage' => 6.0, 'position_close' => 35],  // Close 35% at 6% profit  
            'tp3' => ['percentage' => 12.0, 'position_close' => 25], // Close remaining 25% at 12% profit
        ],
        'max_position_size' => 0.01,  // Increased since we have better risk management
        'min_risk_reward_ratio' => 2.4,  // TP1: 3%/5% = 0.6, but overall weighted ratio is ~2.4
        'dynamic_sizing' => true,  // Enable dynamic position sizing based on signal strength
        'volatility_adjustment' => true,  // Adjust SL/TP based on market volatility
        'stop_loss_buffer' => 0.5,  // Additional buffer percentage for market noise
        'trailing_stop' => true,  // Enable trailing stop after TP1
        'trailing_stop_distance' => 2.0,  // Trail by 2% after TP1 hit
    ],

    // Performance optimization
    'performance' => [
        'enable_caching' => true,  // Enable caching for faster analysis
        'cache_duration_minutes' => 5,  // Cache duration for candle data
        'max_concurrent_analysis' => 3,  // Maximum concurrent timeframe analysis
    ],

    // Recommended timeframes for improved trading (UPDATED BASED ON ANALYSIS)
    'recommended_timeframes' => [
        'primary' => ['15m', '30m'],  // Focus on higher quality timeframes
        'secondary' => ['1h'],  // Secondary timeframe for confirmation
        'testing' => ['5m'],  // Only for testing with high strength signals
        'avoid' => ['1m', '4h', '1d'],  // Avoid noisy and slow timeframes
        'engulfing_primary' => '15m',  // Primary timeframe for engulfing pattern detection
    ],

    // Trading session settings (IMPROVED)
    'trading_sessions' => [
        'max_trades_per_hour' => 3,  // Reduced for quality over quantity
        'cooldown_minutes' => 15,  // Increased cooldown for better analysis
        'session_hours' => [  // Active trading hours (24-hour format)
            'start' => 0,  // 00:00 UTC
            'end' => 24,   // 24:00 UTC (24-hour trading)
        ],
        'min_signal_age_minutes' => 5,  // Minimum age before acting on signal
    ],
];
