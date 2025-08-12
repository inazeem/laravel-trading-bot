<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_bot_id')->constrained()->onDelete('cascade');
            $table->string('exchange_order_id')->nullable();
            $table->enum('side', ['buy', 'sell']);
            $table->string('symbol');
            $table->decimal('quantity', 15, 8);
            $table->decimal('price', 15, 8);
            $table->decimal('total', 15, 8);
            $table->decimal('fee', 15, 8)->default(0);
            $table->enum('status', ['open', 'closed', 'cancelled'])->default('open');
            $table->string('signal_type')->nullable();
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->decimal('profit_loss', 15, 8)->nullable();
            $table->decimal('profit_loss_percentage', 8, 4)->nullable();
            $table->decimal('stop_loss', 15, 8)->nullable();
            $table->decimal('take_profit', 15, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['trading_bot_id', 'status']);
            $table->index(['symbol', 'status']);
            $table->index('entry_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
