<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_email',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the administrative action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an administrative audit log event.
     */
    public static function record(
        ?User $user,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null
    ): self {
        $user = $user ?? Auth::user();
        $email = $user ? $user->email : 'system@bycgrowth.org';
        $userId = $user ? $user->id : null;

        return static::create([
            'user_id' => $userId,
            'user_email' => $email,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
