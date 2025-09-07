<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support modifying enum constraints directly
        // We need to recreate the table with the new enum value
        
        // Create a temporary table with the new enum values
        Schema::create('trading_strategies_temp', function (Blueprint $table) {
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
                'custom',
                'smart_money_concept'  // Add the new type
            ]);
            $table->enum('market_type', ['spot', 'futures', 'both'])->default('both');
            $table->json('default_parameters')->nullable();
            $table->json('required_indicators')->nullable();
            $table->json('supported_timeframes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['type', 'market_type', 'is_active']);
        });
        
        // Copy data from old table to new table
        DB::statement('INSERT INTO trading_strategies_temp SELECT * FROM trading_strategies');
        
        // Drop the old table
        Schema::dropIfExists('trading_strategies');
        
        // Rename the temporary table
        Schema::rename('trading_strategies_temp', 'trading_strategies');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the table without smart_money_concept
        Schema::create('trading_strategies_temp', function (Blueprint $table) {
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
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['type', 'market_type', 'is_active']);
        });
        
        // Copy data back (excluding smart_money_concept strategies)
        DB::statement('INSERT INTO trading_strategies_temp SELECT * FROM trading_strategies WHERE type != "smart_money_concept"');
        
        // Drop the current table
        Schema::dropIfExists('trading_strategies');
        
        // Rename the temporary table
        Schema::rename('trading_strategies_temp', 'trading_strategies');
    }
};
