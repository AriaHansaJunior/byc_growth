<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Default earth-tone color themes for dynamic teams
     */
    public const COLOR_PALETTE = [
        ['code' => 'red', 'name' => 'Team Red', 'color' => '#bd4c42', 'theme' => 'red'],
        ['code' => 'blue', 'name' => 'Team Blue', 'color' => '#315e89', 'theme' => 'blue'],
        ['code' => 'forest', 'name' => 'Team Forest', 'color' => '#284e3b', 'theme' => 'forest'],
        ['code' => 'gold', 'name' => 'Team Gold', 'color' => '#c89228', 'theme' => 'gold'],
        ['code' => 'purple', 'name' => 'Team Purple', 'color' => '#6e4870', 'theme' => 'purple'],
        ['code' => 'teal', 'name' => 'Team Teal', 'color' => '#2e7078', 'theme' => 'teal'],
    ];

    /**
     * Get the game scores achieved by this team.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }

    /**
     * Get rounds awarded to this team.
     */
    public function awardedRounds(): HasMany
    {
        return $this->hasMany(GameRound::class, 'awarded_team_id');
    }

    /**
     * Get the score for a specific game.
     */
    public function scoreForGame(int $gameId): int
    {
        $score = $this->scores()->where('game_id', $gameId)->first();
        return $score ? (int) $score->score : 0;
    }
}
