<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_bots', function (Blueprint $table) {
           $table->id();
            $table->string('name');
            $table->enum('exchange', ['kucoin', 'binance']);
            $table->string('symbol');
            $table->text('api_key');
            $table->text('api_secret');
            $table->string('passphrase')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('risk_percentage', 5, 2)->default(2.00);
            $table->decimal('max_position_size', 15, 8)->default(1000.00000000);
            $table->json('timeframes');
            $table->json('strategy_settings')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->enum('status', ['idle', 'running', 'error'])->default('idle');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_bots');
    }
};
