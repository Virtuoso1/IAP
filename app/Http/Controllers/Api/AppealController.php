<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AppealController extends Controller
{
    /**
     * Display a listing of appeals.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appeal::class);

        try {
            $query = Appeal::with(['user', 'reviewer', 'appealable']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('appealable_type')) {
                $query->where('appealable_type', $request->appealable_type);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search by username or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $appeals = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $appeals,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load appeals', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load appeals',
                'error_code' => 'appeals_load_failed',
            ], 500);
        }
    }

    /**
     * Display a public listing of appeals (no auth required).
     */
    public function publicIndex(Request $request): JsonResponse
    {
        try {
            $query = Appeal::with(['appealable'])
                ->where('status', 'approved') // Only show approved appeals publicly
                ->when($request->appealable_type, function ($query, $type) {
                    $query->where('appealable_type', $type);
                })
                ->when($request->date_from, function ($query, $dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                })
                ->when($request->date_to, function ($query, $dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                });

            $appeals = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $appeals,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load public appeals', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load appeals',
                'error_code' => 'public_appeals_load_failed',
            ], 500);
        }
    }

    /**
     * Store a newly created appeal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'appealable_type' => ['required', Rule::in(['Warning', 'UserRestriction'])],
            'appealable_id' => 'required|integer',
            'reason' => 'required|string|max:1000',
            'description' => 'required|string|max:2000',
            'evidence' => 'nullable|array',
            'evidence.*.type' => 'required|string|in:screenshot,chat_log,email,other',
            'evidence.*.description' => 'required|string|max:500',
            'evidence.*.file_path' => 'nullable|string|max:255',
        ]);

        try {
            $userId = Auth::id();

            // Validate appealable exists and belongs to user
            $appealableClass = $request->appealable_type;
            $appealable = $appealableClass::findOrFail($request->appealable_id);

            if ($appealable->user_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only appeal your own warnings or restrictions',
                    'error_code' => 'not_authorized',
                ], 403);
            }

            // Check if appeal already exists
            $existingAppeal = Appeal::where('appealable_type', $request->appealable_type)
                ->where('appealable_id', $request->appealable_id)
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'under_review'])
                ->first();

            if ($existingAppeal) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active appeal for this item',
                    'error_code' => 'appeal_already_exists',
                    'data' => $existingAppeal,
                ], 422);
            }

            // Check appeal deadline (7 days for warnings, 30 days for restrictions)
            $deadlineDays = $request->appealable_type === 'Warning' ? 7 : 30;
            $deadline = $appealable->created_at->addDays($deadlineDays);

            if (now()->greaterThan($deadline)) {
                return response()->json([
                    'success' => false,
                    'message' => "Appeal deadline has passed ({$deadlineDays} days)",
                    'error_code' => 'appeal_deadline_passed',
                ], 422);
            }

            DB::beginTransaction();

            $appeal = Appeal::create([
                'user_id' => $userId,
                'appealable_type' => $request->appealable_type,
                'appealable_id' => $request->appealable_id,
                'reason' => $request->reason,
                'description' => $request->description,
                'evidence' => $request->evidence ?? [],
                'status' => 'pending',
                'deadline_at' => $deadline,
            ]);

            // Log the appeal creation
            app(AuditLogService::class)->log(
                'appeal_created',
                'Appeal created',
                $appeal->id, // entity_id
                'Appeal', // entity_type
                Auth::id(), // actor_id
                [
                    'appealable_type' => $request->appealable_type,
                    'appealable_id' => $request->appealable_id,
                    'reason' => $request->reason,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Appeal submitted successfully',
                'data' => $appeal->load(['user', 'appealable']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create appeal', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'appealable_type' => $request->appealable_type,
                'appealable_id' => $request->appealable_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit appeal',
                'error_code' => 'appeal_creation_failed',
            ], 500);
        }
    }

    /**
     * Get appeal statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appeal::class);

        try {
            $dateFrom = $request->date_from ?? now()->subDays(30);
            $dateTo = $request->date_to ?? now();

            $stats = [
                'total_appeals' => Appeal::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'by_status' => Appeal::selectRaw('status, COUNT(*) as count')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'by_type' => Appeal::selectRaw('appealable_type, COUNT(*) as count')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->groupBy('appealable_type')
                    ->pluck('count', 'appealable_type'),
                'approval_rate' => $this->calculateApprovalRate($dateFrom, $dateTo),
                'average_review_time' => Appeal::whereNotNull('reviewed_at')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
                    ->value('avg_hours'),
                'overdue_appeals' => Appeal::where('status', 'pending')
                    ->where('deadline_at', '<', now())
                    ->count(),
                'pending_appeals' => Appeal::whereIn('status', ['pending', 'under_review'])->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load appeal statistics', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load appeal statistics',
                'error_code' => 'statistics_load_failed',
            ], 500);
        }
    }

    /**
     * Display the specified appeal.
     */
    public function show(Request $request, $appealId): JsonResponse
    {
        $this->authorize('view', Appeal::class);

        try {
            $appeal = Appeal::with(['user', 'reviewer', 'appealable'])
                ->findOrFail($appealId);

            return response()->json([
                'success' => true,
                'data' => $appeal,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load appeal', [
                'error' => $e->getMessage(),
                'appeal_id' => $appealId,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load appeal',
                'error_code' => 'appeal_load_failed',
            ], 500);
        }
    }

    /**
     * Update the specified appeal.
     */
    public function update(Request $request, $appealId): JsonResponse
    {
        $this->authorize('update', Appeal::class);

        $request->validate([
            'reason' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:2000',
            'evidence' => 'nullable|array',
            'evidence.*.type' => 'required|string|in:screenshot,chat_log,email,other',
            'evidence.*.description' => 'required|string|max:500',
            'evidence.*.file_path' => 'nullable|string|max:255',
        ]);

        try {
            $appeal = Appeal::findOrFail($appealId);

            // Check if appeal can be updated
            if (!in_array($appeal->status, ['pending', 'under_review'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update appeal in current status',
                    'error_code' => 'appeal_not_updatable',
                ], 422);
            }

            // Only the user who created the appeal can update it
            if ($appeal->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update your own appeals',
                    'error_code' => 'not_authorized',
                ], 403);
            }

            DB::beginTransaction();

            $updates = [];

            if ($request->has('reason')) {
                $updates['reason'] = $request->reason;
            }

            if ($request->has('description')) {
                $updates['description'] = $request->description;
            }

            if ($request->has('evidence')) {
                $updates['evidence'] = $request->evidence;
            }

            if (!empty($updates)) {
                $appeal->update($updates);

                // Log the appeal update
                app(AuditLogService::class)->log(
                    'appeal_updated',
                    'Appeal updated',
                    $appeal->id, // entity_id
                    'Appeal', // entity_type
                    Auth::id(), // actor_id
                    $updates
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Appeal updated successfully',
                'data' => $appeal->fresh()->load(['user', 'appealable']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update appeal', [
                'error' => $e->getMessage(),
                'appeal_id' => $appealId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update appeal',
                'error_code' => 'appeal_update_failed',
            ], 500);
        }
    }

    /**
     * Review an appeal.
     */
    public function review(Request $request, $appealId): JsonResponse
    {
        $this->authorize('review', Appeal::class);

        $request->validate([
            'status' => ['required', Rule::in(['under_review', 'approved', 'denied'])],
            'review_notes' => 'required|string|max:2000',
            'evidence_review' => 'nullable|array',
            'evidence_review.*.type' => 'required|string',
            'evidence_review.*.assessment' => 'required|string|in:valid,invalid,inconclusive',
            'evidence_review.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            $appeal = Appeal::findOrFail($appealId);

            // Check if appeal can be reviewed
            if (!in_array($appeal->status, ['pending', 'under_review'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appeal cannot be reviewed in current status',
                    'error_code' => 'appeal_not_reviewable',
                ], 422);
            }

            DB::beginTransaction();

            $appeal->update([
                'status' => $request->status,
                'reviewer_id' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $request->review_notes,
                'evidence_review' => $request->evidence_review ?? [],
            ]);

            // If approved, update the original warning/restriction
            if ($request->status === 'approved') {
                $this->handleApprovedAppeal($appeal);
            }

            // Log the appeal review
            app(AuditLogService::class)->log(
                'appeal_reviewed',
                'Appeal reviewed',
                $appeal->id, // entity_id
                'Appeal', // entity_type
                Auth::id(), // actor_id
                [
                    'status' => $request->status,
                    'reviewer_id' => Auth::id(),
                    'review_notes' => $request->review_notes,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Appeal reviewed successfully',
                'data' => $appeal->fresh()->load(['user', 'reviewer', 'appealable']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to review appeal', [
                'error' => $e->getMessage(),
                'appeal_id' => $appealId,
                'reviewer_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to review appeal',
                'error_code' => 'appeal_review_failed',
            ], 500);
        }
    }

    /**
     * Approve an appeal.
     */
    public function approve(Request $request, $appealId): JsonResponse
    {
        return $this->review($request->merge(['status' => 'approved']), $appealId);
    }

    /**
     * Deny an appeal.
     */
    public function deny(Request $request, $appealId): JsonResponse
    {
        return $this->review($request->merge(['status' => 'denied']), $appealId);
    }

    /**
     * Get user's own appeals.
     */
    public function myAppeals(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $query = Appeal::with(['appealable'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('appealable_type')) {
                $query->where('appealable_type', $request->appealable_type);
            }

            $appeals = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $appeals,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load user appeals', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load your appeals',
                'error_code' => 'my_appeals_load_failed',
            ], 500);
        }
    }

    /**
     * Handle approved appeal logic.
     */
    protected function handleApprovedAppeal(Appeal $appeal): void
    {
        $appealable = $appeal->appealable;

        if ($appealable instanceof Warning) {
            $appealable->update([
                'status' => 'overturned',
                'overturned_at' => now(),
                'overturned_by' => Auth::id(),
                'overturn_reason' => 'Appeal approved: ' . $appeal->review_notes,
            ]);
        } elseif ($appealable instanceof UserRestriction) {
            $appealable->update([
                'is_active' => false,
                'status' => 'lifted',
                'lifted_at' => now(),
                'lifted_by' => Auth::id(),
                'lift_reason' => 'Appeal approved: ' . $appeal->review_notes,
            ]);
        }
    }

    /**
     * Calculate approval rate.
     */
    protected function calculateApprovalRate($dateFrom, $dateTo): float
    {
        $totalReviewed = Appeal::whereIn('status', ['approved', 'denied'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        if ($totalReviewed === 0) {
            return 0;
        }

        $approved = Appeal::where('status', 'approved')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        return round(($approved / $totalReviewed) * 100, 2);
    }
}