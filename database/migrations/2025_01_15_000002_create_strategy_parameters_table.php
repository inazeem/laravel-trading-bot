<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('strategy_id');
            $table->string('parameter_name');
            $table->string('parameter_type'); // 'integer', 'float', 'boolean', 'string', 'array'
            $table->text('description')->nullable();
            $table->json('default_value')->nullable();
            $table->json('min_value')->nullable();
            $table->json('max_value')->nullable();
            $table->json('options')->nullable(); // For enum/select parameters
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('strategy_id')->references('id')->on('trading_strategies')->onDelete('cascade');
            $table->unique(['strategy_id', 'parameter_name']);
            $table->index(['strategy_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_parameters');
    }
};
