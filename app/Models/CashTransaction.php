<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_id',
        'contributor_name',
        'amount',
        'account_type',
        'type',
        'description',
        'proof_file_id',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Get the user who recorded this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated member if linked.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the transfer proof image metadata.
     */
    public function proof(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'proof_file_id');
    }

    /**
     * Validate that the proof file is an image format.
     */
    public function hasValidImageProof(): bool
    {
        return $this->proof && $this->proof->isImage();
    }
}
