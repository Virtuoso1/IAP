<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\BlockedUser;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Send a direct message from authenticated user to another user.
     */
    public function sendMessage(Request $request)
    {
        // Validate input
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        $senderId = auth()->id(); // assuming user is logged in
        $receiverId = $request->receiver_id;
        $content = $request->input('content');

        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        // Check if receiver has blocked sender
        if (BlockedUser::where('user_id', $receiverId)
                       ->where('blocked_user_id', $senderId)
                       ->exists()) {
            return response()->json([
                'error' => 'Cannot send message to this user.'
            ], 403);
        }

        // Prevent two high-risk users from messaging each other
        if ($sender->risk_level === 'high' && $receiver->risk_level === 'high') {
            return response()->json([
                'error' => 'Cannot connect two high-risk users directly.'
            ], 403);
        }

        // Create the message
        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ]);
    }

    /**
     * Get messages for the authenticated user.
     */
    public function inbox()
    {
        $userId = auth()->id();

        $messages = Message::where('receiver_id', $userId)
                           ->orWhere('sender_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->get();

        return response()->json($messages);
    }
    
    /**
     * Show conversation with a specific user.
     */
    public function conversation(User $user)
    {
        $currentUser = auth()->user();
        
        // Check if the other user has blocked the current user
        if (BlockedUser::where('user_id', $user->id)
                       ->where('blocked_user_id', $currentUser->id)
                       ->exists()) {
            return redirect()->route('dashboard')
                ->with('error', 'You cannot message this user.');
        }
        
        // Get all messages between these two users
        $messages = Message::where(function($query) use ($currentUser, $user) {
            $query->where('sender_id', $currentUser->id)
                  ->where('receiver_id', $user->id);
        })->orWhere(function($query) use ($currentUser, $user) {
            $query->where('sender_id', $user->id)
                  ->where('receiver_id', $currentUser->id);
        })->orderBy('created_at', 'asc')->get();
        
        // Mark messages from the other user as read (if you have a read_at column)
        // Message::where('sender_id', $user->id)
        //        ->where('receiver_id', $currentUser->id)
        //        ->whereNull('read_at')
        //        ->update(['read_at' => now()]);
        
        return view('messages.conversation', compact('user', 'messages'));
    }
    
    /**
     * Send a message to a specific user.
     */
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:1000',
        ]);

        $senderId = auth()->id();
        $receiverId = $request->receiver_id;
        $content = $request->input('content');

        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);

        // Check if receiver has blocked sender
        if (BlockedUser::where('user_id', $receiverId)
                       ->where('blocked_user_id', $senderId)
                       ->exists()) {
            return back()->with('error', 'Cannot send message to this user.');
        }

        // Prevent two high-risk users from messaging each other (if risk_level exists)
        // if ($sender->risk_level === 'high' && $receiver->risk_level === 'high') {
        //     return back()->with('error', 'Cannot connect two high-risk users directly.');
        // }

        // Create the message
        Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
        ]);

        return back()->with('success', 'Message sent successfully!');
    }
}

