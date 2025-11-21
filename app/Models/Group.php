<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all members of the group.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_user')
                    ->withPivot('role', 'status', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get only active members of the group.
     */
    public function activeMembers()
    {
        return $this->belongsToMany(User::class, 'group_user')
                    ->wherePivot('status', 'active')
                    ->where('users.status', 'active') // Also check user account status
                    ->withPivot('role', 'status', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get group admins.
     */
    public function admins()
    {
        return $this->belongsToMany(User::class, 'group_user')
                    ->wherePivot('role', 'admin')
                    ->wherePivot('status', 'active')
                    ->where('users.status', 'active') // Also check user account status
                    ->withPivot('role', 'status', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Get all messages in the group.
     */
    public function messages()
    {
        return $this->hasMany(GroupMessage::class);
    }

    /**
     * Get recent messages (last 50).
     */
    public function recentMessages()
    {
        return $this->hasMany(GroupMessage::class)
                    ->latest()
                    ->limit(50);
    }

    /**
     * Scope to get only active groups.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only public groups.
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope to get only private groups.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Check if the group is full.
     */
    public function isFull()
    {
        return $this->activeMembers()->count() >= $this->max_members;
    }

    /**
     * Check if a user is a member of the group.
     */
    public function hasMember($userId)
    {
        return $this->members()
                ->where('user_id', $userId)
                ->wherePivot('status', 'active')
                ->exists();
    }

    /**
     * Check if a user is an admin of the group.
     */
    public function isAdmin($userId)
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
    public function isBanned($userId)
    {
        return $this->members()
                    ->where('user_id', $userId)
                    ->wherePivot('status', 'banned')
                    ->exists();
    }

    /**
     * Get the member count.
     */
    public function getMemberCountAttribute()
    {
        return $this->activeMembers()->count();
    }

    /**
     * Check if user can manage the group (is admin or moderator).
     */
    public function canManage($user)
    {
        return $this->isAdmin($user->id) || $user->isModerator();
    }

    /**
     * Get helpers available in the group.
     */
    public function availableHelpers()
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
    public function seekers()
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
    public function moderators()
    {
        return $this->activeMembers()
                    ->where('users.role', 'moderator');
    }
}