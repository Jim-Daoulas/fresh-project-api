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
        Schema::create('user_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('unlockable_type'); // Champion ή Skin
            $table->unsignedBigInteger('unlockable_id'); // ID του champion ή skin
            $table->integer('cost_paid'); // Πόσα points πλήρωσε
            $table->timestamps();

            // Composite index για γρήγορη αναζήτηση
            $table->index(['user_id', 'unlockable_type', 'unlockable_id']);
            
            // Unique constraint - κάθε user μπορεί να unlock κάτι μόνο μία φορά
            $table->unique(['user_id', 'unlockable_type', 'unlockable_id'], 'user_unlock_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_unlocks');
    }
};