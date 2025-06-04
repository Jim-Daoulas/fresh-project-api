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
        Schema::create('reworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('champion_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Admin who created the rework
            $table->string('title');
            $table->text('summary');
            $table->json('stats')->nullable();
            $table->timestamps();
        });

        Schema::create('rework_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rework_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('key'); // Q, W, E, R, or Passive
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reworks');
        Schema::dropIfExists('rework_abilities');
    }
};
