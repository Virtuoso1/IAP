@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-10">
    <h2 class="text-3xl font-bold mb-6 text-gray-800">Dashboard</h2>

    <p class="text-gray-700 mb-4">
        Welcome to <span class="font-semibold text-blue-600">SafeSpace</span>, a safe, anonymous space for student mental health support.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="p-4 bg-green-100 rounded shadow">
            <h3 class="font-semibold text-lg text-green-800">Student Helper</h3>
            <p class="text-gray-700">These students are here to offer support and guidance.</p>
        </div>
        <div class="p-4 bg-red-100 rounded shadow">
            <h3 class="font-semibold text-lg text-red-800">Overwhelmed Student</h3>
            <p class="text-gray-700">These students may need support and guidance.</p>
        </div>
    </div>
</div>
@endsection
