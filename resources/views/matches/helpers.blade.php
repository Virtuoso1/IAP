<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Helpers - SafeSpace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Left Sidebar - Navigation -->
        <div class="w-72 bg-gradient-to-b from-blue-700 to-blue-500 text-white shadow-xl flex flex-col justify-between py-8 px-6">
            <div>
                <div class="flex items-center gap-3 mb-10">
                    <img src="/images/person.jpg" alt="Logo" class="h-10 w-10 rounded-full object-cover border-2 border-blue-300 shadow" />
                    <span class="text-2xl font-bold tracking-wide">SafeSpace</span>
                </div>
                <nav class="space-y-2">
                    <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" /></svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a href="{{ route('matches.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20h6M3 20h5v-2a4 4 0 013-3.87M16 3.13a4 4 0 00-8 0M12 7v4m0 0v4m0-4h4m-4 0H8" /></svg>
                        <span class="font-medium">Matches</span>
                    </a>
                    <a href="{{ route('matches.helpers') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <span class="font-medium">Available Helpers</span>
                    </a>
                    <a href="{{ route('groups.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20h6M3 20h5v-2a4 4 0 013-3.87M16 3.13a4 4 0 00-8 0M12 7v4m0 0v4m0-4h4m-4 0H8" /></svg>
                        <span class="font-medium">Groups</span>
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
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-blue-600 mb-2">Available Helpers</h1>
                            <p class="text-gray-700">Find helpers who are currently available to provide support.</p>
                        </div>
                        @if(auth()->user()->isSeeker())
                            <a href="{{ route('matches.create') }}" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Create Match
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Available Helpers List -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    @if($availableHelpers->count() > 0)
                        <div class="mb-4">
                            <p class="text-gray-600">{{ $availableHelpers->count() }} helper(s) available</p>
                        </div>
                        
                        <div class="grid gap-4">
                            @foreach($availableHelpers as $helper)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
                                                {{ substr($helper->username, 0, 1)->upper() }}
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-800">{{ $helper->username }}</h3>
                                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">
                                                        {{ ucfirst($helper->role) }}
                                                    </span>
                                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                                        Available
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex gap-2">
                                            @if(auth()->user()->isSeeker())
                                                <a href="{{ route('matches.create') }}?helper_id={{ $helper->id }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm">
                                                    Request Match
                                                </a>
                                            @endif
                                            
                                            <a href="/messages/{{ $helper->id }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                                                Message
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No helpers available</h3>
                            <p class="text-gray-500 mb-6">
                                There are currently no helpers available. Please check back later.
                            </p>
                            <div class="flex justify-center gap-4">
                                <a href="{{ route('matches.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold">
                                    Back to Matches
                                </a>
                                @if(auth()->user()->isSeeker())
                                    <a href="{{ route('matches.create') }}" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold">
                                        Create Match Anyway
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Helper Statistics -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Helper Statistics</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">{{ $availableHelpers->count() }}</div>
                            <div class="text-gray-600 text-sm mt-1">Available Now</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600">
                                {{ \App\Models\User::where(function($query) {
                                    $query->where('role', 'helper')
                                          ->orWhere('role', 'hybrid');
                                })->where('is_available', false)->count() }}
                            </div>
                            <div class="text-gray-600 text-sm mt-1">Currently Busy</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-purple-600">
                                {{ \App\Models\User::where(function($query) {
                                    $query->where('role', 'helper')
                                          ->orWhere('role', 'hybrid');
                                })->count() }}
                            </div>
                            <div class="text-gray-600 text-sm mt-1">Total Helpers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>