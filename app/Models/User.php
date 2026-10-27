<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
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
 * @method bool isSuperAdmin()
 * @method bool isRootOwner()
 * @method bool canAssignSuperAdmin()
 * @method bool canBeEditedBy(?User $actor)
 * @method bool canBeDeletedBy(?User $actor)
 * @method bool hasPermission(string $permission)
 * @method bool hasAnyPermission()
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Permanent root owner usernames (cannot be deleted; can only be edited when logged into root owner accounts).
     */
    public const ROOT_OWNER_USERNAMES = [
        'rilbiezzz',
        'admin_utama',
        'akun_utama',
    ];

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
     * Check if this user is one of the primary website root owners.
     */
    public function isRootOwner(): bool
    {
        return in_array(strtolower($this->username ?? ''), self::ROOT_OWNER_USERNAMES, true);
    }

    /**
     * Check if this user is a super administrator (either root owner or assigned super_admin).
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN || $this->isRootOwner();
    }

    /**
     * Check if the user is an administrator (standard admin or super admin).
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true) || $this->isRootOwner();
    }

    /**
     * Determine if this user has the authority to assign or create Super Admin accounts.
     * Strictly restricted to the root owners (rilbiezzz and admin_utama).
     */
    public function canAssignSuperAdmin(): bool
    {
        return $this->isRootOwner();
    }

    /**
     * Determine if this user account can be edited by the specified actor.
     */
    public function canBeEditedBy(?self $actor): bool
    {
        if (!$actor) {
            return false;
        }

        // Root owners can only be edited by a root owner (when logging into their own root account)
        if ($this->isRootOwner()) {
            return $actor->isRootOwner();
        }

        // An assigned super admin can only be edited by root owners or by themselves
        if ($this->isSuperAdmin()) {
            return $actor->isRootOwner() || $actor->id === $this->id;
        }

        // Regular admins and standard users:
        // Root owners and Super Admins can edit anyone
        if ($actor->isRootOwner() || $actor->isSuperAdmin()) {
            return true;
        }

        // Regular admins can edit regular admins and standard users
        if ($actor->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if this user account can be deleted by the specified actor.
     */
    public function canBeDeletedBy(?self $actor): bool
    {
        if (!$actor) {
            return false;
        }

        // Rule 0: Never allow deleting one's own active account
        if ($actor->id === $this->id) {
            return false;
        }

        // Rule 1: Root owners (rilbiezzz and admin_utama) can NEVER be deleted by anyone under any circumstances
        if ($this->isRootOwner()) {
            return false;
        }

        // Rule 2: Assigned Super Admins can ONLY be deleted by root owners (cannot be deleted by fellow admins or other super admins)
        if ($this->isSuperAdmin()) {
            return $actor->isRootOwner();
        }

        // Rule 3: Regular admins and standard users can be deleted by root owners, super admins, or regular admins
        if ($actor->isRootOwner() || $actor->isSuperAdmin() || $actor->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the administrator has permission to access a specific module.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isAdmin()) {
            return false;
        }

        // Super Admins automatically have full unrestricted access to all modules
        if ($this->isSuperAdmin()) {
            return true;
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
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isAdmin() && is_array($this->permissions) && count($this->permissions) > 0;
    }

    /**
     * Get the currently authenticated user from either admin or web guard.
     */
    public static function current(): ?self
    {
        return Auth::guard('admin')->user() ?? Auth::guard('web')->user();
    }

    /**
     * Check if the current session is authenticated as an administrator across all guards.
     */
    public static function isCurrentAdmin(): bool
    {
        $user = self::current();
        return $user instanceof self && $user->isAdmin();
    }
}
