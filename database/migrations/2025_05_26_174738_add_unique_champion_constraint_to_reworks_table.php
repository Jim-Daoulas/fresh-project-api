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
        Schema::table('reworks', function (Blueprint $table) {
            // Προσθήκη unique constraint στο champion_id
            $table->unique('champion_id', 'unique_champion_rework');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reworks', function (Blueprint $table) {
            // Αφαίρεση του unique constraint
            $table->dropUnique('unique_champion_rework');
        });
    }
};