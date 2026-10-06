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
        // 1. Dedicated Table for Homepage Slideshow Photos
        Schema::create('homepage_slide_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_slide_id')->constrained('homepage_slides')->cascadeOnDelete();
            $table->longText('image_data'); // Stores base64 data URI (data:image/jpeg;base64,...)
            $table->string('original_name')->nullable();
            $table->string('mime_type')->default('image/jpeg');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();

            $table->index('homepage_slide_id');
        });

        // 2. Dedicated Table for Member Photos
        Schema::create('member_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->longText('photo_data'); // Stores base64 data URI (data:image/jpeg;base64,...)
            $table->string('original_name')->nullable();
            $table->string('mime_type')->default('image/jpeg');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();

            $table->index('member_id');
        });

        // 3. Migrate existing Homepage Slides photos from disk into DB
        $slides = DB::table('homepage_slides')
            ->join('media_files', 'homepage_slides.media_file_id', '=', 'media_files.id')
            ->select('homepage_slides.id as slide_id', 'media_files.*')
            ->get();

        foreach ($slides as $slide) {
            $fullPath = public_path($slide->file_path);
            if (File::exists($fullPath)) {
                $content = File::get($fullPath);
                $mime = $slide->mime_type ?: 'image/jpeg';
                $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

                DB::table('homepage_slide_photos')->insert([
                    'homepage_slide_id' => $slide->slide_id,
                    'image_data' => $base64,
                    'original_name' => $slide->original_name,
                    'mime_type' => $mime,
                    'file_size' => $slide->file_size ?: strlen($content),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Delete physical file from uploads folder so VSCode/public folder stays clean
                if (str_starts_with($slide->file_path, 'assets/images/uploads/')) {
                    File::delete($fullPath);
                }
            }
        }

        // Clean up any remaining hero_slide uploaded files in assets/images/uploads
        $uploadDir = public_path('assets/images/uploads');
        if (File::isDirectory($uploadDir)) {
            $files = File::files($uploadDir);
            foreach ($files as $file) {
                if (str_starts_with($file->getFilename(), 'hero_slide_')) {
                    File::delete($file->getPathname());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_slide_photos');
        Schema::dropIfExists('member_photos');
    }
};
