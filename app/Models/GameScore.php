<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'team_id',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    /**
     * Get the game for this score.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the team for this score.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
