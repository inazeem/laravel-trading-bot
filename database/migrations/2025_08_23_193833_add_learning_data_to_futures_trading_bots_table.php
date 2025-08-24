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
            // Learning and performance tracking
            $table->json('learning_data')->nullable()->after('enable_bitcoin_correlation');
            $table->decimal('total_pnl', 20, 8)->default(0)->after('learning_data');
            $table->integer('total_trades')->default(0)->after('total_pnl');
            $table->integer('winning_trades')->default(0)->after('total_trades');
            $table->decimal('win_rate', 5, 2)->default(0)->after('winning_trades');
            $table->decimal('profit_factor', 10, 4)->default(0)->after('win_rate');
            $table->decimal('avg_win', 20, 8)->default(0)->after('profit_factor');
            $table->decimal('avg_loss', 20, 8)->default(0)->after('avg_win');
            $table->timestamp('last_learning_at')->nullable()->after('avg_loss');
            
            // Best performing patterns (for quick access)
            $table->string('best_signal_type')->nullable()->after('last_learning_at');
            $table->string('best_timeframe')->nullable()->after('best_signal_type');
            $table->json('best_trading_hours')->nullable()->after('best_timeframe');
            $table->json('worst_trading_hours')->nullable()->after('best_trading_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('futures_trading_bots', function (Blueprint $table) {
            $table->dropColumn([
                'learning_data',
                'total_pnl',
                'total_trades',
                'winning_trades',
                'win_rate',
                'profit_factor',
                'avg_win',
                'avg_loss',
                'last_learning_at',
                'best_signal_type',
                'best_timeframe',
                'best_trading_hours',
                'worst_trading_hours'
            ]);
        });
    }
};
