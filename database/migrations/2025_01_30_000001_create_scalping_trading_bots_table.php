<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scalping_trading_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_key_id')->constrained()->onDelete('cascade');
            
            // Basic bot configuration
            $table->string('name');
            $table->string('exchange')->default('binance');
            $table->string('symbol');
            $table->boolean('is_active')->default(false);
            $table->decimal('risk_percentage', 5, 2)->default(1.0);
            $table->decimal('max_position_size', 12, 8)->default(0.005);
            $table->decimal('min_order_value', 10, 2)->default(10.0);
            
            // Scalping-specific settings
            $table->string('order_type')->default('market'); // market/limit
            $table->decimal('limit_order_buffer', 5, 2)->default(0.1); // for limit orders
            $table->decimal('min_risk_reward_ratio', 4, 2)->default(1.2);
            $table->json('timeframes')->default('["5m", "15m", "30m"]'); // scalping timeframes
            
            // Leverage and margin
            $table->integer('leverage')->default(10);
            $table->string('margin_type')->default('isolated'); // isolated/cross
            $table->string('position_side')->default('both'); // both/long/short
            
            // Risk management
            $table->decimal('stop_loss_percentage', 5, 2)->default(1.5);
            $table->decimal('take_profit_percentage', 5, 2)->default(2.5);
            $table->boolean('enable_trailing_stop')->default(true);
            $table->decimal('trailing_distance', 5, 2)->default(0.8);
            $table->boolean('enable_breakeven')->default(true);
            $table->decimal('breakeven_trigger', 5, 2)->default(1.0);
            
            // Scalping features
            $table->boolean('enable_momentum_scalping')->default(true);
            $table->boolean('enable_price_action_scalping')->default(true);
            $table->boolean('enable_smart_money_scalping')->default(true);
            $table->boolean('enable_quick_exit')->default(true);
            
            // Session management
            $table->integer('max_trades_per_hour')->default(12);
            $table->integer('cooldown_seconds')->default(30);
            $table->integer('max_concurrent_positions')->default(2);
            $table->decimal('max_spread_percentage', 5, 3)->default(0.1);
            
            // Advanced features
            $table->boolean('enable_bitcoin_correlation')->default(false);
            $table->boolean('enable_volatility_filter')->default(true);
            $table->boolean('enable_volume_filter')->default(true);
            $table->json('strategy_settings')->nullable();
            
            // Performance tracking
            $table->decimal('total_pnl', 15, 8)->default(0);
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->decimal('profit_factor', 8, 4)->default(0);
            $table->decimal('avg_win', 15, 8)->default(0);
            $table->decimal('avg_loss', 15, 8)->default(0);
            $table->decimal('avg_trade_duration_minutes', 8, 2)->default(0);
            $table->decimal('max_drawdown', 8, 4)->default(0);
            
            // Learning and optimization
            $table->json('learning_data')->nullable();
            $table->timestamp('last_learning_at')->nullable();
            $table->string('best_signal_type')->nullable();
            $table->string('best_timeframe')->nullable();
            $table->json('best_trading_hours')->nullable();
            $table->json('worst_trading_hours')->nullable();
            $table->decimal('best_rsi_entry_level', 5, 2)->nullable();
            $table->decimal('optimal_spread_threshold', 5, 3)->nullable();
            
            // Status and timestamps
            $table->timestamp('last_run_at')->nullable();
            $table->string('status')->default('idle'); // idle/running/paused/error
            $table->text('last_error')->nullable();
            $table->integer('consecutive_losses')->default(0);
            $table->boolean('risk_management_paused')->default(false);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['exchange', 'symbol']);
            $table->index(['is_active', 'status']);
            $table->index('last_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scalping_trading_bots');
    }
};

