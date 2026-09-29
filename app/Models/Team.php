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
    ];

    /**
     * Get the game scores achieved by this team.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }
}
