<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enhanced Trading Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the enhanced spot trading bot with
    | signal strength-based filtering and cooldown periods
    |
    */

    // Signal strength requirements
    'signal_strength' => [
        'minimum_strength' => 0.70,  // 70% minimum strength requirement
        'high_strength_threshold' => 0.85,  // 85% for high confidence signals
        'strength_normalization' => true,  // Normalize strength values
    ],

    // Position sizing settings
    'position_sizing' => [
        'percentage_of_holdings' => 0.10,  // 10% of current holdings
        'percentage_of_usdt_balance' => 0.10,  // 10% of USDT balance for buy orders
        'minimum_order_size' => [
            'BTC' => 0.001,
            'ETH' => 0.01,
            'USDT' => 10,
            'default' => 0.001,
        ],
        'maximum_position_size' => 0.50,  // 50% maximum position size
    ],

    // Cooldown periods
    'cooldown' => [
        'after_trade_hours' => 3,  // 3-hour cooldown after placing trade
        'after_position_close_hours' => 3,  // 3-hour cooldown after closing position
        'enable_cooldown' => true,
    ],

    // Risk management
    'risk_management' => [
        'minimum_risk_reward_ratio' => 1.5,  // Minimum 1.5:1 risk/reward
        'default_stop_loss_percentage' => 2.0,  // 2% default stop loss
        'default_take_profit_percentage' => 3.0,  // 3% default take profit
        'enable_smc_levels' => true,  // Use SMC levels for SL/TP
    ],

    // Trading logic
    'trading_logic' => [
        'close_on_opposite_signal' => true,  // Close position on opposite signal
        'opposite_signal_strength_requirement' => 0.70,  // Only close on strong opposite signals
        'enable_single_position' => true,  // Only one position at a time
        'max_trades_per_day' => 8,  // Maximum 8 trades per day (24/3 = 8)
    ],

    // Asset management
    'asset_management' => [
        'track_holdings' => true,  // Track user asset holdings
        'update_holdings_after_trade' => true,  // Update holdings after each trade
        'prevent_over_selling' => true,  // Prevent selling more than available
        'prevent_over_buying' => true,  // Prevent buying more than USDT balance allows
    ],

    // Logging and monitoring
    'logging' => [
        'log_signal_strength' => true,
        'log_position_sizing' => true,
        'log_cooldown_periods' => true,
        'log_asset_holdings' => true,
    ],

    // Performance optimization
    'performance' => [
        'cache_holdings_data' => true,
        'cache_duration_minutes' => 5,
        'enable_signal_caching' => true,
    ],
];
