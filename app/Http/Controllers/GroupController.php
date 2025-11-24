<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    /**
     * Show the form for editing the specified group.
     */
    public function edit(Group $group)
    {
        $user = Auth::user();
        // Allow owner, admin, or moderator to edit
        $isAdmin = $group->members()->where('user_id', $user->id)->where('group_user.role', 'admin')->exists();
        $isModerator = property_exists($user, 'role') && $user->role === 'moderator';
        if ($group->owner_id !== $user->id && !$isAdmin && !$isModerator) {
            return back()->with('error', 'You do not have permission to edit this group.');
        }
        return view('groups.edit', compact('group'));
    }
    /**
     * Delete a group.
     */
    public function destroy(Group $group)
    {
        $user = Auth::user();
        // Only allow owner or admin to delete
        if ($group->owner_id !== $user->id && !$group->members()->where('user_id', $user->id)->where('role', 'admin')->exists()) {
            return back()->with('error', 'You do not have permission to delete this group.');
        }
        $group->delete();
        return redirect()->route('groups.index')->with('success', 'Group deleted successfully!');
    }
    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        return view('groups.create');
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group)
    {
        return view('groups.show', compact('group'));
    }
    /**
     * List all groups and member counts.
     */
    public function index()
    {
        $user = Auth::user();
        $groups = Group::withCount('members')->get();
        $joinableGroups = Group::whereDoesntHave('members', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get();
        return view('groups.index', compact('groups', 'joinableGroups'));
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'owner_id' => $user->id,
        ]);
        // Add creator as member
        $group->members()->attach($user->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        return redirect()->route('groups.show', $group->id)->with('success', 'Group created successfully!');
    }

    /**
     * Get recent messages (for polling/AJAX).
     */
    public function recent(Request $request, Group $group)
    {
        $user = Auth::user();

        // Check if user is a member
        if (!$group->hasMember($user->id)) {
            abort(403, 'You must be a member to view messages.');
        }

        $lastMessageId = $request->input('last_message_id', 0);

        $messages = $group->messages()
            ->with('user:id,username')
            ->where('id', '>', $lastMessageId)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Load older messages (for infinite scroll).
     */
    public function older(Request $request, Group $group)
    {
        $user = Auth::user();

        // Check if user is a member
        if (!$group->hasMember($user->id)) {
            abort(403, 'You must be a member to view messages.');
        }

        $oldestMessageId = $request->input('oldest_message_id');

        $messages = $group->messages()
            ->with('user:id,username')
            ->where('id', '<', $oldestMessageId)
            ->latest()
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages,
            'has_more' => $messages->count() === 20,
        ]);
    }

    /**
     * Update a message (edit).
     */
    public function update(Request $request, Group $group, GroupMessage $message)
    {
        $user = Auth::user();

        // Check if user owns the message
        if ($message->user_id !== $user->id) {
            abort(403, 'You can only edit your own messages.');
        }

        // Check if message belongs to this group
        if ($message->group_id !== $group->id) {
            abort(404, 'Message not found in this group.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', 'Message updated!');
    }


    /**
     * Get message count for a group.
     */
    public function count(Group $group)
    {
        $user = Auth::user();

        // Check if user is a member
        if (!$group->hasMember($user->id)) {
            abort(403);
        }

        $count = $group->messages()->count();

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Search messages in a group.
     */
    public function search(Request $request, Group $group)
    {
        $user = Auth::user();

        // Check if user is a member
        if (!$group->hasMember($user->id)) {
            abort(403, 'You must be a member to search messages.');
        }

        $query = $request->input('query');

        if (empty($query)) {
            return response()->json(['messages' => []]);
        }

        $messages = $group->messages()
            ->with('user:id,username')
            ->where('content', 'like', '%' . $query . '%')
            ->latest()
            ->limit(50)
            ->get();
        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Display messages for a group.
     */
    public function messages(Group $group)
    {
        $user = Auth::user();
        // Debug output
                Log::info('User ID: ' . ($user ? $user->id : 'null'));
                Log::info('Group ID: ' . $group->id);
                Log::info('Membership exists: ' . ($group->hasMember($user->id) ? 'yes' : 'no'));

                // Check if user is a member
                if (!$group->hasMember($user->id)) {
                    abort(403, 'You must be a member to view group messages.');
                }

                // Get messages with user info
                $messages = $group->messages()
                    ->with('user:id,username')
                    ->latest()
                    ->paginate(50);

        return view('groups.messages.index', compact('group', 'messages'));
    }
}