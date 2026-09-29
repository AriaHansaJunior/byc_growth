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
        Schema::create('game_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_round_id')->constrained('game_rounds')->cascadeOnDelete();
            $table->string('answer_text');
            $table->integer('points')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_revealed')->default(false);
            $table->timestamps();

            $table->index(['game_round_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_answers');
    }
};
