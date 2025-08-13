<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('futures_trading_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_key_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('exchange');
            $table->string('symbol');
            $table->boolean('is_active')->default(true);
            $table->decimal('risk_percentage', 5, 2)->default(1.00); // Risk per trade
            $table->decimal('max_position_size', 20, 8)->default(0.01);
            $table->json('timeframes')->default('["1m", "5m", "15m"]'); // 1min, 5min, 15min
            $table->integer('leverage')->default(10); // Leverage for futures
            $table->enum('margin_type', ['isolated', 'cross'])->default('isolated');
            $table->enum('position_side', ['long', 'short', 'both'])->default('both');
            $table->decimal('stop_loss_percentage', 5, 2)->default(2.00); // Stop loss percentage
            $table->decimal('take_profit_percentage', 5, 2)->default(4.00); // Take profit percentage
            $table->json('strategy_settings')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->enum('status', ['idle', 'running', 'error', 'paused'])->default('idle');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('futures_trading_bots');
    }
};
