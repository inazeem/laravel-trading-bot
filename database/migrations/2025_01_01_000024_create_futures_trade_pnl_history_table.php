<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('futures_trade_pnl_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('futures_trade_id');
            $table->decimal('pnl_value', 20, 8);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            
            $table->foreign('futures_trade_id')->references('id')->on('futures_trades')->onDelete('cascade');
            $table->index(['futures_trade_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('futures_trade_pnl_history');
    }
};
