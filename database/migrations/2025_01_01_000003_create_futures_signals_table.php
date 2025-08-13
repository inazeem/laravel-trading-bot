<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('futures_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('futures_trading_bot_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->string('timeframe');
            $table->enum('direction', ['long', 'short']);
            $table->string('signal_type');
            $table->decimal('strength', 5, 4); // Signal strength 0-1
            $table->decimal('price', 20, 8);
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->decimal('risk_reward_ratio', 5, 2)->nullable();
            $table->json('signal_data')->nullable(); // Additional signal data
            $table->boolean('executed')->default(false);
            $table->foreignId('futures_trade_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('futures_signals');
    }
};
