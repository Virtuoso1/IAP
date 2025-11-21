@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto mt-8">
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">Create New Group</h1>
        <form method="POST" action="{{ route('groups.store') }}">
            @csrf
            <label class="block mb-1">Group Name</label>
            <input type="text" name="name" id="name" class="form-control mb-4 border rounded px-3 py-2" required>
            <label class="block mb-1">Description</label>
            <textarea name="description" id="description" class="form-control mb-4 border rounded px-3 py-2"></textarea>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Create Group</button>
        </form>
    </div>
</div>
@endsection