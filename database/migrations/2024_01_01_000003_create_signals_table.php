<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_bot_id')->constrained()->onDelete('cascade');
            $table->string('signal_type'); // BOS, CHoCH, OrderBlock_Breakout
            $table->string('timeframe'); // 1h, 4h, 1d
            $table->string('symbol');
            $table->decimal('price', 15, 8);
            $table->decimal('strength', 8, 4)->default(0);
            $table->enum('direction', ['bullish', 'bearish']);
            $table->decimal('support_level', 15, 8)->nullable();
            $table->decimal('resistance_level', 15, 8)->nullable();
            $table->decimal('stop_loss', 15, 8)->nullable();
            $table->decimal('take_profit', 15, 8)->nullable();
            $table->decimal('risk_reward_ratio', 8, 4)->nullable();
            $table->boolean('is_executed')->default(false);
            $table->timestamp('executed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['trading_bot_id', 'signal_type']);
            $table->index(['symbol', 'direction']);
            $table->index('executed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
