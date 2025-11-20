@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-16 text-center">
    <h1 class="text-4xl font-bold mb-8">Welcome to Safespace</h1>
    <p class="text-lg mb-12">Connect with other students and support each other.</p>

    <div class="flex justify-center gap-8">
        <a href="{{ route('register') }}?type=overwhelmed"
           class="bg-red-500 hover:bg-red-600 text-white px-6 py-4 rounded-lg text-xl">
           I feel overwhelmed
        </a>

        <a href="{{ route('register') }}?type=helper"
           class="bg-green-500 hover:bg-green-600 text-white px-6 py-4 rounded-lg text-xl">
           I want to help others
        </a>
    </div>
</div>
@endsection
