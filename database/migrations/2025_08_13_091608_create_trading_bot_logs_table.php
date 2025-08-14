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
        Schema::create('trading_bot_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trading_bot_id')->nullable();
            $table->enum('bot_type', ['trading_bot', 'futures_trading_bot'])->default('trading_bot');
            $table->unsignedBigInteger('futures_trading_bot_id')->nullable();
            $table->string('level'); // info, warning, error, debug
            $table->string('category')->nullable(); // price, analysis, signals, execution, etc.
            $table->text('message');
            $table->json('context')->nullable(); // Additional data like prices, counts, etc.
            $table->timestamp('logged_at');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('trading_bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
            $table->foreign('futures_trading_bot_id')->references('id')->on('futures_trading_bots')->onDelete('cascade');
            
            // Indexes for efficient querying
            $table->index(['trading_bot_id', 'logged_at']);
            $table->index(['futures_trading_bot_id', 'logged_at']);
            $table->index(['level', 'logged_at']);
            $table->index(['category', 'logged_at']);
            $table->index(['bot_type', 'logged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_bot_logs');
    }
};
