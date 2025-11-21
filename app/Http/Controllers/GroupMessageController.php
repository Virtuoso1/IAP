<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupMessageController extends Controller
{
    /**
     * Display messages for a group.
     */
    public function index(Group $group)
    {
        $user = Auth::user();
        \Log::info('User ID: ' . ($user ? $user->id : 'none'));
        \Log::info('Group ID: ' . ($group ? $group->id : 'none'));

        // Check if user is a member
        $isMember = $group->hasMember($user->id);
        \Log::info('Membership exists: ' . ($isMember ? 'yes' : 'no'));
        if (!$isMember) {
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
     * Store a new message in the group.
     */
    public function store(Request $request, Group $group)
    {
        $user = Auth::user();
        \Log::info('Message store request', ['group_id' => $group->id, 'user_id' => $user->id, 'content' => $request->content]);

        // Check if user account is active
        if ($user->status === 'banned') {
            \Log::warning('Banned user tried to send message', ['user_id' => $user->id]);
            abort(403, 'Banned users cannot send messages.');
        }

        if ($user->status === 'suspended') {
            \Log::warning('Suspended user tried to send message', ['user_id' => $user->id]);
            abort(403, 'Suspended users cannot send messages.');
        }

        // Check if user is a member
        if (!$group->hasMember($user->id)) {
            \Log::warning('Non-member tried to send message', ['user_id' => $user->id, 'group_id' => $group->id]);
            abort(403, 'You must be a member to send messages.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message = GroupMessage::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);
        \Log::info('Message created', ['message_id' => $message->id]);

        // Load user relationship for response
        $message->load('user:id,username');

        // If AJAX request, return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', 'Message sent!');
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
     * Delete a message (soft delete).
     */
    public function destroy(Group $group, GroupMessage $message)
    {
        $user = Auth::user();

        // Check if user owns the message, is a group admin, or is a moderator
        $canDelete = ($message->user_id === $user->id) 
                     || $group->isAdmin($user->id) 
                     || ($user->role === 'moderator');
        
        if (!$canDelete) {
            abort(403, 'You can only delete your own messages or be an admin/moderator.');
        }

        // Check if message belongs to this group
        if ($message->group_id !== $group->id) {
            abort(404, 'Message not found in this group.');
        }

        $message->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Message deleted.',
            ]);
        }

        return back()->with('success', 'Message deleted!');
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
}