<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ModeratorAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication required',
                'code' => 'auth_required'
            ], 401);
        }

        // Check if user has moderator privileges
        if (!$this->hasModeratorPrivileges($user)) {
            return response()->json([
                'message' => 'Insufficient privileges. Moderator access required.',
                'code' => 'insufficient_privileges'
            ], 403);
        }

        // Check if moderator account is active
        if (!$this->isModeratorAccountActive($user)) {
            return response()->json([
                'message' => 'Moderator account is suspended or inactive',
                'code' => 'account_suspended'
            ], 403);
        }

        // Log moderator access for audit
        $this->logModeratorAccess($user, $request);

        return $next($request);
    }

    /**
     * Check if user has moderator privileges.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    protected function hasModeratorPrivileges($user): bool
    {
        // Check role-based permissions
        if ($user->hasRole('admin') || $user->hasRole('moderator')) {
            return true;
        }

        // Check specific permission
        if ($user->hasPermission('moderate_content')) {
            return true;
        }

        // Check if user can moderate (using the canModerate method)
        return $user->canModerate();
    }

    /**
     * Check if moderator account is active.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    protected function isModeratorAccountActive($user): bool
    {
        // Check if user has any active restrictions
        $activeRestrictions = $user->restrictions()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereIn('type', ['moderation_suspension', 'full_ban'])
            ->exists();

        return !$activeRestrictions;
    }

    /**
     * Log moderator access for audit purposes.
     *
     * @param  \App\Models\User  $user
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function logModeratorAccess($user, Request $request): void
    {
        \App\Services\AuditLogService::log(
            'moderator_access',
            'Moderator accessed protected resource',
            null, // entity_id
            null, // entity_type
            $user->id, // actor_id
            [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'route' => $request->route()->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ]
        );
    }
}