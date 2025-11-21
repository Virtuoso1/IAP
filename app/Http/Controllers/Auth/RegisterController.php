<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|min:3|max:20|unique:users,username|alpha_dash',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:seeker,helper,hybrid',
            'terms' => 'accepted',
        ], [
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
            'username.alpha_dash' => 'Username can only contain letters, numbers, dashes and underscores.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'terms.accepted' => 'You must accept the terms and conditions.',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => 'active',
            'is_available' => $validated['role'] !== 'seeker', // Helpers start as available
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to SafeSpace! Your account has been created.');
    }
}