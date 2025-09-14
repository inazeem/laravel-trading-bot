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

    // Signal generation settings - SIMPLIFIED FOR IMMEDIATE TRADING
    'signal_settings' => [
        'min_strength_threshold' => 0.60,  // 60% - Lowered for more trading opportunities
        'high_strength_requirement' => 0.70,  // 70% - More reasonable requirement
        'min_confluence' => 0,  // Allow single timeframe signals (simplified for immediate trading)
        'max_trade_duration_hours' => 2,  // Shorter duration for micro trading
        'enable_engulfing_pattern' => true,  // Enable engulfing candle detection
        'engulfing_min_body_ratio' => 0.7,  // Minimum body ratio for engulfing pattern
    ],


    // Risk management with DYNAMIC PRICE-BASED SL/TP ADJUSTMENT
    'risk_management' => [
        'default_stop_loss_percentage' => 2.0,  // 2% stop loss for 1:2 risk/reward
        'multi_take_profit' => false,  // Disabled to prevent quick closures - use bot's configured TP instead
        'take_profit_levels' => [
            'tp1' => ['percentage' => 4.0, 'position_close' => 100],   // 4% take profit for 1:2 ratio
            'tp2' => ['percentage' => 4.0, 'position_close' => 35],  // Same as TP1 for consistency
            'tp3' => ['percentage' => 4.0, 'position_close' => 35],  // Same as TP1 for consistency
        ],
        'max_position_size' => 0.01,  // Increased since we have better risk management
        'min_risk_reward_ratio' => 2.0,  // 1:2 risk/reward ratio as requested
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
                    'stop_loss_percentage' => 8.0,  // Reverted back to 8.0% - wider SL for high volatility
                    'take_profit_percentage' => 20.0,  // Reverted back to 20.0% - higher TP for bigger moves
                    'min_risk_reward_ratio' => 1.8,
                    'description' => 'Micro-cap altcoins (under $0.01)'
                ],
                // Small altcoins ($0.01 - $1)
                'small' => [
                    'price_range' => ['min' => 0.01, 'max' => 1.0],
                    'stop_loss_percentage' => 6.0,  // Reverted back to 6.0% - medium SL
                    'take_profit_percentage' => 15.0,  // Reverted back to 15.0% - medium TP
                    'min_risk_reward_ratio' => 1.6,
                    'description' => 'Small altcoins ($0.01 - $1.00)'
                ],
                // Medium altcoins ($1 - $100)
                'medium' => [
                    'price_range' => ['min' => 1.0, 'max' => 100.0],
                    'stop_loss_percentage' => 5.0,  // Reverted back to 5.0% - moderate SL for medium volatility
                    'take_profit_percentage' => 12.0,  // Reverted back to 12.0% - moderate TP
                    'min_risk_reward_ratio' => 1.5,
                    'description' => 'Medium altcoins ($1 - $100)'
                ],
                // Large cap ($100 - $10k)
                'large' => [
                    'price_range' => ['min' => 100.0, 'max' => 10000.0],
                    'stop_loss_percentage' => 2.0,  // 2% SL for 1:2 ratio
                    'take_profit_percentage' => 4.0,  // 4% TP for 1:2 ratio
                    'min_risk_reward_ratio' => 2.0,
                    'description' => 'Large cap assets ($100 - $10k) - 1:2 R/R'
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

    // Market condition filtering (NEW)
    'market_conditions' => [
        'enable_tight_market_detection' => true,  // Enable detection of tight/choppy markets
        'tight_market_threshold' => 3.0,  // If support/resistance within 3%, consider market tight
        'volatility_threshold' => 0.02,  // 2% minimum volatility required for trading
        'range_expansion_required' => true,  // Wait for range expansion before trading
        'pause_trading_in_tight_markets' => true,  // Pause trading when markets are too tight
    ],

    // Multi-timeframe confirmation and 1h S/R control
    'mtf_confirmation' => [
        'enable' => false,                      // DISABLED for immediate trading - too restrictive
        'include_30m_trend' => true,            // Require 30m trend agreement with 1h
        'sl_buffer_pct' => 0.003,               // SL buffer around 1h level (0.3%)
        'tp_extension_pct' => 0.006,            // If next target not found, extend from level (0.6%)
        'level_tolerance_pct' => 0.0005,        // Level equality tolerance (0.05%)
        'consumed_ttl_minutes' => 240,          // Cooldown for consumed levels (4h)
    ],
];
