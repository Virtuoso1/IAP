<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property-read int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string $status
 * @property bool $is_available
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $profile_picture
 * @property string|null $bio
 * @property array|null $preferences
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 *
 * @method bool canModerate()
 * @method bool isModerator()
 * @method bool isAdmin()
 * @method bool hasRole(string $role)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'email_verified_at',
        'status',
        'is_available',
        'last_login_at',
        'last_login_ip',
        'profile_picture',
        'bio',
        'preferences'
    ];

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
        'last_login_at' => 'datetime',
        'status' => 'string',
        'preferences' => 'array',
    ];

    /**
     * Get the reports created by the user.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Get the reports against the user.
     */
    public function reportsAgainst(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Get the warnings received by the user.
     */
    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class, 'user_id');
    }

    /**
     * Get the restrictions applied to the user.
     */
    public function restrictions(): HasMany
    {
        return $this->hasMany(UserRestriction::class, 'user_id');
    }

    /**
     * Get the appeals made by the user.
     */
    public function appeals(): HasMany
    {
        return $this->hasMany(Appeal::class, 'user_id');
    }

    /**
     * Get the audit logs for the user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(ModerationAuditLog::class, 'actor_id');
    }

    /**
     * Get the messages sent by the user.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get the groups the user belongs to.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the groups created by the user.
     */
    public function ownedGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    /**
     * Get the group messages sent by the user.
     */
    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class, 'sender_id');
    }

    /**
     * Get the users blocked by this user.
     */
    public function blockedUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocker_id');
    }

    /**
     * Get the users who blocked this user.
     */
    public function blockedBy(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocked_id');
    }

    /**
     * Check if user has specific role.
     *
     * @param string $role The role to check
     * @return bool True if user has the role
     */
    public function hasRole(string $role): bool
    {
        // For now, implement basic role checking
        // In a real implementation, this would query a roles table
        $userRoles = $this->getUserRoles();
        return in_array($role, $userRoles);
    }

    /**
     * Check if user is moderator.
     *
     * @return bool True if user is moderator or admin
     */
    public function isModerator(): bool
    {
        return $this->hasRole('moderator') || $this->hasRole('admin');
    }

    /**
     * Check if user is admin.
     *
     * @return bool True if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get user roles from database or configuration.
     */
    protected function getUserRoles(): array
    {
        // Basic implementation - in production, this would query a roles table
        $roles = [];
        
        // Check if user is admin (you might have an is_admin column)
        if (isset($this->attributes['is_admin']) && $this->attributes['is_admin']) {
            $roles[] = 'admin';
        }
        
        // Check if user is moderator (you might have an is_moderator column)
        if (isset($this->attributes['is_moderator']) && $this->attributes['is_moderator']) {
            $roles[] = 'moderator';
        }
        
        // Check if user has moderator role in the role field
        if ($this->role === 'moderator') {
            $roles[] = 'moderator';
        }
        
        // Check if user has admin role in the role field
        if ($this->role === 'admin') {
            $roles[] = 'admin';
        }
        
        // Default role for all users
        $roles[] = 'user';
        
        return $roles;
    }

    /**
     * Get user permissions.
     */
    public function permissions(): HasMany
    {
        // This would relate to a user_permissions table
        // For now, return an empty relation
        // return $this->hasMany(UserPermission::class);
        // Return empty collection for now since UserPermission model doesn't exist
        return $this->hasMany(\App\Models\Warning::class)->whereRaw('1=0');
    }

    /**
     * Check if user has specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Admin users have all permissions
        if ($this->isAdmin()) {
            return true;
        }
        
        // Moderators have specific permissions
        if ($this->isModerator()) {
            $moderatorPermissions = [
                'view_reports',
                'moderate_reports',
                'issue_warnings',
                'apply_restrictions',
                'view_dashboard'
            ];
            
            return in_array($permission, $moderatorPermissions);
        }
        
        return false;
    }

    /**
     * Check if user is currently restricted.
     */
    public function isRestricted(?string $type = null): bool
    {
        $query = $this->restrictions()->where('is_active', true);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->exists();
    }

    /**
     * Get active restrictions for the user.
     */
    public function getActiveRestrictions(): Collection
    {
        return $this->restrictions()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Check if user can send messages.
     */
    public function canSendMessage(): bool
    {
        return !$this->isRestricted('message_mute');
    }

    /**
     * Check if user can join groups.
     */
    public function canJoinGroups(): bool
    {
        return !$this->isRestricted('group_join');
    }

    /**
     * Get the user's warning level.
     */
    public function getWarningLevel(): int
    {
        return $this->warnings()
            ->where('status', 'active')
            ->sum('level');
    }

    /**
     * Check if user should be auto-restricted based on warnings.
     */
    public function shouldAutoRestrict(): bool
    {
        $warningLevel = $this->getWarningLevel();
        return $warningLevel >= 3; // Auto-restrict after 3 active warnings
    }

    /**
     * Get the user's full name (username for now).
     */
    public function getFullNameAttribute(): string
    {
        return $this->username;
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_picture) {
            return asset('storage/' . $this->profile_picture);
        }
        
        // Default avatar based on username
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->username) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Check if user is online.
     */
    public function isOnline(): bool
    {
        return $this->last_login_at &&
               $this->last_login_at->gt(now()->subMinutes(5));
    }

    /**
     * Get user's display status.
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->isOnline()) {
            return 'online';
        }
        
        if ($this->last_login_at) {
            return 'last_seen_' . $this->last_login_at->diffForHumans();
        }
        
        return 'never_seen';
    }

    /**
     * Get user's role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'moderator' => 'Moderator',
            'helper' => 'Helper',
            'seeker' => 'Seeker',
            'hybrid' => 'Helper/Seeker',
            default => 'User'
        };
    }

    /**
     * Get user's status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'banned' => 'Banned',
            default => 'Unknown'
        };
    }

    /**
     * Get user's status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'yellow',
            'suspended' => 'orange',
            'banned' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get user's role color.
     */
    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'admin' => 'purple',
            'moderator' => 'red',
            'helper' => 'blue',
            'seeker' => 'green',
            'hybrid' => 'indigo',
            default => 'gray'
        };
    }

    /**
     * Check if user can moderate content.
     *
     * @return bool True if user has moderator or admin role
     */
    public function canModerate(): bool
    {
        return $this->isModerator() || $this->isAdmin();
    }

    /**
     * Check if user can access admin features.
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get user's preferences with defaults.
     */
    public function getPreferencesWithDefaults(): array
    {
        $defaults = [
            'email_notifications' => true,
            'push_notifications' => true,
            'show_online_status' => true,
            'allow_direct_messages' => true,
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'UTC'
        ];

        return array_merge($defaults, $this->preferences ?? []);
    }

    /**
     * Update user's last login information.
     */
    public function updateLastLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip()
        ]);
    }

    /**
     * Get user's statistics.
     */
    public function getStatsAttribute(): array
    {
        return [
            'groups_count' => $this->groups()->wherePivot('status', 'active')->count(),
            'owned_groups_count' => $this->ownedGroups()->count(),
            'messages_count' => $this->messages()->count(),
            'warnings_count' => $this->warnings()->where('is_active', true)->count(),
            'restrictions_count' => $this->getActiveRestrictions()->count(),
            'reports_created_count' => $this->reports()->count(),
            'reports_against_count' => $this->reportsAgainst()->count()
        ];
    }
