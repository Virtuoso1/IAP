<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'group_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the group that the user belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user in this group.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active memberships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only banned memberships.
     */
    public function scopeBanned($query)
    {
        return $query->where('status', 'banned');
    }

    /**
     * Scope to get only left memberships.
     */
    public function scopeLeft($query)
    {
        return $query->where('status', 'left');
    }

    /**
     * Scope to get only admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope to get only members (non-admins).
     */
    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
    }

    /**
     * Check if this membership is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if this user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if this user is banned.
     */
    public function isBanned()
    {
        return $this->status === 'banned';
    }

    /**
     * Promote member to admin.
     */
    public function promote()
    {
        return $this->update(['role' => 'admin']);
    }

    /**
     * Demote admin to member.
     */
    public function demote()
    {
        return $this->update(['role' => 'member']);
    }

    /**
     * Ban the user from the group.
     */
    public function ban()
    {
        return $this->update(['status' => 'banned']);
    }

    /**
     * Mark as left the group.
     */
    public function leave()
    {
        return $this->update(['status' => 'left']);
    }

    /**
     * Reactivate membership (unban or rejoin).
     */
    public function activate()
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Boot method to set default values.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($groupUser) {
            if (is_null($groupUser->joined_at)) {
                $groupUser->joined_at = now();
            }
            if (is_null($groupUser->role)) {
                $groupUser->role = 'member';
            }
            if (is_null($groupUser->status)) {
                $groupUser->status = 'active';
            }

            // Validate user account status before adding to group
            $user = User::find($groupUser->user_id);
            if ($user && in_array($user->status, ['banned', 'suspended'])) {
                throw new \Exception('User account is ' . $user->status . ' and cannot join groups.');
            }
        });
    }

    /**
     * Check if the user account is active.
     */
    public function userIsActive()
    {
        return $this->user && $this->user->status === 'active';
    }

    /**
     * Scope to only get memberships where user account is active.
     */
    public function scopeWithActiveUsers($query)
    {
        return $query->whereHas('user', function($q) {
            $q->where('status', 'active');
        });
    }
}