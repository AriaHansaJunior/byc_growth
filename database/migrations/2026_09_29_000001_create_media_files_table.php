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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->nullableMorphs('fileable');
            $table->timestamps();

            $table->index(['file_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
