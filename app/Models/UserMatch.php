<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'matches';

    protected $fillable = [
        'seeker_id',
        'helper_id',
        'status',
        'notes',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Relationships
    public function seeker()
    {
        return $this->belongsTo(User::class, 'seeker_id');
    }

    public function helper()
    {
        return $this->belongsTo(User::class, 'helper_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('seeker_id', $userId)
              ->orWhere('helper_id', $userId);
        });
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeActivated()
    {
        return $this->isPending();
    }

    public function canBeCompleted()
    {
        return $this->isActive();
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'active']);
    }

    public function getOtherUser($userId)
    {
        if ($this->seeker_id === $userId) {
            return $this->helper;
        }
        return $this->seeker;
    }

    public function getUserRole($userId)
    {
        if ($this->seeker_id === $userId) {
            return 'seeker';
        }
        if ($this->helper_id === $userId) {
            return 'helper';
        }
        return null;
    }
}