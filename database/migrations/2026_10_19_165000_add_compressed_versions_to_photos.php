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
        // 1. Add compressed_path to media_files (Activities, Cash Transaction Proofs, Game Rounds)
        if (Schema::hasTable('media_files') && !Schema::hasColumn('media_files', 'compressed_path')) {
            Schema::table('media_files', function (Blueprint $table) {
                $table->string('compressed_path')->nullable()->after('file_path');
            });
        }

        // 2. Add compressed_photo_data to member_photos
        if (Schema::hasTable('member_photos') && !Schema::hasColumn('member_photos', 'compressed_photo_data')) {
            Schema::table('member_photos', function (Blueprint $table) {
                $table->longText('compressed_photo_data')->nullable()->after('photo_data');
            });
        }

        // 3. Add compressed_image_data to homepage_slide_photos
        if (Schema::hasTable('homepage_slide_photos') && !Schema::hasColumn('homepage_slide_photos', 'compressed_image_data')) {
            Schema::table('homepage_slide_photos', function (Blueprint $table) {
                $table->longText('compressed_image_data')->nullable()->after('image_data');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('media_files') && Schema::hasColumn('media_files', 'compressed_path')) {
            Schema::table('media_files', function (Blueprint $table) {
                $table->dropColumn('compressed_path');
            });
        }

        if (Schema::hasTable('member_photos') && Schema::hasColumn('member_photos', 'compressed_photo_data')) {
            Schema::table('member_photos', function (Blueprint $table) {
                $table->dropColumn('compressed_photo_data');
            });
        }

        if (Schema::hasTable('homepage_slide_photos') && Schema::hasColumn('homepage_slide_photos', 'compressed_image_data')) {
            Schema::table('homepage_slide_photos', function (Blueprint $table) {
                $table->dropColumn('compressed_image_data');
            });
        }
    }
};
