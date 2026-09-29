<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_round_id',
        'answer_text',
        'points',
        'sort_order',
        'is_revealed',
    ];

    protected $casts = [
        'points' => 'integer',
        'sort_order' => 'integer',
        'is_revealed' => 'boolean',
    ];

    /**
     * Get the round that this answer belongs to.
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(GameRound::class, 'game_round_id');
    }
}
