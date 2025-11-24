<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $report_id
 * @property string $evidence_type
 * @property string|null $file_path
 * @property string|null $file_hash
 * @property array|null $metadata
 * @property string|null $content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Report $report */
class ReportEvidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'evidence_type',
        'file_path',
        'file_hash',
        'metadata',
        'content'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the associated report.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Scope to get evidence by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('evidence_type', $type);
    }

    /**
     * Scope to get file evidence.
     */
    public function scopeFiles($query)
    {
        return $query->whereNotNull('file_path');
    }

    /**
     * Scope to get text evidence.
     */
    public function scopeText($query)
    {
        return $query->whereNotNull('content');
    }

    /**
     * Check if evidence is a file.
     */
    public function isFile(): bool
    {
        return !is_null($this->file_path);
    }

    /**
     * Check if evidence is text content.
     */
    public function isText(): bool
    {
        return !is_null($this->content);
    }

    /**
     * Get file size if available.
     */
    public function getFileSizeAttribute(): ?int
    {
        if (!$this->isFile()) {
            return null;
        }

        $filePath = storage_path('app/' . $this->file_path);
        return file_exists($filePath) ? filesize($filePath) : null;
    }

    /**
     * Get file extension if available.
     */
    public function getFileExtensionAttribute(): ?string
    {
        if (!$this->isFile()) {
            return null;
        }

        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $size = $this->file_size;
        
        if ($size === null) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}