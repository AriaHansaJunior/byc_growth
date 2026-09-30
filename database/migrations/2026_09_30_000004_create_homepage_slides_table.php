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
        Schema::create('homepage_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // Seed initial default slides from existing static hero slide assets
        $defaultSlides = [
            [
                'file' => 'assets/images/hero-slide-1.jpg',
                'title' => 'Growing in Faith & Fellowship',
                'caption' => 'A vibrant youth fellowship rooted in faith, love, and spiritual unity.',
                'sort_order' => 1,
            ],
            [
                'file' => 'assets/images/hero-slide-2.jpg',
                'title' => 'Sunday Service & Worship',
                'caption' => 'Connecting hearts through passionate worship, prayer, and God\'s Word.',
                'sort_order' => 2,
            ],
            [
                'file' => 'assets/images/hero-slide-3.jpg',
                'title' => 'Community Outreach & Service',
                'caption' => 'Sharing Christ\'s love through active service and genuine community care.',
                'sort_order' => 3,
            ],
        ];

        foreach ($defaultSlides as $item) {
            $fullPath = public_path($item['file']);
            $mediaId = null;

            if (File::exists($fullPath)) {
                $media = DB::table('media_files')->where('file_path', $item['file'])->first();
                if (!$media) {
                    $mediaId = DB::table('media_files')->insertGetId([
                        'disk' => 'public',
                        'file_path' => $item['file'],
                        'original_name' => basename($item['file']),
                        'mime_type' => 'image/jpeg',
                        'file_size' => File::size($fullPath),
                        'fileable_type' => 'App\\Models\\HomepageSlide',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $mediaId = $media->id;
                }
            }

            $slideId = DB::table('homepage_slides')->insertGetId([
                'media_file_id' => $mediaId,
                'title' => $item['title'],
                'caption' => $item['caption'],
                'sort_order' => $item['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($mediaId) {
                DB::table('media_files')->where('id', $mediaId)->update([
                    'fileable_type' => 'App\\Models\\HomepageSlide',
                    'fileable_id' => $slideId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_slides');
    }
};
