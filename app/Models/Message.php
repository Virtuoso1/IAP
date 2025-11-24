<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    // Fillable fields for mass assignment
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'match_id',
        'content',
        'is_read',
    ];

    // Cast fields
    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relationship: sender of the message
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relationship: receiver of the message
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Relationship: associated match
     */
    public function match()
    {
        return $this->belongsTo(UserMatch::class, 'match_id');
    }

    /**
     * Scope: get messages between two users
     */
    public function scopeBetween($query, $user1Id, $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user1Id)
              ->where('receiver_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user2Id)
              ->where('receiver_id', $user1Id);
        });
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Check if message is read
     */
    public function isRead()
    {
        return $this->is_read;
    }
}
