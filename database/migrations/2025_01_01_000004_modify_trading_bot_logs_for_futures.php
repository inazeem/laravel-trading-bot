<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_bot_logs', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['trading_bot_id']);
            
            // Make the trading_bot_id nullable and add a type field
            $table->unsignedBigInteger('trading_bot_id')->nullable()->change();
            $table->enum('bot_type', ['trading_bot', 'futures_trading_bot'])->default('trading_bot')->after('trading_bot_id');
            
            // Add a new column for futures trading bot ID
            $table->unsignedBigInteger('futures_trading_bot_id')->nullable()->after('bot_type');
            
            // Add foreign key for futures trading bots
            $table->foreign('futures_trading_bot_id')->references('id')->on('futures_trading_bots')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('trading_bot_logs', function (Blueprint $table) {
            // Drop the futures trading bot foreign key
            $table->dropForeign(['futures_trading_bot_id']);
            
            // Remove the new columns
            $table->dropColumn(['bot_type', 'futures_trading_bot_id']);
            
            // Restore the original foreign key constraint
            $table->unsignedBigInteger('trading_bot_id')->nullable(false)->change();
            $table->foreign('trading_bot_id')->references('id')->on('trading_bots')->onDelete('cascade');
        });
    }
};
