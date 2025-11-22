@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">Edit Group</h1>
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif
        <form method="POST" action="{{ route('groups.update', $group->id) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $group->name) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required>
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Description</label>
                <textarea name="description" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required>{{ old('description', $group->description) }}</textarea>
            </div>
            <div class="flex justify-end gap-4">
                <a href="{{ route('groups.show', $group->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold">Cancel</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold shadow">Update Group</button>
            </div>
        </form>
    </div>
</div>
@endsection