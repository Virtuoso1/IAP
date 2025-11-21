@extends('layouts.app')
@section('title', '403 Forbidden')
@section('content')
<div class="max-w-xl mx-auto mt-20 text-center">
    <div class="bg-white shadow rounded-lg p-8">
        <h1 class="text-4xl font-bold text-red-600 mb-4">403 Forbidden</h1>
        <p class="text-lg text-gray-700 mb-6">You can only edit your own messages.</p>
        <a href="{{ url()->previous() }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Go Back</a>
    </div>
</div>
@endsection
