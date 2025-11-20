@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen flex flex-col justify-start items-center px-4 pt-12">
    <!-- Logo / Illustration at Top -->
    <div class="mb-8">
        <img src="{{ asset('images/mental health.jpg') }}" alt="SafeSpace Logo" class="w-48 h-48 mx-auto opacity-90">
    </div>

    <!-- Hero Section -->
    <div class="text-center max-w-2xl">
        <h1 class="text-5xl font-extrabold mb-6 text-gray-800">
            Welcome to SafeSpace
        </h1>
        <p class="text-xl text-gray-600 mb-8 leading-relaxed">
            Creating safe, anonymous spaces for student mental health support through empathy-driven design. 
            Connect, share, and support each other in a compassionate environment.
        </p>
        
        <a href="/dashboard" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded shadow transition duration-300">
            Visit Dashboard
        </a>
    </div>

    <!-- Footer Message -->
    <p class="text-gray-500 mt-12 text-center">
        SafeSpace – where empathy meets action
    </p>
</div>
@endsection
