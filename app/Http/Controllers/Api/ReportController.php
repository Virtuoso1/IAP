<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportRequest;
use App\Http\Requests\Api\UpdateReportRequest;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Services\Contracts\EvidenceCollectionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct(
        private EvidenceCollectionServiceInterface $evidenceService
    ) {}

    /**
     * Display a listing of reports.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::with(['reporter', 'category', 'evidence'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->where('created_at', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Display a public listing of reports (no auth required).
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $reports = Report::with(['category'])
            ->where('status', 'resolved') // Only show resolved reports publicly
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->where('created_at', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Store a newly created report.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create the report
            $report = Report::create([
                'reporter_id' => Auth::id(),
                'reportable_type' => $request->reportable_type,
                'reportable_id' => $request->reportable_id,
                'category_id' => $request->category_id,
                'reason' => $request->reason,
                'description' => $request->description,
                'priority' => $this->calculatePriority($request),
                'status' => 'pending',
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'report_context' => $request->report_context ?? [],
                ],
            ]);

            // Process evidence if provided
            if ($request->has('evidence')) {
                $evidenceData = $this->evidenceService->collectEvidence(
                    $request->input('evidence', []),
                    Auth::id()
                );

                foreach ($evidenceData as $evidence) {
                    $report->evidence()->create($evidence);
                }
            }

            // Auto-quarantine content if high priority
            if (in_array($report->priority, ['high', 'critical'])) {
                $this->quarantineContent($report);
            }

            DB::commit();

            // Load relationships for response
            $report->load(['reporter', 'category', 'evidence']);

            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully',
                'data' => $report,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create report', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit report. Please try again.',
                'error_code' => 'report_creation_failed',
            ], 500);
        }
    }

    /**
     * Display the specified report.
     */
    public function show(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load([
            'reporter',
            'category',
            'evidence',
            'moderator',
            'appeals'
        ]);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Update the specified report.
     */
    public function update(UpdateReportRequest $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        try {
            $report->update($request->validated());

            $report->load(['reporter', 'category', 'evidence']);

            return response()->json([
                'success' => true,
                'message' => 'Report updated successfully',
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update report', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update report',
                'error_code' => 'report_update_failed',
            ], 500);
        }
    }

    /**
     * Remove the specified report.
     */
    public function destroy(Report $report): JsonResponse
    {
        $this->authorize('delete', $report);

        try {
            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Report deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete report', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete report',
                'error_code' => 'report_deletion_failed',
            ], 500);
        }
    }

    /**
     * Get report categories.
     */
    public function categories(): JsonResponse
    {
        $categories = ReportCategory::with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Resolve a report.
     */
    public function resolve(Request $request, Report $report): JsonResponse
    {
        $this->authorize('resolve', $report);

        $request->validate([
            'resolution' => 'required|in:resolved,dismissed',
            'resolution_notes' => 'required|string|max:1000',
            'actions_taken' => 'nullable|array',
            'warning_issued' => 'nullable|boolean',
            'restriction_applied' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $report->update([
                'status' => $request->resolution,
                'resolution_notes' => $request->resolution_notes,
                'moderator_id' => Auth::id(),
                'resolved_at' => now(),
                'metadata' => array_merge($report->metadata ?? [], [
                    'resolution_actions' => $request->actions_taken ?? [],
                    'warning_issued' => $request->warning_issued ?? false,
                    'restriction_applied' => $request->restriction_applied ?? false,
                ]),
            ]);

            // Remove quarantine if content was quarantined
            if ($report->metadata['quarantined'] ?? false) {
                $this->removeQuarantine($report);
            }

            DB::commit();

            $report->load(['reporter', 'category', 'evidence', 'moderator']);

            return response()->json([
                'success' => true,
                'message' => 'Report resolved successfully',
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to resolve report', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve report',
                'error_code' => 'report_resolution_failed',
            ], 500);
        }
    }

    /**
     * Get report statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Report::class);

        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_reports' => Report::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'pending_reports' => Report::where('status', 'pending')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'resolved_reports' => Report::where('status', 'resolved')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'dismissed_reports' => Report::where('status', 'dismissed')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'reports_by_priority' => Report::selectRaw('priority, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'reports_by_category' => Report::selectRaw('category_id, COUNT(*) as count')
                ->with('category:name')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('category_id')
                ->get(),
            'average_resolution_time' => Report::whereNotNull('resolved_at')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate report priority based on content and context.
     */
    protected function calculatePriority(Request $request): string
    {
        $category = ReportCategory::find($request->category_id);
        $basePriority = $category->priority ?? 'medium';

        // Adjust priority based on content analysis
        if ($request->has('priority_factors')) {
            $factors = $request->priority_factors;
            
            // Escalate if multiple users affected
            if (($factors['affected_users'] ?? 1) > 5) {
                return 'high';
            }
            
            // Escalate if contains sensitive content
            if ($factors['contains_sensitive_content'] ?? false) {
                return 'high';
            }
            
            // Critical if involves safety concerns
            if ($factors['safety_concern'] ?? false) {
                return 'critical';
            }
        }

        return $basePriority;
    }

    /**
     * Quarantine content associated with report.
     */
    protected function quarantineContent(Report $report): void
    {
        try {
            $content = $report->reportable;
            
            if ($content && method_exists($content, 'quarantine')) {
                $content->quarantine();
                
                $report->update([
                    'metadata' => array_merge($report->metadata ?? [], [
                        'quarantined' => true,
                        'quarantined_at' => now()->toISOString(),
                    ])
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to quarantine content', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
            ]);
        }
    }

    /**
     * Remove quarantine from content.
     */
    protected function removeQuarantine(Report $report): void
    {
        try {
            $content = $report->reportable;
            
            if ($content && method_exists($content, 'removeQuarantine')) {
                $content->removeQuarantine();
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove quarantine', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
            ]);
        }
    }
}