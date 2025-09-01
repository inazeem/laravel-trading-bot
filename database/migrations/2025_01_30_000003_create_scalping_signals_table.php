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
        Schema::create('scalping_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scalping_trading_bot_id')->constrained()->onDelete('cascade');
            $table->foreignId('scalping_trade_id')->nullable()->constrained()->onDelete('set null');
            
            // Signal identification
            $table->string('signal_type'); // BOS, CHoCH, momentum_scalping, price_action_scalping
            $table->string('direction'); // long/short
            $table->decimal('strength', 4, 3);
            $table->decimal('scalping_score', 4, 3);
            $table->string('timeframe');
            $table->string('urgency')->default('medium'); // low/medium/high
            
            // Signal details
            $table->decimal('price_at_signal', 16, 8);
            $table->string('entry_reason');
            $table->json('signal_data')->nullable(); // detailed signal information
            $table->integer('confluence'); // how many timeframes agree
            $table->json('contributing_timeframes'); // which timeframes contributed
            
            // Market context
            $table->decimal('rsi_at_signal', 5, 2)->nullable();
            $table->decimal('spread_at_signal', 5, 3)->nullable();
            $table->decimal('volatility_at_signal', 8, 4)->nullable();
            $table->json('market_conditions')->nullable();
            
            // Signal outcome
            $table->boolean('was_traded')->default(false);
            $table->string('not_traded_reason')->nullable(); // cooldown, risk_management, market_conditions
            $table->decimal('max_price_move', 8, 4)->nullable(); // max price move in signal direction
            $table->integer('signal_duration_minutes')->nullable(); // how long signal was valid
            
            // Performance tracking
            $table->boolean('was_successful')->nullable(); // null if not traded, true/false if traded
            $table->decimal('signal_performance_score', 4, 3)->nullable(); // retrospective scoring
            
            $table->timestamps();
            
            // Indexes
            $table->index(['scalping_trading_bot_id', 'signal_type']);
            $table->index(['direction', 'timeframe']);
            $table->index(['was_traded', 'was_successful']);
            $table->index(['urgency', 'strength']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scalping_signals');
    }
};

