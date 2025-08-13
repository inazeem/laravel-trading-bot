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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique(); // e.g., BTC, ETH, AAPL
            $table->string('name'); // e.g., Bitcoin, Ethereum, Apple Inc.
            $table->decimal('current_price', 15, 8)->default(0);
            $table->string('type')->default('crypto'); // crypto, stock, forex
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
