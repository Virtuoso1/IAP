<?php

namespace App\Services\Contracts;

use App\Models\Report;
use Illuminate\Http\UploadedFile;

/**
 * Interface for evidence collection service
 */
interface EvidenceCollectionServiceInterface
{
    /**
     * Collect screenshot evidence for a report.
     *
     * @param Report $report
     * @param string $imageData
     * @return int Evidence ID
     */
    public function collectScreenshot(Report $report, string $imageData): int;

    /**
     * Collect message context evidence for a report.
     *
     * @param Report $report
     * @param int $messageId
     * @param int $contextRadius
     * @return int Evidence ID
     */
    public function collectMessageContext(Report $report, int $messageId, int $contextRadius = 5): int;

    /**
     * Store uploaded file evidence.
     *
     * @param Report $report
     * @param UploadedFile $file
     * @param string $type
     * @return int Evidence ID
     */
    public function storeUploadedFile(Report $report, UploadedFile $file, string $type): int;

    /**
     * Encrypt file data for secure storage.
     *
     * @param string $data
     * @return string Encrypted data
     */
    public function encrypt(string $data): string;

    /**
     * Decrypt file data from secure storage.
     *
     * @param string $encryptedData
     * @return string Decrypted data
     */
    public function decrypt(string $encryptedData): string;

    /**
     * Validate file type and size.
     *
     * @param UploadedFile $file
     * @return array Validation result
     */
    public function validateFile(UploadedFile $file): array;

    /**
     * Scan file for viruses (stubbed for integration).
     *
     * @param string $filePath
     * @return bool Scan result
     */
    public function scanForViruses(string $filePath): bool;

    /**
     * Collect and process evidence from request data.
     *
     * @param array $evidenceData
     * @param int|null $userId
     * @return array
     */
    public function collectEvidence(array $evidenceData, ?int $userId = null): array;
}