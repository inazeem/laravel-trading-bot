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
            $table->foreignId('trading_bot_id')->constrained()->onDelete('cascade');
            $table->string('level'); // info, warning, error, debug
            $table->string('category')->nullable(); // price, analysis, signals, execution, etc.
            $table->text('message');
            $table->json('context')->nullable(); // Additional data like prices, counts, etc.
            $table->timestamp('logged_at');
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['trading_bot_id', 'logged_at']);
            $table->index(['level', 'logged_at']);
            $table->index(['category', 'logged_at']);
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
