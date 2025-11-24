<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'is_private',
        'max_members',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_private' => 'boolean',
        'max_members' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created the group.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all members of the group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_users')
                    ->withPivot('role', 'status', 'is_active', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get only active members of the group.
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_users')
                    ->wherePivot('status', 'active')
                    ->wherePivot('is_active', true)
                    ->where('users.status', 'active') // Also check user account status
                    ->withPivot('role', 'status', 'is_active', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get group admins.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_users')
                    ->wherePivot('role', 'admin')
                    ->wherePivot('status', 'active')
                    ->wherePivot('is_active', true)
                    ->where('users.status', 'active') // Also check user account status
                    ->withPivot('role', 'status', 'is_active', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get all messages in the group.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }

    /**
     * Get recent messages (last 50).
     */
    public function recentMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class)
                    ->latest()
                    ->limit(50);
    }

    /**
     * Scope to get only active groups.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only public groups.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope to get only private groups.
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_private', true);
    }

    /**
     * Check if the group is full.
     */
    public function isFull(): bool
    {
        return $this->activeMembers()->count() >= $this->max_members;
    }

    /**
     * Check if a user is a member of the group.
     */
    public function hasMember(int $userId): bool
    {
        return $this->members()
                ->where('user_id', $userId)
                ->wherePivot('status', 'active')
                ->exists();
    }

    /**
     * Check if a user is an admin of the group.
     */
    public function isAdmin(int $userId): bool
    {
        return $this->members()
                    ->where('user_id', $userId)
                    ->wherePivot('role', 'admin')
                    ->wherePivot('status', 'active')
                    ->where('users.status', 'active') // Also check user account status
                    ->exists();
    }

    /**
     * Check if a user is banned from the group.
     */
    public function isBanned(int $userId): bool
    {
        return $this->members()
                    ->where('user_id', $userId)
                    ->wherePivot('status', 'banned')
                    ->exists();
    }

    /**
     * Get the member count.
     */
    public function getMemberCountAttribute(): int
    {
        return $this->activeMembers()->count();
    }

    /**
     * Check if user can manage the group (is admin or moderator).
     */
    public function canManage(User $user): bool
    {
        return $this->isAdmin($user->id) || $user->isModerator();
    }

    /**
     * Get helpers available in the group.
     */
    public function availableHelpers(): BelongsToMany
    {
        return $this->activeMembers()
                    ->where(function($query) {
                        $query->where('users.role', 'helper')
                              ->orWhere('users.role', 'hybrid');
                    })
                    ->where('users.is_available', true);
    }

    /**
     * Get seekers in the group.
     */
    public function seekers(): BelongsToMany
    {
        return $this->activeMembers()
                    ->where(function($query) {
                        $query->where('users.role', 'seeker')
                              ->orWhere('users.role', 'hybrid');
                    });
    }

    /**
     * Get moderators in the group.
     */
    public function moderators(): BelongsToMany
    {
        return $this->activeMembers()
                    ->where('users.role', 'moderator');
    }
}