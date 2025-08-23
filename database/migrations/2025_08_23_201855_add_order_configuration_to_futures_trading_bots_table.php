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
            // Order configuration
            $table->decimal('min_order_value', 10, 2)->default(5.00)->after('max_position_size'); // Minimum order value in USDT
            $table->enum('order_type', ['market', 'limit'])->default('limit')->after('min_order_value'); // Order type preference
            $table->decimal('limit_order_buffer', 5, 4)->default(0.0010)->after('order_type'); // Buffer for limit orders (0.1%)
            $table->decimal('min_risk_reward_ratio', 5, 2)->default(1.00)->after('limit_order_buffer'); // Minimum risk/reward ratio
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('futures_trading_bots', function (Blueprint $table) {
            $table->dropColumn([
                'min_order_value',
                'order_type',
                'limit_order_buffer',
                'min_risk_reward_ratio'
            ]);
        });
    }
};
