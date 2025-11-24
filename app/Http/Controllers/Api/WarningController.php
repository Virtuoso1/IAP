<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWarningRequest;
use App\Http\Requests\Api\UpdateWarningRequest;
use App\Models\Warning;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarningController extends Controller
{
    /**
     * Display a listing of warnings.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Warning::class);

        $warnings = Warning::with(['user', 'moderator', 'appeals'])
            ->when($request->user_id, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($request->moderator_id, function ($query, $moderatorId) {
                $query->where('moderator_id', $moderatorId);
            })
            ->when($request->level, function ($query, $level) {
                $query->where('level', $level);
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
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
            'data' => $warnings->items(),
            'meta' => [
                'current_page' => $warnings->currentPage(),
                'last_page' => $warnings->lastPage(),
                'per_page' => $warnings->perPage(),
                'total' => $warnings->total(),
            ],
        ]);
    }

    /**
     * Store a newly created warning.
     */
    public function store(StoreWarningRequest $request): JsonResponse
    {
        $this->authorize('create', Warning::class);

        try {
            DB::beginTransaction();

            $warning = Warning::create([
                'user_id' => $request->user_id,
                'moderator_id' => Auth::id(),
                'level' => $request->level,
                'type' => $request->type,
                'reason' => $request->reason,
                'description' => $request->description,
                'expires_at' => $request->expires_at,
                'metadata' => array_merge($request->metadata ?? [], [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'client_version' => $request->header('X-Client-Version'),
                ]),
            ]);

            // Check for automatic escalation
            $this->checkAutomaticEscalation($warning);

            DB::commit();

            $warning->load(['user', 'moderator']);

            return response()->json([
                'success' => true,
                'message' => 'Warning issued successfully',
                'data' => $warning,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create warning', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to issue warning',
                'error_code' => 'warning_creation_failed',
            ], 500);
        }
    }

    /**
     * Display the specified warning.
     */
    public function show(Warning $warning): JsonResponse
    {
        $this->authorize('view', $warning);

        $warning->load(['user', 'moderator', 'appeals']);

        return response()->json([
            'success' => true,
            'data' => $warning,
        ]);
    }

    /**
     * Update the specified warning.
     */
    public function update(UpdateWarningRequest $request, Warning $warning): JsonResponse
    {
        $this->authorize('update', $warning);

        try {
            $warning->update($request->validated());

            $warning->load(['user', 'moderator', 'appeals']);

            return response()->json([
                'success' => true,
                'message' => 'Warning updated successfully',
                'data' => $warning,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update warning', [
                'error' => $e->getMessage(),
                'warning_id' => $warning->id,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update warning',
                'error_code' => 'warning_update_failed',
            ], 500);
        }
    }

    /**
     * Remove the specified warning.
     */
    public function destroy(Warning $warning): JsonResponse
    {
        $this->authorize('delete', $warning);

        try {
            $warning->delete();

            return response()->json([
                'success' => true,
                'message' => 'Warning deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete warning', [
                'error' => $e->getMessage(),
                'warning_id' => $warning->id,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete warning',
                'error_code' => 'warning_deletion_failed',
            ], 500);
        }
    }

    /**
     * Escalate a warning to higher level.
     */
    public function escalate(Request $request, Warning $warning): JsonResponse
    {
        $this->authorize('escalate', $warning);

        $request->validate([
            'escalation_reason' => 'required|string|max:1000',
            'new_level' => 'required|in:medium,high,critical',
            'senior_moderator_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $warning->update([
                'level' => $request->new_level,
                'status' => 'escalated',
                'escalated_at' => now(),
                'escalated_by' => Auth::id(),
                'metadata' => array_merge($warning->metadata ?? [], [
                    'escalation_reason' => $request->escalation_reason,
                    'escalated_to_level' => $request->new_level,
                    'escalated_by' => Auth::id(),
                ]),
            ]);

            // Create escalation record
            $warning->escalations()->create([
                'from_level' => $warning->level,
                'to_level' => $request->new_level,
                'reason' => $request->escalation_reason,
                'escalated_by' => Auth::id(),
                'senior_moderator_id' => $request->senior_moderator_id,
            ]);

            DB::commit();

            $warning->load(['user', 'moderator', 'escalations']);

            return response()->json([
                'success' => true,
                'message' => 'Warning escalated successfully',
                'data' => $warning,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to escalate warning', [
                'error' => $e->getMessage(),
                'warning_id' => $warning->id,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to escalate warning',
                'error_code' => 'warning_escalation_failed',
            ], 500);
        }
    }

    /**
     * Acknowledge a warning by the user.
     */
    public function acknowledge(Request $request, Warning $warning): JsonResponse
    {
        $this->authorize('acknowledge', $warning);

        if ($warning->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only acknowledge your own warnings',
                'error_code' => 'unauthorized_acknowledgment',
            ], 403);
        }

        try {
            $warning->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledgment_ip' => $request->ip(),
                'acknowledgment_user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Warning acknowledged successfully',
                'data' => $warning,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to acknowledge warning', [
                'error' => $e->getMessage(),
                'warning_id' => $warning->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge warning',
                'error_code' => 'warning_acknowledgment_failed',
            ], 500);
        }
    }

    /**
     * Get warning statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Warning::class);

        $dateFrom = $request->date_from ?? now()->subDays(30);
        $dateTo = $request->date_to ?? now();

        $stats = [
            'total_warnings' => Warning::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'active_warnings' => Warning::where('status', 'active')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'acknowledged_warnings' => Warning::where('status', 'acknowledged')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'escalated_warnings' => Warning::where('status', 'escalated')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'expired_warnings' => Warning::where('status', 'expired')->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'warnings_by_level' => Warning::selectRaw('level, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('level')
                ->pluck('count', 'level'),
            'warnings_by_type' => Warning::selectRaw('type, COUNT(*) as count')
                ->with('user:name')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('type')
                ->get(),
            'average_resolution_time' => Warning::whereNotNull('acknowledged_at')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, acknowledged_at)) as avg_hours')
                ->value('avg_hours'),
            'top_warned_users' => User::selectRaw('users.id, users.name, COUNT(warnings.id) as warning_count')
                ->join('warnings', 'users.id', '=', 'warnings.user_id')
                ->whereBetween('warnings.created_at', [$dateFrom, $dateTo])
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('warning_count')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Check for automatic escalation based on user warning count.
     */
    protected function checkAutomaticEscalation(Warning $warning): void
    {
        $user = $warning->user;
        $activeWarnings = $user->warnings()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        // Escalation rules
        $escalationRules = [
            3 => ['level' => 'high', 'auto_restrict' => true],
            5 => ['level' => 'critical', 'auto_restrict' => true],
            7 => ['level' => 'critical', 'auto_ban' => true],
        ];

        foreach ($escalationRules as $threshold => $action) {
            if ($activeWarnings >= $threshold) {
                if ($warning->level !== $action['level']) {
                    $warning->update(['level' => $action['level']]);
                }

                if (isset($action['auto_restrict']) && $action['auto_restrict']) {
                    $this->applyAutomaticRestriction($user, $warning);
                }

                if (isset($action['auto_ban']) && $action['auto_ban']) {
                    $this->applyAutomaticBan($user, $warning);
                }

                break;
            }
        }
    }

    /**
     * Apply automatic restriction.
     */
    protected function applyAutomaticRestriction(User $user, Warning $warning): void
    {
        try {
            $user->restrictions()->create([
                'type' => 'posting_restriction',
                'reason' => 'Automatic restriction due to warning escalation',
                'moderator_id' => $warning->moderator_id,
                'expires_at' => now()->addDays(7),
                'is_permanent' => false,
                'metadata' => [
                    'auto_applied' => true,
                    'warning_id' => $warning->id,
                    'escalation_threshold' => 3,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to apply automatic restriction', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'warning_id' => $warning->id,
            ]);
        }
    }

    /**
     * Apply automatic ban.
     */
    protected function applyAutomaticBan(User $user, Warning $warning): void
    {
        try {
            $user->restrictions()->create([
                'type' => 'full_ban',
                'reason' => 'Automatic ban due to excessive warnings',
                'moderator_id' => $warning->moderator_id,
                'is_permanent' => true,
                'metadata' => [
                    'auto_applied' => true,
                    'warning_id' => $warning->id,
                    'escalation_threshold' => 7,
                ],
            ]);

            // Deactivate user account
            $user->update(['is_active' => false]);
        } catch (\Exception $e) {
            Log::error('Failed to apply automatic ban', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'warning_id' => $warning->id,
            ]);
        }
    }
}