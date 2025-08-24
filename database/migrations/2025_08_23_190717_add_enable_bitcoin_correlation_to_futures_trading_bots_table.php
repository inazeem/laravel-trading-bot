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
        Schema::table('futures_trading_bots', function (Blueprint $table) {
            $table->boolean('enable_bitcoin_correlation')->default(false)->after('take_profit_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('futures_trading_bots', function (Blueprint $table) {
            $table->dropColumn('enable_bitcoin_correlation');
        });
    }
};
