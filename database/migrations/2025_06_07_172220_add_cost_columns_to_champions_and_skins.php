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
        Schema::table('champions', function (Blueprint $table) {
            $table->integer('unlock_cost')->default(30)->after('stats');
            $table->boolean('is_unlocked_by_default')->default(false)->after('unlock_cost');
        });

        Schema::table('skins', function (Blueprint $table) {
            $table->integer('unlock_cost')->default(10)->after('description');
            $table->boolean('is_unlocked_by_default')->default(false)->after('unlock_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('champions', function (Blueprint $table) {
            $table->dropColumn(['unlock_cost', 'is_unlocked_by_default']);
        });

        Schema::table('skins', function (Blueprint $table) {
            $table->dropColumn(['unlock_cost', 'is_unlocked_by_default']);
        });
    }
};