<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('exchange', ['kucoin', 'binance']);
            $table->string('name');
            $table->text('api_key');
            $table->text('api_secret');
            $table->text('passphrase')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('permissions');
            $table->timestamp('last_used_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'exchange']);
            $table->index(['exchange', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
