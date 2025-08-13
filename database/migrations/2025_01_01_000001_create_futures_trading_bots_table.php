<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('futures_trading_bots', function (Blueprint $table) {
             $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('api_key_id');
            $table->string('name');
            $table->string('exchange');
            $table->string('symbol');
            $table->boolean('is_active')->default(true);
            $table->decimal('risk_percentage', 5, 2)->default(1);
            $table->decimal('max_position_size', 20, 8)->default(0.01);

            // JSON column without DB default (Laravel will handle default)
            $table->json('timeframes')->nullable();

            $table->integer('leverage')->default(10);
            $table->enum('margin_type', ['isolated', 'cross'])->default('isolated');
            $table->enum('position_side', ['long', 'short', 'both'])->default('both');
            $table->decimal('stop_loss_percentage', 5, 2)->default(2);
            $table->decimal('take_profit_percentage', 5, 2)->default(4);

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
