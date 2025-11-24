@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto mt-10 p-4">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-sm">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-sm">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-lg p-8 space-y-6">

        {{-- Group Header --}}
        <div class="flex flex-col md:flex-row md:justify-between md:items-center">
            <div>
                <h1 class="text-4xl font-bold text-blue-600 mb-1">{{ $group->name }}</h1>
                <p class="text-gray-600 text-lg">{{ $group->description }}</p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-3 mt-4 md:mt-0">
                <a href="{{ route('groups.edit', $group->id) }}" class="flex items-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-500 text-blue-700 hover:text-white rounded-full font-semibold shadow transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('groups.destroy', $group->id) }}" onsubmit="return confirm('Are you sure you want to delete this group?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-500 text-red-600 hover:text-white rounded-full font-semibold shadow transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16" /></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>

        {{-- Edit Form removed as requested --}}

        {{-- Members --}}
        <div class="mt-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Members ({{ $group->members->count() }})</h2>
            <ul class="divide-y divide-gray-200">
                @foreach($group->members as $user)
                <li class="py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <div>
                            <p class="font-medium">{{ $user->username }}</p>
                            <p class="text-sm text-gray-500 capitalize">{{ $user->pivot->role }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('groups.users.destroy', [$group->id, $user->id]) }}" onsubmit="return confirm('Remove this member?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1 bg-red-100 hover:bg-red-500 text-red-600 hover:text-white rounded-full flex items-center gap-1 transition text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Remove
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Group Messages --}}
        <div class="mt-8 text-center">
            <a href="{{ route('groups.messages.index', $group->id) }}" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white font-semibold rounded-full shadow hover:bg-blue-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                View Messages
            </a>
        </div>

        {{-- Join Group Button --}}
        @if(!$group->members->contains(auth()->id()))
        <div class="mt-6 text-center">
            <form method="POST" action="{{ route('groups.users.store', $group->id) }}">
                @csrf
                <input type="hidden" name="username" value="{{ auth()->user()->username }}">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-green-500 text-white rounded-full font-semibold shadow hover:bg-green-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    Join Group
                </button>
            </form>
        </div>
        @endif

    </div>
</div>
@endsection
