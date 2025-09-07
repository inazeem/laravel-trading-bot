<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_strategies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('type', [
                'trend_following',
                'mean_reversion', 
                'momentum',
                'scalping',
                'swing_trading',
                'arbitrage',
                'grid_trading',
                'dca',
                'custom'
            ]);
            $table->enum('market_type', ['spot', 'futures', 'both'])->default('both');
            $table->json('default_parameters')->nullable();
            $table->json('required_indicators')->nullable();
            $table->json('supported_timeframes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System strategies vs user-created
            $table->unsignedBigInteger('created_by')->nullable(); // User who created custom strategy
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['type', 'market_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_strategies');
    }
};
