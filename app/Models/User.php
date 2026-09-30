<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'member_id',
    ];

    /**
     * Bootstrap the model and its traits.
     * Ensures every user automatically has a valid unique username if not explicitly set.
     */
    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->username)) {
                $base = !empty($user->email) ? explode('@', $user->email)[0] : (!empty($user->name) ? \Illuminate\Support\Str::slug($user->name, '_') : 'user');
                $base = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?: 'user');
                $candidate = $base;
                $counter = 1;
                while (static::where('username', $candidate)->exists()) {
                    $candidate = $base . '_' . $counter;
                    $counter++;
                }
                $user->username = $candidate;
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'member_id' => 'integer',
    ];

    /**
     * Get the associated fellowship member profile (One User Account = One Member).
     */
    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Check if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
