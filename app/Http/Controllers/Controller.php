<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get the authenticated user.
     */
    protected function getCurrentUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Check if current user is moderator.
     */
    protected function isModerator(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->isModerator();
    }

    /**
     * Check if current user is admin.
     */
    protected function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->isAdmin();
    }

    /**
     * Custom authorization method for moderation actions.
     */
    protected function authorizeModeration(string $action, $resource = null): bool
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }

        // Admin can do everything
        if ($user->isAdmin()) {
            return true;
        }

        // Check specific permissions
        switch ($action) {
            case 'view_reports':
            case 'moderate_reports':
                return $user->isModerator();
            
            case 'issue_warnings':
            case 'apply_restrictions':
                return $user->isModerator();
            
            case 'view_dashboard':
                return $user->isModerator();
            
            case 'delete_reports':
            case 'permanent_ban':
                return $user->hasRole('senior_moderator') || $user->isAdmin();
            
            default:
                return false;
        }
    }

    /**
     * Override authorize method to handle custom authorization.
     */
    protected function authorize($ability, $arguments = []): void
    {
        // Use Laravel's built-in authorization if policies exist
        if (method_exists(parent::class, 'authorize')) {
            parent::authorize($ability, $arguments);
            return;
        }

        // Fallback to custom authorization
        if (!$this->authorizeModeration($ability, $arguments)) {
            abort(403, 'This action is unauthorized.');
        }
    }
}
