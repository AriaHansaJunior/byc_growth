<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('media_files')) {
            Schema::table('media_files', function (Blueprint $table) {
                if (!Schema::hasColumn('media_files', 'file_data')) {
                    $table->longText('file_data')->nullable()->after('file_size');
                }
                if (!Schema::hasColumn('media_files', 'compressed_data')) {
                    $table->longText('compressed_data')->nullable()->after('file_data');
                }
            });

            // Migrate any existing media files on disk into the database
            $records = DB::table('media_files')->whereNull('file_data')->get();
            foreach ($records as $record) {
                $fullPath = public_path($record->file_path);
                if (File::exists($fullPath) && is_file($fullPath)) {
                    $content = File::get($fullPath);
                    $mime = $record->mime_type ?: File::mimeType($fullPath) ?: 'image/jpeg';
                    $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

                    $compressedBase64 = null;
                    if (!empty($record->compressed_path)) {
                        $compPath = public_path($record->compressed_path);
                        if (File::exists($compPath) && is_file($compPath)) {
                            $compContent = File::get($compPath);
                            $compMime = File::mimeType($compPath) ?: $mime;
                            $compressedBase64 = 'data:' . $compMime . ';base64,' . base64_encode($compContent);
                        }
                    }

                    DB::table('media_files')->where('id', $record->id)->update([
                        'file_data' => $base64,
                        'compressed_data' => $compressedBase64 ?: $base64,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('media_files')) {
            Schema::table('media_files', function (Blueprint $table) {
                if (Schema::hasColumn('media_files', 'compressed_data')) {
                    $table->dropColumn('compressed_data');
                }
                if (Schema::hasColumn('media_files', 'file_data')) {
                    $table->dropColumn('file_data');
                }
            });
        }
    }
};
