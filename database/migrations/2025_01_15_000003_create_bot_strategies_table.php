<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_strategies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('strategy_id');
            $table->morphs('bot'); // bot_type, bot_id (for both TradingBot and FuturesTradingBot)
            $table->json('parameters')->nullable(); // Custom parameter values for this bot
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1); // For multiple strategies per bot
            $table->timestamps();
            
            $table->foreign('strategy_id')->references('id')->on('trading_strategies')->onDelete('cascade');
            $table->unique(['strategy_id', 'bot_type', 'bot_id', 'priority']);
            $table->index(['bot_type', 'bot_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_strategies');
    }
};
