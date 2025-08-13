<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('futures_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('futures_trading_bot_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->enum('side', ['long', 'short']);
            $table->decimal('quantity', 20, 8);
            $table->decimal('entry_price', 20, 8);
            $table->decimal('exit_price', 20, 8)->nullable();
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->integer('leverage');
            $table->enum('margin_type', ['isolated', 'cross']);
            $table->decimal('unrealized_pnl', 20, 8)->default(0);
            $table->decimal('realized_pnl', 20, 8)->default(0);
            $table->decimal('pnl_percentage', 10, 4)->default(0);
            $table->enum('status', ['open', 'closed', 'cancelled'])->default('open');
            $table->string('order_id')->nullable();
            $table->json('exchange_response')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('futures_trades');
    }
};
