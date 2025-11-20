@extends('layouts.auth')

@section('title', 'Login')

@section('heading', 'Welcome Back')

@section('subheading', 'Sign in to continue your journey')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
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
                placeholder="Enter your password"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    name="remember" 
                    class="text-blue-600 focus:ring-blue-500 rounded"
                >
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>

            <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                Forgot password?
            </a>
        </div>

        <!-- Submit Button -->
        <button 
            type="submit" 
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg"
        >
            Sign In
        </button>
    </form>
@endsection

@section('footer')
    <p class="text-center text-gray-600">
        Don't have an account? 
        <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-semibold">Sign up</a>
    </p>
@endsection

@section('additional_info')
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
        <p class="text-blue-800 text-xs">
            <strong>Need immediate help?</strong> Call the crisis hotline at 
            <a href="tel:1-800-273-8255" class="font-semibold underline">1-800-273-8255</a>
        </p>
    </div>
@endsection