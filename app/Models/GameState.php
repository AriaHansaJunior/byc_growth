<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameState extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'current_round_index',
        'state_data',
    ];

    protected $casts = [
        'current_round_index' => 'integer',
        'state_data' => 'array',
    ];

    /**
     * Get the game this state belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
