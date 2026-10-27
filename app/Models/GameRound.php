<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'round_number',
        'question',
        'correct_answer',
        'clue',
        'score',
        'awarded_team_id',
        'awarded_points',
        'media_file_id',
        'image_path',
        'is_hidden',
    ];

    protected $casts = [
        'round_number' => 'integer',
        'score' => 'integer',
        'awarded_points' => 'integer',
        'is_hidden' => 'boolean',
    ];

    /**
     * Get the game this round belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the team awarded points for this round.
     */
    public function awardedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'awarded_team_id');
    }

    /**
     * Get the answers for this round (Growth 100).
     */
    public function answers(): HasMany
    {
        return $this->hasMany(GameAnswer::class)->orderByDesc('points')->orderBy('sort_order');
    }

    /**
     * Get the uploaded media file metadata associated with this round.
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    /**
     * Get the image asset URL.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->mediaFile) {
            $url = $this->mediaFile->getUrl();
            if (!empty($url)) {
                return $url;
            }
        }

        if ($this->image_path) {
            $raw = basename($this->image_path);

            if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
                return $this->image_path;
            }

            if (file_exists(public_path('assets/images/uploads/' . $raw))) {
                return asset('assets/images/uploads/' . $raw);
            }

            if (file_exists(public_path('assets/images/' . $raw))) {
                return asset('assets/images/' . $raw);
            }

            if (file_exists(storage_path('app/game/images/' . $raw))) {
                return route('game.image', ['filename' => $raw]);
            }

            return asset('assets/images/BYC_Growth.jpg');
        }

        return asset('assets/images/BYC_Growth.jpg');
    }

    /**
     * Mutator: normalize correct_answer to uppercase before persistence.
     */
    public function setCorrectAnswerAttribute($value): void
    {
        $this->attributes['correct_answer'] = $value !== null ? strtoupper(trim((string) $value)) : null;
    }

    /**
     * Mutator: normalize clue to uppercase before persistence.
     */
    public function setClueAttribute($value): void
    {
        $this->attributes['clue'] = $value !== null ? strtoupper(trim((string) $value)) : null;
    }
}

