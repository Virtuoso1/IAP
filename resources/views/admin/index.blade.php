@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-10">
    <h2 class="text-3xl font-bold mb-6 text-gray-800">Admin Panel</h2>

    <p class="text-gray-700 mb-4">
        This is the admin area. Here you can manage students, helpers, and other SafeSpace settings.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <div class="p-4 bg-blue-100 rounded shadow">
            <h3 class="font-semibold text-lg text-blue-800">Manage Students</h3>
            <p class="text-gray-700">Add, edit or remove student entries.</p>
        </div>
        <div class="p-4 bg-yellow-100 rounded shadow">
            <h3 class="font-semibold text-lg text-yellow-800">Manage Helpers</h3>
            <p class="text-gray-700">Add, edit or remove helper entries.</p>
        </div>
        <div class="p-4 bg-purple-100 rounded shadow">
            <h3 class="font-semibold text-lg text-purple-800">Settings</h3>
            <p class="text-gray-700">Configure SafeSpace features and preferences.</p>
        </div>
    </div>
</div>
@endsection
