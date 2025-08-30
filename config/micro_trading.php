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

    // Candle limits for each timeframe (ENHANCED for better SMC analysis)
    'candle_limits' => [
        '1m' => 60,    // 1 hour - Recent price action only (unused)
        '5m' => 48,    // 4 hours - Short-term momentum (unused)
        '15m' => 40,   // 10 hours - Enhanced SMC pattern detection
        '30m' => 32,   // 16 hours - Better confluence & session analysis
        '1h' => 30,    // 30 hours - Superior trend & volatility analysis
        '4h' => 30,    // 5 days - Weekly structure (unused)
        '1d' => 30     // 1 month - Monthly overview (unused)
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

    // Risk management with DYNAMIC PRICE-BASED SL/TP ADJUSTMENT
    'risk_management' => [
        'default_stop_loss_percentage' => 5.0,  // Fallback if no price tier matches
        'multi_take_profit' => false,  // Disabled to prevent quick closures - use bot's configured TP instead
        'take_profit_levels' => [
            'tp1' => ['percentage' => 8.0, 'position_close' => 30],   // More conservative TP1
            'tp2' => ['percentage' => 15.0, 'position_close' => 35],  // Higher TP2
            'tp3' => ['percentage' => 25.0, 'position_close' => 35],  // Much higher TP3
        ],
        'max_position_size' => 0.01,  // Increased since we have better risk management
        'min_risk_reward_ratio' => 1.5,  // More realistic for different asset types
        'dynamic_sizing' => true,  // Enable dynamic position sizing based on signal strength
        'volatility_adjustment' => true,  // Adjust SL/TP based on market volatility
        'stop_loss_buffer' => 0.5,  // Additional buffer percentage for market noise
        'trailing_stop' => false,  // Disabled to prevent premature closures
        'trailing_stop_distance' => 2.0,  // Trail by 2% after TP1 hit
        
        // PRICE-BASED DYNAMIC ADJUSTMENT SYSTEM
        'price_based_adjustment' => [
            'enable' => true,
            'price_tiers' => [
                // Micro-cap altcoins (< $0.01)
                'micro' => [
                    'price_range' => ['min' => 0, 'max' => 0.01],
                    'stop_loss_percentage' => 8.0,  // Wider SL for high volatility
                    'take_profit_percentage' => 20.0,  // Higher TP for bigger moves
                    'min_risk_reward_ratio' => 1.8,
                    'description' => 'Micro-cap altcoins (under $0.01)'
                ],
                // Small altcoins ($0.01 - $1)
                'small' => [
                    'price_range' => ['min' => 0.01, 'max' => 1.0],
                    'stop_loss_percentage' => 6.0,  // Medium SL
                    'take_profit_percentage' => 15.0,  // Medium TP
                    'min_risk_reward_ratio' => 1.6,
                    'description' => 'Small altcoins ($0.01 - $1.00)'
                ],
                // Medium altcoins ($1 - $100)
                'medium' => [
                    'price_range' => ['min' => 1.0, 'max' => 100.0],
                    'stop_loss_percentage' => 5.0,  // Moderate SL for medium volatility
                    'take_profit_percentage' => 12.0,  // Moderate TP
                    'min_risk_reward_ratio' => 1.5,
                    'description' => 'Medium altcoins ($1 - $100)'
                ],
                // Large cap ($100 - $10k)
                'large' => [
                    'price_range' => ['min' => 100.0, 'max' => 10000.0],
                    'stop_loss_percentage' => 3.0,  // Tight SL
                    'take_profit_percentage' => 8.0,  // Conservative TP
                    'min_risk_reward_ratio' => 1.2,
                    'description' => 'Large cap assets ($100 - $10k)'
                ],
                // Ultra large cap (> $10k) - BTC, etc.
                'ultra' => [
                    'price_range' => ['min' => 10000.0, 'max' => PHP_FLOAT_MAX],
                    'stop_loss_percentage' => 2.5,  // Very tight SL
                    'take_profit_percentage' => 6.0,  // Very conservative TP
                    'min_risk_reward_ratio' => 1.0,
                    'description' => 'Ultra large cap assets (> $10k)'
                ]
            ]
        ]
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
        'max_trades_per_hour' => 20,  // Increased for testing - CHANGE BACK TO 3 WHEN DONE
        'cooldown_minutes' => 15,  // Increased cooldown for better analysis
        'session_hours' => [  // Active trading hours (24-hour format)
            'start' => 0,  // 00:00 UTC
            'end' => 24,   // 24:00 UTC (24-hour trading)
        ],
        'min_signal_age_minutes' => 5,  // Minimum age before acting on signal
    ],
];
