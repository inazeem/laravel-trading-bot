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
        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->string('exchange_order_id')->nullable()->after('notes');
            $table->text('exchange_response')->nullable()->after('exchange_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->dropColumn(['exchange_order_id', 'exchange_response']);
        });
    }
};
