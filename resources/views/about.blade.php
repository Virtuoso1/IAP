@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-10 px-4">
    <h2 class="text-4xl font-bold mb-8 text-center text-gray-800">About SafeSpace</h2>

    <!-- Mission Section -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-2xl font-semibold mb-3 text-blue-700">Our Mission</h3>
        <p class="text-gray-700 leading-relaxed">
            SafeSpace is dedicated to creating safe, confidential, and supportive spaces for student mental health. 
            We connect students seeking guidance with empathetic peers who provide understanding and encouragement.
        </p>
    </div>

    <!-- Values Section -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-2xl font-semibold mb-3 text-purple-700">Our Values</h3>
        <ul class="list-disc list-inside text-gray-700 space-y-2">
            <li><strong>Empathy:</strong> We prioritize understanding and caring for each student’s needs.</li>
            <li><strong>Privacy:</strong> Confidentiality and anonymity are central to everything we do.</li>
            <li><strong>Support:</strong> We foster a community where students help each other grow emotionally and mentally.</li>
            <li><strong>Accessibility:</strong> Everyone should have easy access to mental health support.</li>
        </ul>
    </div>

    <!-- Community Section -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-2xl font-semibold mb-3 text-green-700">Join Our Community</h3>
        <p class="text-gray-700 leading-relaxed mb-4">
            By joining SafeSpace, you become part of a compassionate, supportive, and resilient student network. 
            Share your challenges, seek advice, and build meaningful connections in a non-judgmental environment.
        </p>
        <a href="/dashboard" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded transition duration-300">
            Visit Dashboard
        </a>
    </div>

    <!-- Visual Accent -->
    <div class="text-center mt-10">
        <img src="https://cdn-icons-png.flaticon.com/512/3177/3177440.png" alt="SafeSpace Icon" class="mx-auto w-32 h-32 opacity-70">
        <p class="text-gray-500 mt-4">SafeSpace – A place where empathy meets action</p>
    </div>
</div>
@endsection
