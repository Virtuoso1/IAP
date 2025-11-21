<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupMessage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the group that this message belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get messages for a specific group.
     */
    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope to get recent messages.
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->latest()->limit($limit);
    }

    /**
     * Scope to get messages by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get messages after a specific date/time.
     */
    public function scopeAfter($query, $datetime)
    {
        return $query->where('created_at', '>', $datetime);
    }

    /**
     * Check if this message belongs to a specific user.
     */
    public function belongsToUser($userId)
    {
        return $this->user_id == $userId;
    }

    /**
     * Get formatted time (e.g., "2 hours ago").
     */
    public function getFormattedTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get truncated content for previews.
     */
    public function getPreviewAttribute()
    {
        return strlen($this->content) > 100 
            ? substr($this->content, 0, 100) . '...' 
            : $this->content;
    }
}