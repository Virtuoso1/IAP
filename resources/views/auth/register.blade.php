@extends('layouts.auth')

@section('title', 'Sign Up')

@section('heading', 'Join SafeSpace')

@section('subheading', 'Create your account and connect with peer support')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                Email Address
            </label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                value="{{ old('email') }}"
                required 
                autofocus
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('email') border-red-500 @enderror"
                placeholder="your.email@student.edu"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Username -->
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                Username <span class="text-gray-500 text-xs">(This will be your anonymous display name)</span>
            </label>
            <input 
                type="text" 
                id="username" 
                name="username" 
                value="{{ old('username') }}"
                required 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('username') border-red-500 @enderror"
                placeholder="Choose a username"
            >
            @error('username')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Password
            </label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('password') border-red-500 @enderror"
                placeholder="Create a strong password"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                Confirm Password
            </label>
            <input 
                type="password" 
                id="password_confirmation" 
                name="password_confirmation" 
                required 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="Re-enter your password"
            >
        </div>

        <!-- Role Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
                I want to: (You can change this at any time)
            </label>
            <div class="space-y-3">
                <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition @error('role') border-red-500 @enderror">
                    <input 
                        type="radio" 
                        name="role" 
                        value="seeker" 
                        {{ old('role') == 'seeker' ? 'checked' : '' }}
                        class="mt-1 text-blue-600 focus:ring-blue-500"
                    >
                    <div class="ml-3">
                        <span class="block font-medium text-gray-900">Seek Support</span>
                        <span class="block text-sm text-gray-600">Connect with peer supporters when I need help</span>
                    </div>
                </label>

                <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                    <input 
                        type="radio" 
                        name="role" 
                        value="helper" 
                        {{ old('role') == 'helper' ? 'checked' : '' }}
                        class="mt-1 text-blue-600 focus:ring-blue-500"
                    >
                    <div class="ml-3">
                        <span class="block font-medium text-gray-900">Provide Support</span>
                        <span class="block text-sm text-gray-600">Help others by offering peer support</span>
                    </div>
                </label>

                <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition">
                    <input 
                        type="radio" 
                        name="role" 
                        value="hybrid" 
                        {{ old('role') == 'hybrid' ? 'checked' : '' }}
                        checked
                        class="mt-1 text-blue-600 focus:ring-blue-500"
                    >
                    <div class="ml-3">
                        <span class="block font-medium text-gray-900">Both</span>
                        <span class="block text-sm text-gray-600">I want to both seek and provide support</span>
                    </div>
                </label>
            </div>
            @error('role')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Terms and Conditions -->
        <div class="flex items-start">
            <input 
                type="checkbox" 
                id="terms" 
                name="terms" 
                required
                class="mt-1 text-blue-600 focus:ring-blue-500 rounded"
            >
            <label for="terms" class="ml-2 text-sm text-gray-600">
                I agree to the <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Terms of Service</a> and 
                <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Privacy Policy</a>
            </label>
        </div>

        <!-- Submit Button -->
        <button 
            type="submit" 
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg"
        >
            Create Account
        </button>
    </form>
@endsection

@section('footer')
    <p class="text-center text-gray-600">
        Already have an account? 
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-semibold">Sign in</a>
    </p>
@endsection

@section('additional_info')
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
        <p class="text-blue-800 text-xs">
            <strong>Note:</strong> Your email is private and used only for account security. 
            Your username will be visible to others for anonymity.
        </p>
    </div>
@endsection