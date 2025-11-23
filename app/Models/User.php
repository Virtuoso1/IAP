<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    
    protected $fillable = [
        'email',
        'password',
        'username',
        'role',
        'is_available',
        'status',
        'warnings_count',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_available' => 'boolean',
            'warnings_count' => 'integer',
        ];
    }
    
    // Helper methods
    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }
    
    public function isHelper(): bool
    {
        return in_array($this->role, ['helper', 'hybrid']);
    }
    
    public function isSeeker(): bool
    {
        return in_array($this->role, ['seeker', 'hybrid']);
    }
    
    public function canHelp(): bool
    {
        return $this->isHelper() && $this->is_available;
    }
    
    // ========================================
    // GROUP RELATIONSHIPS (Person 4)
    // ========================================
    
    /**
     * Get all groups the user is a member of.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user')
                    ->withPivot('role', 'status', 'joined_at')
                    ->withTimestamps();
    }
    
    /**
     * Get only active groups the user is in.
     */
    public function activeGroups()
    {
        return $this->belongsToMany(Group::class, 'group_user')
                    ->wherePivot('status', 'active')
                    ->withPivot('role', 'status', 'joined_at')
                    ->withTimestamps();
    }
    
    /**
     * Get groups created by the user.
     */
    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'owner_id');
    }
    
    /**
     * Get all group messages sent by the user.
     */
    public function groupMessages()
    {
        return $this->hasMany(GroupMessage::class);
    }
    // Messages sent
public function sentMessages() {
    return $this->hasMany(Message::class, 'sender_id');
}

// Messages received
public function receivedMessages() {
    return $this->hasMany(Message::class, 'receiver_id');
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