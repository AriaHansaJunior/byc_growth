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
        Schema::table('birthday_letters', function (Blueprint $table) {
            $table->boolean('is_self_wish')->default(false)->after('is_anonymous');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('birthday_letters', function (Blueprint $table) {
            $table->dropColumn('is_self_wish');
        });
    }
};
