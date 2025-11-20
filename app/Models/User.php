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

    // Relationships will be added here as other models are created
}