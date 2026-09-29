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
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('color');
            }
            if (!Schema::hasColumn('teams', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });

        Schema::table('game_rounds', function (Blueprint $table) {
            if (!Schema::hasColumn('game_rounds', 'awarded_team_id')) {
                $table->foreignId('awarded_team_id')->nullable()->after('score')->constrained('teams')->nullOnDelete();
            }
            if (!Schema::hasColumn('game_rounds', 'awarded_points')) {
                $table->integer('awarded_points')->default(0)->after('awarded_team_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_rounds', function (Blueprint $table) {
            if (Schema::hasColumn('game_rounds', 'awarded_team_id')) {
                $table->dropForeign(['awarded_team_id']);
                $table->dropColumn('awarded_team_id');
            }
            if (Schema::hasColumn('game_rounds', 'awarded_points')) {
                $table->dropColumn('awarded_points');
            }
        });

        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('teams', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
