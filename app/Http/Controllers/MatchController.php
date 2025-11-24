<?php

namespace App\Http\Controllers;

use App\Models\UserMatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{

    /**
     * Display a listing of the user's matches.
     */
    public function index()
    {
        $user = Auth::user();
        $matches = $user->allMatches()->with(['seeker', 'helper'])->latest()->get();
        
        return view('matches.index', compact('matches'));
    }

    /**
     * Show the form for creating a new match.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Only seekers can create matches
        if (!$user->isSeeker()) {
            return redirect()->route('matches.index')
                ->with('error', 'Only seekers can create matches.');
        }
        
        $availableHelpers = User::getAvailableHelpers();
        
        return view('matches.create', compact('availableHelpers'));
    }

    /**
     * Store a newly created match in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Only seekers can create matches
        if (!$user->isSeeker()) {
            return redirect()->route('matches.index')
                ->with('error', 'Only seekers can create matches.');
        }
        
        $request->validate([
            'helper_id' => 'nullable|exists:users,id|different:' . $user->id,
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Check if a match already exists with the selected helper (if provided)
        if ($request->helper_id) {
            $existingMatch = UserMatch::where(function($query) use ($user, $request) {
                $query->where('seeker_id', $user->id)
                      ->where('helper_id', $request->helper_id);
            })->orWhere(function($query) use ($user, $request) {
                $query->where('seeker_id', $request->helper_id)
                      ->where('helper_id', $user->id);
            })->first();
            
            if ($existingMatch) {
                return redirect()->route('matches.index')
                    ->with('error', 'A match already exists between you and this user.');
            }
        }
        
        $match = UserMatch::create([
            'seeker_id' => $user->id,
            'helper_id' => $request->helper_id,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);
        
        return redirect()->route('matches.show', $match)
            ->with('success', 'Match created successfully!');
    }

    /**
     * Display the specified match.
     */
    public function show(UserMatch $match)
    {
        $user = Auth::user();
        
        // Check if user is part of this match or if it's a pending request without a helper (for helpers to view)
        if ($match->seeker_id !== $user->id && $match->helper_id !== $user->id) {
            // Allow helpers to view pending matches without assigned helpers
            if (!($user->isHelper() && $match->helper_id === null && $match->isPending())) {
                abort(403, 'Unauthorized action.');
            }
        }
        
        $match->load(['seeker', 'helper']);
        
        return view('matches.show', compact('match'));
    }

    /**
     * Activate a pending match.
     */
    public function activate(UserMatch $match)
    {
        $user = Auth::user();
        
        // Only helpers can activate matches
        if ($match->helper_id !== $user->id) {
            return redirect()->route('matches.show', $match)
                ->with('error', 'Only the helper can activate this match.');
        }
        
        if (!$match->canBeActivated()) {
            return redirect()->route('matches.show', $match)
                ->with('error', 'This match cannot be activated.');
        }
        
        $match->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
        
        return redirect()->route('matches.show', $match)
            ->with('success', 'Match activated successfully!');
    }

    /**
     * Complete an active match.
     */
    public function complete(UserMatch $match)
    {
        $user = Auth::user();
        
        // Both seeker and helper can complete matches
        if ($match->seeker_id !== $user->id && $match->helper_id !== $user->id) {
            return redirect()->route('matches.show', $match)
                ->with('error', 'You are not part of this match.');
        }
        
        if (!$match->canBeCompleted()) {
            return redirect()->route('matches.show', $match)
                ->with('error', 'This match cannot be completed.');
        }
        
        $match->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);
        
        return redirect()->route('matches.show', $match)
            ->with('success', 'Match completed successfully!');
    }

    /**
     * Cancel a match.
     */
    public function cancel(UserMatch $match)
    {
        $user = Auth::user();
        
        // Check if user is part of this match
        if ($match->seeker_id !== $user->id && $match->helper_id !== $user->id) {
            return redirect()->route('matches.show', $match)
                ->with('error', 'You are not part of this match.');
        }
        
        if (!$match->canBeCancelled()) {
            return redirect()->route('matches.show', $match)
                ->with('error', 'This match cannot be cancelled.');
        }
        
        $match->update([
            'status' => 'cancelled',
            'ended_at' => now(),
        ]);
        
        return redirect()->route('matches.show', $match)
            ->with('success', 'Match cancelled successfully!');
    }

    /**
     * Show available helpers for all users.
     */
    public function helpers()
    {
        $availableHelpers = User::getAvailableHelpers();
        
        return view('matches.helpers', compact('availableHelpers'));
    }
    
    /**
     * Show pending match requests (for helpers only).
     */
    public function pending()
    {
        $user = Auth::user();
        
        // Only helpers can view pending requests
        if (!$user->isHelper()) {
            return redirect()->route('matches.index')
                ->with('error', 'Only helpers can view pending match requests.');
        }
        
        $pendingMatches = UserMatch::whereNull('helper_id')
            ->where('status', 'pending')
            ->with('seeker')
            ->latest()
            ->get();
        
        return view('matches.pending', compact('pendingMatches'));
    }
    
    /**
     * Accept a pending match request (for helpers).
     */
    public function accept(UserMatch $match)
    {
        $user = Auth::user();
        
        // Only helpers can accept matches
        if (!$user->isHelper()) {
            return redirect()->route('matches.pending')
                ->with('error', 'Only helpers can accept match requests.');
        }
        
        // Prevent users from accepting their own match requests
        if ($match->seeker_id === $user->id) {
            return redirect()->route('matches.pending')
                ->with('error', 'You cannot accept your own match request.');
        }
        
        // Check if match already has a helper
        if ($match->helper_id !== null) {
            return redirect()->route('matches.pending')
                ->with('error', 'This match request has already been accepted.');
        }
        
        // Check if match is pending
        if (!$match->isPending()) {
            return redirect()->route('matches.pending')
                ->with('error', 'This match request is no longer available.');
        }
        
        // Assign the helper to the match
        $match->update([
            'helper_id' => $user->id,
            'status' => 'active',
            'started_at' => now(),
        ]);
        
        return redirect()->route('matches.show', $match)
            ->with('success', 'Match request accepted! You are now helping this seeker.');
    }
}