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
        'start_date',
        'end_date',
        'event_date',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'event_date' => 'date',
    ];

    /**
     * Get the effective start date (fallback to event_date).
     */
    public function getEventStartDateAttribute()
    {
        return $this->start_date ?? $this->event_date;
    }

    /**
     * Get the effective end date (fallback to start_date or event_date).
     */
    public function getEventEndDateAttribute()
    {
        return $this->end_date ?? $this->start_date ?? $this->event_date;
    }

    /**
     * Format the date range neatly:
     * - Single day: "June 15, 2026"
     * - Multi-day same month: "June 15 – 17, 2026"
     * - Multi-day different months: "June 30 – July 02, 2026"
     */
    public function getFormattedDateRangeAttribute(): string
    {
        $start = $this->start_date ?? $this->event_date;
        $end = $this->end_date ?? $start;

        if (!$start) {
            return '';
        }

        if (!$end || $start->isSameDay($end)) {
            return $start->format('F d, Y');
        }

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->format('F d') . ' – ' . $end->format('d, Y');
        }

        if ($start->format('Y') === $end->format('Y')) {
            return $start->format('M d') . ' – ' . $end->format('M d, Y');
        }

        return $start->format('M d, Y') . ' – ' . $end->format('M d, Y');
    }

    /**
     * Get all supporting photos for this activity (polymorphic MediaFile).
     */
    public function photos(): MorphMany
    {
        return $this->morphMany(MediaFile::class, 'fileable');
    }
}
