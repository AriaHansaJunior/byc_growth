<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property string $role
 * @property array|null $permissions
 * @property int|null $member_id
 * @method bool isAdmin()
 * @method bool hasPermission(string $permission)
 * @method bool hasAnyPermission()
 */
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
        'permissions',
        'member_id',
    ];

    /**
     * Available management modules and their readable labels.
     */
    public const AVAILABLE_PERMISSIONS = [
        'homepage' => [
            'name' => 'View and Access Homepage',
            'description' => 'Allows this admin to view, customize, and publish homepage slides and showcase content.',
        ],
        'activities' => [
            'name' => 'View and Access Activities',
            'description' => 'Allows this admin to view, create, edit, batch delete, and manage fellowship activities and recap photos.',
        ],
        'members' => [
            'name' => 'View and Access Members',
            'description' => 'Allows this admin to view, register, edit, batch delete, and manage fellowship member records.',
        ],
        'games' => [
            'name' => 'View and Access Games',
            'description' => 'Allows this admin to host games, manage rounds, questions, answers, and game system controls.',
        ],
        'birthday_wishes' => [
            'name' => 'View and Access Birthday Wishes',
            'description' => 'Allows this admin to browse archives, view member letters, and manage birthday wishes.',
        ],
        'cash_management' => [
            'name' => 'View and Access Cash Management',
            'description' => 'Allows this admin to view financial ledgers, record transactions, and manage treasury records.',
        ],
        'roles' => [
            'name' => 'View and Access Roles & Accounts',
            'description' => 'Allows this admin to view, create accounts, configure administrator permissions, and manage user roles.',
        ],
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
        'permissions' => 'array',
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

    /**
     * Check if the administrator has permission to access a specific module.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        $perms = $this->permissions;
        if (!is_array($perms)) {
            return false;
        }

        return in_array($permission, $perms, true);
    }

    /**
     * Check if the administrator has any active module permissions.
     */
    public function hasAnyPermission(): bool
    {
        return $this->isAdmin() && is_array($this->permissions) && count($this->permissions) > 0;
    }
}
