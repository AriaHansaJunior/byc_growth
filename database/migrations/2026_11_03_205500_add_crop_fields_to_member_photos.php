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
        if (Schema::hasTable('member_photos')) {
            Schema::table('member_photos', function (Blueprint $table) {
                if (!Schema::hasColumn('member_photos', 'crop_zoom')) {
                    $table->decimal('crop_zoom', 4, 2)->default(1.00)->after('photo_data');
                }
                if (!Schema::hasColumn('member_photos', 'crop_offset_x')) {
                    $table->float('crop_offset_x')->default(0)->after('crop_zoom');
                }
                if (!Schema::hasColumn('member_photos', 'crop_offset_y')) {
                    $table->float('crop_offset_y')->default(0)->after('crop_offset_x');
                }
                if (!Schema::hasColumn('member_photos', 'raw_photo_data')) {
                    $table->longText('raw_photo_data')->nullable()->after('crop_offset_y');
                }
            });

            DB::table('member_photos')->whereNull('raw_photo_data')->update([
                'raw_photo_data' => DB::raw('photo_data'),
                'crop_zoom' => 1.00,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('member_photos')) {
            Schema::table('member_photos', function (Blueprint $table) {
                $columns = ['crop_zoom', 'crop_offset_x', 'crop_offset_y', 'raw_photo_data'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('member_photos', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
