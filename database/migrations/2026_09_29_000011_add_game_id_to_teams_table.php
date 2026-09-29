<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique('teams_code_unique');
            $table->foreignId('game_id')->nullable()->after('id')->constrained('games')->cascadeOnDelete();
            $table->index(['game_id', 'sort_order']);
        });

        // Seed / assign game_id for existing records to ensure game ownership isolation
        $game1 = DB::table('games')->where('code', 'game1')->first();
        $game2 = DB::table('games')->where('code', 'game2')->first();

        if ($game1) {
            DB::table('teams')->whereNull('game_id')->update(['game_id' => $game1->id]);
        }

        if ($game2) {
            $existingG2Teams = DB::table('teams')->where('game_id', $game2->id)->count();
            if ($existingG2Teams === 0) {
                $g2RedId = DB::table('teams')->insertGetId([
                    'game_id' => $game2->id,
                    'code' => 'red',
                    'name' => 'Red',
                    'color' => 'red',
                    'sort_order' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $g2BlueId = DB::table('teams')->insertGetId([
                    'game_id' => $game2->id,
                    'code' => 'blue',
                    'name' => 'Blue',
                    'color' => 'blue',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Remap Game 2 scores to its own isolated teams
                DB::table('game_scores')->where('game_id', $game2->id)->delete();
                DB::table('game_scores')->insert([
                    ['game_id' => $game2->id, 'team_id' => $g2RedId, 'score' => 0, 'created_at' => now(), 'updated_at' => now()],
                    ['game_id' => $game2->id, 'team_id' => $g2BlueId, 'score' => 0, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropIndex(['game_id', 'sort_order']);
            $table->dropColumn('game_id');
            $table->unique('code', 'teams_code_unique');
        });
    }
};
