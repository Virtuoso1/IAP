@extends('layouts.app')

@section('content')
<div class="flex min-h-screen bg-gray-100">

    <!-- Sidebar -->
    <div class="w-72 bg-gradient-to-b from-blue-700 to-blue-500 text-white shadow-xl flex flex-col justify-between py-8 px-6">
        <div>
            <div class="flex items-center gap-3 mb-10">
                <img src="/images/person.jpg" alt="Logo" class="h-10 w-10 rounded-full object-cover border-2 border-blue-300 shadow" />
                <span class="text-2xl font-bold tracking-wide">SafeSpace</span>
            </div>
            <nav class="space-y-2">
                <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="{{ route('matches.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    <span class="font-medium">Matches</span>
                </a>
                <a href="{{ route('matches.helpers') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span class="font-medium">Available Helpers</span>
                </a>
                <a href="{{ route('groups.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-blue-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    <span class="font-medium">Groups</span>
                </a>
                <a href="{{ route('groups.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    <span class="font-medium">Create Group</span>
                </a>
            </nav>
            <div class="mt-10 pt-8 border-t border-blue-400">
                <p class="font-semibold text-white">{{ auth()->user()->username }}</p>
                <p class="text-sm text-blue-200 mt-1">{{ ucfirst(auth()->user()->role) }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-lg font-semibold justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
            </button>
        </form>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <div class="max-w-5xl mx-auto">

            <!-- Welcome Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-3xl font-bold text-blue-600">Welcome, {{ auth()->user()->username }}!</h1>
                <p class="text-gray-700 mt-1">Manage your groups and matches below.</p>
            </div>

            <!-- Your Groups -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">Your Groups</h2>
                @if(auth()->user()->groups->count() > 0)
                    <div class="grid gap-4">
                        @foreach(auth()->user()->groups as $group)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">{{ $group->name }}</h3>
                                    <p class="text-gray-500 text-sm mt-1">{{ $group->members()->count() }} members</p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('groups.show', $group->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm transition">View</a>
                                    <a href="{{ route('groups.edit', $group->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded text-sm transition">Edit</a>
                                    <form action="{{ route('groups.destroy', $group->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm transition">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">You haven't joined any groups yet.</p>
                @endif
            </div>

            <!-- Groups You Can Join -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-green-600 mb-4">Groups You Can Join</h2>
                @php
                    $availableGroups = \App\Models\Group::whereDoesntHave('members', function($q) { 
                        $q->where('user_id', auth()->id()); 
                    })->get();
                @endphp
                @if($availableGroups->count() > 0)
                    <div class="grid gap-4">
                        @foreach($availableGroups as $group)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">{{ $group->name }}</h3>
                                    <p class="text-gray-500 text-sm mt-1">{{ $group->members()->count() }} members</p>
                                </div>
                                <form method="POST" action="{{ route('groups.users.store', $group->id) }}">
                                    @csrf
                                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm transition">Join</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">No groups available to join.</p>
                @endif
            </div>

            <!-- Your Matches -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-purple-600 mb-4">Your Matches</h2>
                @php
                    $matches = auth()->user()->allMatches()->with(['seeker', 'helper'])->latest()->take(3)->get();
                @endphp
                @if($matches->count() > 0)
                    <div class="grid gap-4">
                        @foreach($matches as $match)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition flex justify-between items-center">
                                <div>
                                    <p class="font-semibold text-gray-800">
                                        @if($match->seeker_id === auth()->id())
                                            You ↔ {{ $match->helper->username }}
                                        @else
                                            {{ $match->seeker->username }} ↔ You
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500">{{ $match->created_at->format('M j, Y g:i A') }}</p>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        @if($match->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($match->status === 'active') bg-green-100 text-green-800
                                        @elseif($match->status === 'completed') bg-blue-100 text-blue-800
                                        @elseif($match->status === 'cancelled') bg-red-100 text-red-800
                                        @endif">
                                        {{ ucfirst($match->status) }}
                                    </span>
                                </div>
                                <a href="{{ route('matches.show', $match->id) }}" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded text-sm transition">View</a>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 text-center">
                        <a href="{{ route('matches.index') }}" class="text-purple-600 hover:text-purple-800 font-semibold transition">View All Matches</a>
                    </div>
                @else
                    <p class="text-center text-gray-500 py-8">You don't have any matches yet.</p>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection