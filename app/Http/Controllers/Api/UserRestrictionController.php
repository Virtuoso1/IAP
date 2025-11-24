<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserRestriction;
use App\Models\User;
use App\Models\Warning;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserRestrictionController extends Controller
{
    /**
     * Display a listing of user restrictions.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserRestriction::class);

        try {
            $query = UserRestriction::with(['user', 'moderator', 'appeals']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
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

            $restrictions = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $restrictions,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load user restrictions', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load user restrictions',
                'error_code' => 'restrictions_load_failed',
            ], 500);
        }
    }

    /**
     * Store a newly created user restriction.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', UserRestriction::class);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => ['required', Rule::in(['read_only', 'no_messaging', 'no_group_creation', 'temporary_ban', 'permanent_ban'])],
            'reason' => 'required|string|max:1000',
            'description' => 'nullable|string|max:2000',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'is_permanent' => 'boolean',
            'warning_id' => 'nullable|exists:warnings,id',
            'metadata' => 'nullable|array',
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            // Check if user already has an active restriction of this type
            $existingRestriction = UserRestriction::where('user_id', $request->user_id)
                ->where('type', $request->type)
                ->where('is_active', true)
                ->first();

            if ($existingRestriction) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has an active restriction of this type',
                    'error_code' => 'restriction_already_exists',
                    'data' => $existingRestriction,
                ], 422);
            }

            // Validate permanent ban requirements
            if ($request->type === 'permanent_ban') {
                $warningCount = Warning::where('user_id', $request->user_id)
                    ->where('status', 'active')
                    ->count();

                if ($warningCount < 3) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permanent ban requires at least 3 active warnings',
                        'error_code' => 'insufficient_warnings',
                    ], 422);
                }
            }

            // Calculate expiration date
            $expiresAt = null;
            if (!$request->boolean('is_permanent') && $request->duration_days) {
                $expiresAt = now()->addDays($request->duration_days);
            }

            DB::beginTransaction();

            $restriction = UserRestriction::create([
                'user_id' => $request->user_id,
                'moderator_id' => Auth::id(),
                'type' => $request->type,
                'reason' => $request->reason,
                'description' => $request->description,
                'is_permanent' => $request->boolean('is_permanent'),
                'expires_at' => $expiresAt,
                'warning_id' => $request->warning_id,
                'metadata' => $request->metadata ?? [],
            ]);

            // Log the restriction creation
            app(AuditLogService::class)->log(
                'user_restriction_created',
                'User restriction created',
                $restriction->id, // entity_id
                'UserRestriction', // entity_type
                Auth::id(), // actor_id
                [
                    'user_id' => $request->user_id,
                    'type' => $request->type,
                    'reason' => $request->reason,
                    'is_permanent' => $request->boolean('is_permanent'),
                    'expires_at' => $expiresAt,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User restriction created successfully',
                'data' => $restriction->load(['user', 'moderator']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create user restriction', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user restriction',
                'error_code' => 'restriction_creation_failed',
            ], 500);
        }
    }

    /**
     * Get available restriction types.
     */
    public function types(): JsonResponse
    {
        $this->authorize('viewAny', UserRestriction::class);

        $types = [
            'read_only' => [
                'name' => 'Read Only',
                'description' => 'User can only view content, cannot post or interact',
                'default_duration' => 7,
                'max_duration' => 30,
                'requires_warning' => false,
            ],
            'no_messaging' => [
                'name' => 'No Messaging',
                'description' => 'User cannot send private messages',
                'default_duration' => 7,
                'max_duration' => 30,
                'requires_warning' => false,
            ],
            'no_group_creation' => [
                'name' => 'No Group Creation',
                'description' => 'User cannot create new groups',
                'default_duration' => 14,
                'max_duration' => 90,
                'requires_warning' => false,
            ],
            'temporary_ban' => [
                'name' => 'Temporary Ban',
                'description' => 'User is temporarily banned from the platform',
                'default_duration' => 7,
                'max_duration' => 365,
                'requires_warning' => true,
            ],
            'permanent_ban' => [
                'name' => 'Permanent Ban',
                'description' => 'User is permanently banned from the platform',
                'default_duration' => null,
                'max_duration' => null,
                'requires_warning' => true,
                'min_warnings' => 3,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get restriction statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserRestriction::class);

        try {
            $dateFrom = $request->date_from ?? now()->subDays(30);
            $dateTo = $request->date_to ?? now();

            $stats = [
                'total_restrictions' => UserRestriction::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'active_restrictions' => UserRestriction::where('is_active', true)->count(),
                'by_type' => UserRestriction::selectRaw('type, COUNT(*) as count')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'by_status' => UserRestriction::selectRaw('status, COUNT(*) as count')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'permanent_vs_temporary' => [
                    'permanent' => UserRestriction::where('is_permanent', true)
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->count(),
                    'temporary' => UserRestriction::where('is_permanent', false)
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->count(),
                ],
                'average_duration' => UserRestriction::whereNotNull('expires_at')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('AVG(DATEDIFF(expires_at, created_at)) as avg_days')
                    ->value('avg_days'),
                'expiring_soon' => UserRestriction::where('is_active', true)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now()->addDays(7))
                    ->where('expires_at', '>', now())
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load restriction statistics', [
                'error' => $e->getMessage(),
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load restriction statistics',
                'error_code' => 'statistics_load_failed',
            ], 500);
        }
    }

    /**
     * Display the specified user restriction.
     */
    public function show(Request $request, $userId): JsonResponse
    {
        $this->authorize('view', UserRestriction::class);

        try {
            $restrictions = UserRestriction::with(['user', 'moderator', 'appeals'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($restrictions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No restrictions found for this user',
                    'error_code' => 'restrictions_not_found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $restrictions,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load user restrictions', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load user restrictions',
                'error_code' => 'restrictions_load_failed',
            ], 500);
        }
    }

    /**
     * Update the specified user restriction.
     */
    public function update(Request $request, $userId): JsonResponse
    {
        $this->authorize('update', UserRestriction::class);

        $request->validate([
            'restriction_id' => 'required|exists:user_restrictions,id',
            'reason' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:2000',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'is_permanent' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        try {
            $restriction = UserRestriction::where('id', $request->restriction_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Check if restriction can be modified
            if ($restriction->status === 'expired' || $restriction->status === 'lifted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify expired or lifted restriction',
                    'error_code' => 'restriction_not_modifiable',
                ], 422);
            }

            DB::beginTransaction();

            $updates = [];

            if ($request->has('reason')) {
                $updates['reason'] = $request->reason;
            }

            if ($request->has('description')) {
                $updates['description'] = $request->description;
            }

            if ($request->has('duration_days')) {
                $updates['expires_at'] = now()->addDays($request->duration_days);
                $updates['is_permanent'] = false;
            }

            if ($request->has('is_permanent')) {
                $updates['is_permanent'] = $request->boolean('is_permanent');
                if ($request->boolean('is_permanent')) {
                    $updates['expires_at'] = null;
                }
            }

            if ($request->has('metadata')) {
                $updates['metadata'] = $request->metadata;
            }

            if (!empty($updates)) {
                $restriction->update($updates);

                // Log the restriction update
                app(AuditLogService::class)->log(
                    'user_restriction_updated',
                    'User restriction updated',
                    $restriction->id, // entity_id
                    'UserRestriction', // entity_type
                    Auth::id(), // actor_id
                    $updates
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User restriction updated successfully',
                'data' => $restriction->fresh()->load(['user', 'moderator']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update user restriction', [
                'error' => $e->getMessage(),
                'restriction_id' => $request->restriction_id,
                'user_id' => $userId,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user restriction',
                'error_code' => 'restriction_update_failed',
            ], 500);
        }
    }

    /**
     * Remove the specified user restriction.
     */
    public function destroy(Request $request, $userId): JsonResponse
    {
        $this->authorize('delete', UserRestriction::class);

        $request->validate([
            'restriction_id' => 'required|exists:user_restrictions,id',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $restriction = UserRestriction::where('id', $request->restriction_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            if (!$restriction->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Restriction is already inactive',
                    'error_code' => 'restriction_already_inactive',
                ], 422);
            }

            DB::beginTransaction();

            $restriction->update([
                'is_active' => false,
                'status' => 'lifted',
                'lifted_at' => now(),
                'lifted_by' => Auth::id(),
                'lift_reason' => $request->reason,
            ]);

            // Log the restriction lift
            app(AuditLogService::class)->log(
                'user_restriction_lifted',
                'User restriction lifted',
                $restriction->id, // entity_id
                'UserRestriction', // entity_type
                Auth::id(), // actor_id
                [
                    'lift_reason' => $request->reason,
                    'lifted_by' => Auth::id(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User restriction lifted successfully',
                'data' => $restriction->fresh()->load(['user', 'moderator']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to lift user restriction', [
                'error' => $e->getMessage(),
                'restriction_id' => $request->restriction_id,
                'user_id' => $userId,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to lift user restriction',
                'error_code' => 'restriction_lift_failed',
            ], 500);
        }
    }

    /**
     * Lift a user restriction.
     */
    public function lift(Request $request, $userId): JsonResponse
    {
        return $this->destroy($request, $userId);
    }

    /**
     * Extend a user restriction.
     */
    public function extend(Request $request, $userId): JsonResponse
    {
        $this->authorize('update', UserRestriction::class);

        $request->validate([
            'restriction_id' => 'required|exists:user_restrictions,id',
            'duration_days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $restriction = UserRestriction::where('id', $request->restriction_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            if (!$restriction->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot extend inactive restriction',
                    'error_code' => 'restriction_not_active',
                ], 422);
            }

            if ($restriction->is_permanent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot extend permanent restriction',
                    'error_code' => 'restriction_permanent',
                ], 422);
            }

            DB::beginTransaction();

            $newExpiresAt = $restriction->expires_at 
                ? $restriction->expires_at->addDays($request->duration_days)
                : now()->addDays($request->duration_days);

            $restriction->update([
                'expires_at' => $newExpiresAt,
                'extended_at' => now(),
                'extended_by' => Auth::id(),
                'extension_reason' => $request->reason,
            ]);

            // Log the restriction extension
            app(AuditLogService::class)->log(
                'user_restriction_extended',
                'User restriction extended',
                $restriction->id, // entity_id
                'UserRestriction', // entity_type
                Auth::id(), // actor_id
                [
                    'duration_days' => $request->duration_days,
                    'extension_reason' => $request->reason,
                    'new_expires_at' => $newExpiresAt,
                    'extended_by' => Auth::id(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User restriction extended successfully',
                'data' => $restriction->fresh()->load(['user', 'moderator']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to extend user restriction', [
                'error' => $e->getMessage(),
                'restriction_id' => $request->restriction_id,
                'user_id' => $userId,
                'moderator_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to extend user restriction',
                'error_code' => 'restriction_extension_failed',
            ], 500);
        }
    }
}