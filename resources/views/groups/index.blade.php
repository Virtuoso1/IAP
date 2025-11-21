@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">Groups</h1>
        <a href="{{ route('groups.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mb-6 inline-block">Create New Group</a>
        <ul class="divide-y divide-gray-200">
            @foreach($groups as $group)
                <li class="py-4 flex items-center justify-between">
                    <div>
                        <a href="{{ route('groups.show', $group->id) }}" class="text-lg font-semibold text-blue-700 hover:underline">{{ $group->name }}</a>
                        <span class="ml-2 text-gray-500">({{ $group->members()->count() }} members)</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection