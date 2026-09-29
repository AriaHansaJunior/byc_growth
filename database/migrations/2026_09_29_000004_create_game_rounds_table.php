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
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->integer('round_number')->default(1);
            $table->text('question')->nullable();
            $table->string('correct_answer')->nullable();
            $table->string('clue')->nullable();
            $table->integer('score')->default(20);
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'round_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};
