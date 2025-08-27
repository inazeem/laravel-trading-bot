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

    // Candle limits for each timeframe (optimized for ultra-fast micro trading)
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

    // Signal generation settings for micro trading (OPTIMIZATION #3)
    'signal_settings' => [
        'min_strength_threshold' => 0.4,  // Reduced from 0.5 to 0.4 for more signals
        'high_strength_requirement' => 0.70,  // 70% strength requirement for trade placement (reduced from 90%)
        'min_confluence' => 1,  // Single timeframe confirmation for faster execution
        'max_trade_duration_hours' => 2,  // Maximum trade duration
    ],

    // Risk management for micro trading
    'risk_management' => [
        'default_stop_loss_percentage' => 1.5,  // Tighter stop loss for micro trading
        'default_take_profit_percentage' => 3.0,  // 2:1 risk/reward ratio
        'max_position_size' => 0.01,  // Smaller position sizes for micro trading
        'min_risk_reward_ratio' => 1.5,  // Minimum risk/reward ratio
    ],

    // Performance optimization
    'performance' => [
        'enable_caching' => true,  // Enable caching for faster analysis
        'cache_duration_minutes' => 5,  // Cache duration for candle data
        'max_concurrent_analysis' => 3,  // Maximum concurrent timeframe analysis
    ],

    // Recommended timeframes for ultra-fast micro trading
    'recommended_timeframes' => [
        'ultra_fast' => ['1m', '5m', '15m'],  // Ultra-fast timeframes for immediate signals
        'primary' => ['5m', '15m'],  // Primary timeframes for signal generation
        'secondary' => ['1h'],  // Secondary timeframe for confirmation
        'avoid' => ['1d', '4h'],  // Avoid longer timeframes for micro trading
    ],

    // Trading session settings
    'trading_sessions' => [
        'max_trades_per_hour' => 5,  // Maximum trades per hour
        'cooldown_minutes' => 10,  // Cooldown between trades
        'session_hours' => [  // Active trading hours (24-hour format)
            'start' => 0,  // 00:00 UTC
            'end' => 24,   // 24:00 UTC (24-hour trading)
        ],
    ],
];
