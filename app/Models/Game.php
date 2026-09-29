<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the rounds associated with this game.
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class)->orderBy('round_number');
    }

    /**
     * Get the persistent state associated with this game.
     */
    public function state(): HasOne
    {
        return $this->hasOne(GameState::class);
    }

    /**
     * Get the team scores for this game.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }

    /**
     * Get the teams configured for this game.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class)->orderBy('sort_order');
    }
}