// Blocked users
public function blockedUsers() {
    return $this->hasMany(BlockedUser::class, 'user_id');
}

// ========================================
// MATCH RELATIONSHIPS
// ========================================

/**
 * Get matches where user is the seeker
 */
public function seekerMatches()
{
    return $this->hasMany(UserMatch::class, 'seeker_id');
}

/**
 * Get matches where user is the helper
 */
public function helperMatches()
{
    return $this->hasMany(UserMatch::class, 'helper_id');
}

/**
 * Get all matches for the user (both as seeker and helper)
 */
public function allMatches()
{
    return UserMatch::where(function($query) {
        $query->where('seeker_id', $this->id)
              ->orWhere('helper_id', $this->id);
    });
}

/**
 * Get active matches for the user
 */
public function activeMatches()
{
    return $this->allMatches()->active();
}

/**
 * Get pending matches for the user
 */
public function pendingMatches()
{
    return $this->allMatches()->pending();
}

/**
 * Get completed matches for the user
 */
public function completedMatches()
{
    return $this->allMatches()->completed();
}

/**
 * Get available helpers (users who can help)
 */
public static function getAvailableHelpers()
{
    return self::where(function($query) {
        $query->where('role', 'helper')
              ->orWhere('role', 'hybrid');
    })->where('is_available', true)
      ->where('id', '!=', auth()->id())
      ->get();
}
}