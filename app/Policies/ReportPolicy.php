<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportPolicy
{
    /**
     * Determine if the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->canModerate();
    }

    /**
     * Determine if the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        return $user->canModerate();
    }

    /**
     * Determine if the user can create reports.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create reports
    }

    /**
     * Determine if the user can update the report.
     */
    public function update(User $user, Report $report): bool
    {
        return $user->canModerate();
    }

    /**
     * Determine if the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->canModerate();
    }

    /**
     * Determine if the user can resolve the report.
     */
    public function resolve(User $user, Report $report): bool
    {
        return $user->canModerate();
    }

    /**
     * Determine if the user can view statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->canModerate();
    }
}