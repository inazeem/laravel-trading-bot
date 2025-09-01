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
        Schema::create('scalping_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scalping_trading_bot_id')->constrained()->onDelete('cascade');
            
            // Trade details
            $table->string('symbol');
            $table->string('side'); // long/short
            $table->decimal('quantity', 16, 8);
            $table->decimal('entry_price', 16, 8);
            $table->decimal('exit_price', 16, 8)->nullable();
            $table->decimal('stop_loss', 16, 8);
            $table->decimal('take_profit', 16, 8);
            $table->integer('leverage');
            $table->string('margin_type')->default('isolated');
            
            // PnL tracking
            $table->decimal('unrealized_pnl', 15, 8)->default(0);
            $table->decimal('realized_pnl', 15, 8)->default(0);
            $table->decimal('pnl_percentage', 8, 4)->default(0);
            $table->decimal('fees_paid', 12, 8)->default(0);
            $table->decimal('net_pnl', 15, 8)->default(0); // realized_pnl - fees
            
            // Order IDs
            $table->string('order_id')->nullable();
            $table->string('stop_loss_order_id')->nullable();
            $table->string('take_profit_order_id')->nullable();
            $table->string('trailing_stop_order_id')->nullable();
            
            // Scalping-specific data
            $table->string('signal_type'); // momentum_scalping, price_action_scalping, etc.
            $table->string('entry_reason');
            $table->decimal('signal_strength', 4, 3);
            $table->decimal('scalping_score', 4, 3);
            $table->integer('confluence');
            $table->json('signal_timeframes'); // which timeframes contributed
            $table->string('primary_timeframe'); // main timeframe for entry
            
            // Exit details
            $table->string('exit_reason')->nullable(); // tp_hit, sl_hit, quick_exit, trailing_stop, manual
            $table->boolean('was_trailing_stop_used')->default(false);
            $table->boolean('was_quick_exit')->default(false);
            $table->integer('trade_duration_seconds')->nullable();
            $table->decimal('max_favorable_excursion', 8, 4)->default(0); // best profit during trade
            $table->decimal('max_adverse_excursion', 8, 4)->default(0); // worst loss during trade
            
            // Market conditions at entry
            $table->decimal('entry_spread_percentage', 5, 3)->nullable();
            $table->decimal('entry_rsi', 5, 2)->nullable();
            $table->json('entry_market_data')->nullable();
            
            // Status
            $table->string('status')->default('open'); // open/closed/cancelled
            $table->json('exchange_response')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['scalping_trading_bot_id', 'status']);
            $table->index(['symbol', 'side']);
            $table->index(['status', 'opened_at']);
            $table->index(['signal_type', 'primary_timeframe']);
            $table->index('trade_duration_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scalping_trades');
    }
};

