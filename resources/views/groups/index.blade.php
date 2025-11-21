@extends('layouts.app')
@section('content')
<div class="flex justify-center items-center min-h-screen">
<div class="max-w-3xl w-full bg-white rounded-lg shadow-md p-8">
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-6">Groups</h1>
        <a href="{{ route('groups.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mb-6 inline-block">Create New Group</a>
        <h2 class="text-xl font-semibold text-blue-500 mb-2">Groups You Belong To</h2>
        <ul class="divide-y divide-gray-200 mb-6">
            @foreach($groups as $group)
                @if($group->members->contains(auth()->id()))
                <li class="py-4 flex items-center justify-between">
                    <div>
                        <a href="{{ route('groups.show', $group->id) }}" class="text-lg font-semibold text-blue-700 hover:underline">{{ $group->name }}</a>
                        <span class="ml-2 text-gray-500">({{ $group->members()->count() }} members)</span>
                    </div>
                </li>
                @endif
            @endforeach
        </ul>
        <h2 class="text-xl font-semibold text-green-500 mb-2">Groups You Can Join</h2>
        <ul class="divide-y divide-gray-200">
            @foreach($joinableGroups as $group)
                <li class="py-4 flex items-center justify-between">
                    <div>
                        <span class="text-lg font-semibold text-gray-700">{{ $group->name }}</span>
                        <span class="ml-2 text-gray-500">({{ $group->members()->count() }} members)</span>
                    </div>
                    <form method="POST" action="{{ route('groups.users.store', $group->id) }}">
                        @csrf
                        <input type="hidden" name="username" value="{{ auth()->user()->username }}">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Join</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection