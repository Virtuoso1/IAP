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
        
        // Check if user has permission to edit
        // Allow: owner, group admin, site moderator, or any group member
        $isOwner = $group->owner_id === $user->id;
        $isGroupAdmin = $group->isAdmin($user->id);
        $isSiteModerator = $user->role === 'moderator';
        $isMember = $group->hasMember($user->id);
        
        // Log for debugging
        Log ::info('Edit permission check', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'is_owner' => $isOwner,
            'is_group_admin' => $isGroupAdmin,
            'is_site_moderator' => $isSiteModerator,
            'is_member' => $isMember
        ]);
        
        // Allow if user is owner, group admin, site moderator, OR member
        if (!$isOwner && !$isGroupAdmin && !$isSiteModerator && !$isMember) {
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
        
        // Only allow owner, group admin, or site moderator to delete
        $isOwner = $group->owner_id === $user->id;
        $isGroupAdmin = $group->isAdmin($user->id);
        $isSiteModerator = $user->role === 'moderator';
        
        if (!$isOwner && !$isGroupAdmin && !$isSiteModerator) {
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
        // Check if user is a member
        $user = Auth::user();
        if (!$group->hasMember($user->id)) {
            return redirect()->route('groups.index')
                ->with('error', 'You must be a member to view this group.');
        }
        
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
        // Add creator as member with admin role
        $group->members()->attach($user->id, [
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        return redirect()->route('groups.show', $group->id)->with('success', 'Group created successfully!');
    }
    
    /**
     * Update the specified group in storage.
     */
    public function update(Request $request, Group $group)
    {
        $user = Auth::user();
        // Check if user has permission to update
        $isOwner = $group->owner_id === $user->id;
        $isGroupAdmin = $group->isAdmin($user->id);
        $isSiteModerator = $user->role === 'moderator';
        if (!$isOwner && !$isGroupAdmin && !$isSiteModerator) {
            return back()->with('error', 'You do not have permission to update this group.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        $group->update($validated);
        return redirect()->route('groups.show', $group->id)
            ->with('success', 'Group updated successfully!');
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
                \Log::info('User ID: ' . ($user ? $user->id : 'null'));
                \Log::info('Group ID: ' . $group->id);
                \Log::info('Membership exists: ' . ($group->hasMember($user->id) ? 'yes' : 'no'));

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
    
    /**
     * Invite a user to join a group.
     */
    public function invite(Request $request, Group $group)
    {
        $user = Auth::user();
        
        // Check if the current user is a member with admin role or is the owner
        $isMember = $group->members()->where('user_id', $user->id)->exists();
        $isAdmin = $group->members()->where('user_id', $user->id)->where('group_user.role', 'admin')->exists();
        
        if (!$isMember || (!$isAdmin && $group->owner_id !== $user->id)) {
            return back()->with('error', 'You do not have permission to invite users to this group.');
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        
        $invitedUserId = $request->user_id;
        
        // Check if user is already a member
        if ($group->members()->where('user_id', $invitedUserId)->exists()) {
            return back()->with('error', 'This user is already a member of the group.');
        }
        
        // Add user to group
        $group->members()->attach($invitedUserId, [
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        
        $invitedUser = \App\Models\User::find($invitedUserId);
        
        return back()->with('success', $invitedUser->username . ' has been added to the group!');
    }
}
