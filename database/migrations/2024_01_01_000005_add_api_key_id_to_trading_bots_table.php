<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->foreignId('api_key_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            
            // Remove old API fields
            $table->dropColumn(['api_key', 'api_secret', 'passphrase']);
        });
    }

    public function down(): void
    {
        Schema::table('trading_bots', function (Blueprint $table) {
            $table->dropForeign(['api_key_id']);
            $table->dropColumn('api_key_id');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            // Restore old API fields
            $table->text('api_key')->after('symbol');
            $table->text('api_secret')->after('api_key');
            $table->string('passphrase')->nullable()->after('api_secret');
        });
    }
};
