<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SecurityAlert extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'severity',
        'message',
        'source_ip',
        'user_agent',
        'alertable_type',
        'alertable_id',
        'metadata',
        'resolved_at',
        'resolved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the parent alertable model.
     */
    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active alerts.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope a query to only include alerts of a given severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include alerts of a given type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the alert as resolved.
     */
    public function resolve(int $resolvedBy): bool
    {
        return $this->update([
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);
    }

    /**
     * Check if the alert is resolved.
     */
    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }
}