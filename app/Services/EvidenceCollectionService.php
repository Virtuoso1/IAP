<?php

namespace App\Services;

use App\Services\Contracts\EvidenceCollectionServiceInterface;
use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EvidenceCollectionService implements EvidenceCollectionServiceInterface
{
    /**
     * Collect and process evidence from request.
     *
     * @param array $evidenceData
     * @param int|null $userId
     * @return array
     */
    public function collectEvidence(array $evidenceData, ?int $userId = null): array
    {
        $processedEvidence = [];
        
        foreach ($evidenceData as $evidence) {
            try {
                $processed = $this->processEvidenceItem($evidence, $userId);
                if ($processed) {
                    $processedEvidence[] = $processed;
                }
            } catch (\Exception $e) {
                // Log error but continue processing other evidence
                Log::warning('Failed to process evidence item', [
                    'evidence' => $evidence,
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);
            }
        }
        
        return $processedEvidence;
    }

    /**
     * Process individual evidence item.
     *
     * @param array $evidence
     * @param int|null $userId
     * @return array|null
     */
    protected function processEvidenceItem(array $evidence, ?int $userId = null): ?array
    {
        // Validate evidence structure
        $validator = Validator::make($evidence, [
            'type' => 'required|string|in:screenshot,chat_log,email,other,file',
            'description' => 'required|string|max:500',
            'file_path' => 'nullable|string|max:255',
            'file' => 'nullable|file|max:10240', // 10MB max
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $processedEvidence = [
            'type' => $evidence['type'],
            'description' => $evidence['description'],
            'metadata' => $evidence['metadata'] ?? [],
        ];

        // Handle file upload if present
        if (isset($evidence['file']) && $evidence['file'] instanceof UploadedFile) {
            $filePath = $this->storeEvidenceFile($evidence['file'], $userId);
            $processedEvidence['file_path'] = $filePath;
            $processedEvidence['original_filename'] = $evidence['file']->getClientOriginalName();
            $processedEvidence['file_size'] = $evidence['file']->getSize();
            $processedEvidence['mime_type'] = $evidence['file']->getMimeType();
        } elseif (isset($evidence['file_path'])) {
            $processedEvidence['file_path'] = $evidence['file_path'];
        }

        // Add timestamps
        $processedEvidence['created_at'] = now();
        $processedEvidence['updated_at'] = now();

        return $processedEvidence;
    }

    /**
     * Store evidence file securely.
     *
     * @param UploadedFile $file
     * @param int|null $userId
     * @return string
     */
    protected function storeEvidenceFile(UploadedFile $file, ?int $userId = null): string
    {
        $directory = 'evidence/' . date('Y/m/d');
        if ($userId) {
            $directory .= '/' . $userId;
        }

        // Generate secure filename
        $filename = $this->generateSecureFilename($file);
        
        // Store file with virus scanning
        $path = $file->storeAs($directory, $filename, 'evidence');
        
        // Perform virus scan
        $this->scanFileForViruses($path);
        
        return $path;
    }

    /**
     * Generate secure filename.
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = bin2hex(random_bytes(16)); // 32 character random string
        return $basename . '.' . $extension;
    }

    /**
     * Scan uploaded file for viruses.
     *
     * @param string $filePath
     * @return bool
     */
    protected function scanFileForViruses(string $filePath): bool
    {
        try {
            // Integrate with virus scanning service
            // This is a placeholder for actual virus scanning implementation
            $scanResult = $this->performVirusScan($filePath);
            
            if (!$scanResult['clean']) {
                // Remove infected file
                Storage::disk('evidence')->delete($filePath);
                throw new \Exception('File failed virus scan: ' . $scanResult['threat']);
            }
            
            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            Log::error('Virus scan failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Perform actual virus scan.
     *
     * @param string $filePath
     * @return array
     */
    protected function performVirusScan(string $filePath): array
    {
        // Placeholder for actual virus scanning implementation
        // In production, this would integrate with:
        // - ClamAV
        // - AWS Macie
        // - Google Cloud Virus Scanning
        // - Third-party security services
        
        return [
            'clean' => true,
            'threat' => null,
            'scan_time' => now(),
        ];
    }

    /**
     * Validate evidence type constraints.
     *
     * @param string $type
     * @param array $evidence
     * @return bool
     */
    public function validateEvidenceType(string $type, array $evidence): bool
    {
        $rules = $this->getEvidenceTypeRules($type);
        
        $validator = Validator::make($evidence, $rules);
        
        return !$validator->fails();
    }

    /**
     * Get validation rules for evidence type.
     *
     * @param string $type
     * @return array
     */
    protected function getEvidenceTypeRules(string $type): array
    {
        $baseRules = [
            'description' => 'required|string|max:500',
            'metadata' => 'nullable|array',
        ];

        switch ($type) {
            case 'screenshot':
                return array_merge($baseRules, [
                    'file' => 'required|file|image|max:5120', // 5MB max for images
                ]);
                
            case 'chat_log':
                return array_merge($baseRules, [
                    'file' => 'nullable|file|mimes:txt,log|max:1024', // 1MB max for text files
                    'chat_content' => 'nullable|string|max:10000',
                ]);
                
            case 'email':
                return array_merge($baseRules, [
                    'file' => 'nullable|file|mimes:eml,msg|max:2048', // 2MB max for email files
                    'email_headers' => 'nullable|array',
                    'email_body' => 'nullable|string|max:5000',
                ]);
                
            case 'file':
                return array_merge($baseRules, [
                    'file' => 'required|file|max:10240', // 10MB max for general files
                ]);
                
            default:
                return $baseRules;
        }
    }

    /**
     * Get evidence storage statistics.
     *
     * @return array
     */
    public function getStorageStats(): array
    {
        try {
            $disk = Storage::disk('evidence');
            
            return [
                'total_files' => $this->countFiles($disk),
                'total_size' => $this->calculateTotalSize($disk),
                'storage_used' => $disk->size('/'),
                'last_cleanup' => now()->subDays(7), // Placeholder
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get evidence storage stats', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_files' => 0,
                'total_size' => 0,
                'storage_used' => 0,
                'last_cleanup' => null,
            ];
        }
    }

    /**
     * Count files in evidence storage.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     * @return int
     */
    protected function countFiles($disk): int
    {
        // This is a simplified implementation
        // In production, you might want to use a database to track files
        return 0;
    }

    /**
     * Calculate total storage used.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     * @return int
     */
    protected function calculateTotalSize($disk): int
    {
        // This is a simplified implementation
        // In production, you might want to use a database to track file sizes
        return 0;
    }

    /**
     * Clean up old evidence files.
     *
     * @param int $daysOld
     * @return int Number of files cleaned up
     */
    public function cleanupOldEvidence(int $daysOld = 365): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $cleanedCount = 0;
        
        try {
            // This would implement actual cleanup logic
            // In production, you'd want to:
            // 1. Query database for old evidence records
            // 2. Remove files from storage
            // 3. Update database records
            
            Log::info('Evidence cleanup completed', [
                'cutoff_date' => $cutoffDate,
                'files_cleaned' => $cleanedCount,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Evidence cleanup failed', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate,
            ]);
        }
        
        return $cleanedCount;
    }

    /**
     * Collect screenshot evidence for a report.
     */
    public function collectScreenshot(Report $report, string $imageData): int
    {
        try {
            $filename = $this->generateSecureFilenameFromString($imageData, 'png');
            $path = "evidence/screenshots/{$report->id}/{$filename}";
            
            Storage::disk('evidence')->put($path, base64_decode($imageData));
            
            $evidence = $report->evidence()->create([
                'type' => 'screenshot',
                'description' => 'Screenshot evidence',
                'file_path' => $path,
                'metadata' => [
                    'collected_at' => now(),
                    'file_type' => 'image/png',
                ],
            ]);
            
            return $evidence->id;
        } catch (\Exception $e) {
            Log::error('Failed to collect screenshot', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Collect message context for a report.
     */
    public function collectMessageContext(Report $report, int $messageId, int $contextRadius = 5): int
    {
        try {
            // This would collect messages around the reported message
            // Implementation depends on your message structure
            $contextMessages = $this->getMessageContext($messageId, $contextRadius);
            
            $evidence = $report->evidence()->create([
                'type' => 'chat_log',
                'description' => 'Message context evidence',
                'metadata' => [
                    'message_id' => $messageId,
                    'context_radius' => $contextRadius,
                    'messages' => $contextMessages,
                    'collected_at' => now(),
                ],
            ]);
            
            return $evidence->id;
        } catch (\Exception $e) {
            Log::error('Failed to collect message context', [
                'report_id' => $report->id,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Store uploaded file for evidence.
     */
    public function storeUploadedFile(Report $report, UploadedFile $file, string $type): int
    {
        try {
            $this->validateFile($file);
            
            if (!$this->scanForViruses($file->getPathname())) {
                throw new \Exception('File failed virus scan');
            }
            
            $path = $this->storeEvidenceFile($file, $report->reporter_id);
            
            $evidence = $report->evidence()->create([
                'type' => $type,
                'description' => 'Uploaded file evidence',
                'file_path' => $path,
                'metadata' => [
                    'original_filename' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'collected_at' => now(),
                ],
            ]);
            
            return $evidence->id;
        } catch (\Exception $e) {
            Log::error('Failed to store uploaded file', [
                'report_id' => $report->id,
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Encrypt sensitive evidence data.
     */
    public function encrypt(string $data): string
    {
        try {
            // Implement encryption logic
            // This would integrate with your encryption service
            $key = config('app.evidence_encryption_key');
            $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            
            $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
            
            return base64_encode($iv . $encrypted);
        } catch (\Exception $e) {
            Log::error('Failed to encrypt evidence data', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt sensitive evidence data.
     */
    public function decrypt(string $encryptedData): string
    {
        try {
            $key = config('app.evidence_encryption_key');
            $data = base64_decode($encryptedData);
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt evidence data', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate uploaded file.
     */
    public function validateFile(UploadedFile $file): array
    {
        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,log,eml,msg'
            ],
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    /**
     * Scan file for viruses.
     */
    public function scanForViruses(string $filePath): bool
    {
        try {
            $result = $this->performVirusScan($filePath);
            return $result['clean'];
        } catch (\Exception $e) {
            Log::error('Virus scan failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate secure filename from string data.
     */
    protected function generateSecureFilenameFromString(string $data, string $extension): string
    {
        $basename = bin2hex(random_bytes(16));
        return $basename . '.' . $extension;
    }

    /**
     * Get message context for evidence collection.
     */
    protected function getMessageContext(int $messageId, int $radius): array
    {
        // This would implement actual message context retrieval
        // Implementation depends on your message storage structure
        return [];
    }
}