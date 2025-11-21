<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SafeSpace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Left Sidebar - CRUD Operations -->
        <div class="w-72 bg-gradient-to-b from-blue-700 to-blue-500 text-white shadow-xl flex flex-col justify-between py-8 px-6">
            <div>
                <div class="flex items-center gap-3 mb-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-2.21 1.79-4 4-4s4 1.79 4 4-1.79 4-4 4-4-1.79-4-4zm-6 8v-2a4 4 0 014-4h4a4 4 0 014 4v2" /></svg>
                    <span class="text-2xl font-bold tracking-wide">SafeSpace</span>
                </div>
                <nav class="space-y-2">
                    <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" /></svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a href="{{ route('groups.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20h6M3 20h5v-2a4 4 0 013-3.87M16 3.13a4 4 0 00-8 0M12 7v4m0 0v4m0-4h4m-4 0H8" /></svg>
                        <span class="font-medium">Groups</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.657 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span class="font-medium">Profile</span>
                    </a>
                    <a href="{{ route('groups.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span class="font-medium">Create Group</span>
                    </a>
                </nav>
                <div class="mt-10 pt-8 border-t border-blue-400">
                    <div class="flex items-center gap-2 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.657 6.879 1.804" /></svg>
                        <span class="text-sm">Logged in as:</span>
                    </div>
                    <p class="font-semibold text-white">{{ auth()->user()->username }}</p>
                    <p class="text-sm text-blue-200 mt-1">{{ ucfirst(auth()->user()->role) }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-8">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-lg font-semibold justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                    Logout
                </button>
            </form>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 p-8">
            <div class="max-w-5xl mx-auto">
                <!-- Welcome Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h1 class="text-3xl font-bold text-blue-600 mb-2">Welcome, {{ auth()->user()->username }}!</h1>
                    <p class="text-gray-700">Manage your groups and messages below.</p>
                </div>

                <!-- Your Groups Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-blue-600 mb-4">Your Groups</h2>
                    @if(auth()->user()->groups->count() > 0)
                        <div class="grid gap-4">
                            @foreach(auth()->user()->groups as $group)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-bold text-gray-800">{{ $group->name }}</h3>
                                            <p class="text-gray-500 text-sm mt-1">{{ $group->members()->count() }} members</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="{{ route('groups.show', $group->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                                                View
                                            </a>
                                            <a href="{{ route('groups.edit', $group->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-sm">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('groups.destroy', $group->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this group?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">You haven't joined any groups yet. Join a group below or create your own!</p>
                    @endif
                </div>

                <!-- Groups You Can Join Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-green-600 mb-4">Groups You Can Join</h2>
                    @php
                        $availableGroups = \App\Models\Group::whereDoesntHave('members', function($q) { 
                            $q->where('user_id', auth()->id()); 
                        })->get();
                    @endphp
                    
                    @if($availableGroups->count() > 0)
                        <div class="grid gap-4">
                            @foreach($availableGroups as $group)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-700">{{ $group->name }}</h3>
                                            <p class="text-gray-500 text-sm mt-1">{{ $group->members()->count() }} members</p>
                                        </div>
                                        <form method="POST" action="{{ route('groups.users.store', $group->id) }}">
                                            @csrf
                                            <input type="hidden" name="username" value="{{ auth()->user()->username }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                                Join Group
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No groups available to join at the moment.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>