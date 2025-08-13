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
        Schema::create('user_asset_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 8)->default(0);
            $table->decimal('average_buy_price', 15, 8)->default(0);
            $table->decimal('total_invested', 15, 8)->default(0);
            $table->timestamps();
            
            // Ensure unique combination of user and asset
            $table->unique(['user_id', 'asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_asset_holdings');
    }
};
