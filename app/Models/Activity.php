<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_date',
        'description',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    /**
     * Get all supporting photos for this activity (polymorphic MediaFile).
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(MediaFile::class, 'fileable');
    }
}
