<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GroupUserController extends Controller
{
    // Add a user to a group by username
    public function store(Request $request, Group $group)
    {
        \Log::info('Add member request', ['group_id' => $group->id, 'username' => $request->username]);
        $request->validate([
            'username' => 'required|string|exists:users,username',
        ]);
        $user = User::where('username', $request->username)->firstOrFail();
        \Log::info('User found', ['user_id' => $user->id]);
        // Only allow adding if not already a member
        if ($group->members()->where('user_id', $user->id)->exists()) {
            \Log::info('User already a member', ['user_id' => $user->id, 'group_id' => $group->id]);
            return back()->with('error', 'User is already a member.');
        }
        $group->members()->attach($user->id, [
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);
        \Log::info('User attached to group', ['user_id' => $user->id, 'group_id' => $group->id]);
        return back()->with('success', 'User added to group!');
    }

    // Remove a user from a group
    public function destroy(Group $group, User $user)
    {
        // Only allow removal if user is a member
            $user = Auth::user();
            $member = $group->members()->where('user_id', $user->id)->first();

            // Only allow admin or owner to remove members
            if (!$group->isAdmin($user->id) && $group->owner_id !== $user->id) {
                return back()->with('error', 'You do not have permission to remove members.');
            }

            // Prevent removing owner
            if ($group->owner_id == $user->id) {
                return back()->with('error', 'You cannot remove the group owner.');
            }

            // Prevent removing an admin unless you are the owner
            if ($group->isAdmin($user->id) && $group->owner_id !== $user->id) {
                return back()->with('error', 'Only the group owner can remove an admin.');
            }

            $group->members()->detach($user->id);
            return back()->with('success', 'Member removed successfully.');
    }
}
