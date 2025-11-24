<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Warning;
use App\Models\UserRestriction;
use App\Models\Appeal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @method bool canModerate()
 */
class ModerationController extends Controller
{
    /**
     * Display the moderation dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        if (!$user || !($user instanceof \App\Models\User) || !$user->canModerate()) {
            abort(403, 'Unauthorized access to moderation dashboard');
        }

        $stats = [
            'pending_reports' => Report::where('status', 'pending')->count(),
            'active_warnings' => Warning::where('is_active', true)->count(),
            'active_restrictions' => UserRestriction::where('is_active', true)->count(),
            'pending_appeals' => Appeal::where('status', 'pending')->count(),
        ];

        $recentReports = Report::with(['reporter', 'reportedUser', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentWarnings = Warning::with(['user', 'moderator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('moderation.dashboard', compact('stats', 'recentReports', 'recentWarnings'));
    }

    /**
     * Display all reports.
     */
    public function reports(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !($user instanceof \App\Models\User) || !$user->canModerate()) {
            abort(403, 'Unauthorized access to reports');
        }

        $reports = Report::with(['reporter', 'reportedUser', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('moderation.reports', compact('reports'));
    }

    /**
     * Display a specific report.
     */
    public function showReport($id)
    {
        $user = Auth::user();
        
        if (!$user || !($user instanceof \App\Models\User) || !$user->canModerate()) {
            abort(403, 'Unauthorized access to report');
        }

        $report = Report::with(['reporter', 'reportedUser', 'category', 'evidence', 'warnings'])
            ->findOrFail($id);

        return view('moderation.show-report', compact('report'));
    }

    /**
     * Display all warnings.
     */
    public function warnings(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !($user instanceof \App\Models\User) || !$user->canModerate()) {
            abort(403, 'Unauthorized access to warnings');
        }

        $warnings = Warning::with(['user', 'moderator', 'report'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('moderation.warnings', compact('warnings'));
    }

    /**
     * Display a specific warning.
     */
    public function showWarning($id)
    {
        $user = Auth::user();
        
        if (!$user || !($user instanceof \App\Models\User) || !$user->canModerate()) {
            abort(403, 'Unauthorized access to warning');
        }

        $warning = Warning::with(['user', 'moderator', 'report', 'appeals'])
            ->findOrFail($id);

        return view('moderation.show-warning', compact('warning'));
    }

    /**
     * Display user restrictions.
     */
    public function restrictions(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !($user instanceof \App\Models\User) || !$user->canModerate()) {
            abort(403, 'Unauthorized access to restrictions');
        }

        $restrictions = UserRestriction::with(['user', 'moderator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('moderation.restrictions', compact('restrictions'));
    }

    /**
     * Display appeals.
     */
    public function appeals(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !($user instanceof \App\Models\User) || !$user->canModerate()) {
            abort(403, 'Unauthorized access to appeals');
        }

        $appeals = Appeal::with(['user', 'warning', 'moderator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('moderation.appeals', compact('appeals'));
    }
}