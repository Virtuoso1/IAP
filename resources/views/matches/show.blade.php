<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Details - SafeSpace</title>
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
                    <a href="{{ route('matches.helpers') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-900 transition">
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
            <div class="max-w-4xl mx-auto">
                <!-- Success/Error Messages -->
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Match Details -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-blue-600 mb-2">Match Details</h1>
                            <div class="flex items-center gap-3">
                                <span class="px-4 py-2 rounded-full text-sm font-semibold
                                    @if($match->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($match->status === 'active') bg-green-100 text-green-800
                                    @elseif($match->status === 'completed') bg-blue-100 text-blue-800
                                    @elseif($match->status === 'cancelled') bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($match->status) }}
                                </span>
                                <span class="text-gray-500 text-sm">
                                    Match ID: #{{ $match->id }}
                                </span>
                            </div>
                        </div>
                        <a href="{{ route('matches.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                            Back to Matches
                        </a>
                    </div>

                    <!-- Participants -->
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($match->seeker->username, 0, 1)->upper() }}
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">{{ $match->seeker->username }}</h3>
                                    <p class="text-sm text-gray-600">Seeker</p>
                                    @if($match->seeker_id === auth()->id())
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">You</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($match->helper->username, 0, 1)->upper() }}
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">{{ $match->helper->username }}</h3>
                                    <p class="text-sm text-gray-600">Helper</p>
                                    @if($match->helper_id === auth()->id())
                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">You</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Match Information -->
                    <div class="space-y-4">
                        @if($match->notes)
                            <div>
                                <h3 class="font-semibold text-gray-700 mb-2">Notes</h3>
                                <p class="text-gray-600 bg-gray-50 p-3 rounded">{{ $match->notes }}</p>
                            </div>
                        @endif

                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Timeline</h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm text-gray-600">
                                        Created: {{ $match->created_at->format('M j, Y g:i A') }}
                                    </span>
                                </div>
                                
                                @if($match->started_at)
                                    <div class="flex items-center gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm text-gray-600">
                                            Started: {{ $match->started_at->format('M j, Y g:i A') }}
                                        </span>
                                    </div>
                                @endif
                                
                                @if($match->ended_at)
                                    <div class="flex items-center gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="text-sm text-gray-600">
                                            Ended: {{ $match->ended_at->format('M j, Y g:i A') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
                    
                    @if($match->status === 'pending')
                        @if($match->helper_id === auth()->id())
                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-4">
                                <p class="font-semibold">Waiting for your response</p>
                                <p class="text-sm mt-1">You can activate this match to begin helping.</p>
                            </div>
                            
                            <form method="POST" action="{{ route('matches.activate', $match->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold">
                                    Activate Match
                                </button>
                            </form>
                        @else
                            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg mb-4">
                                <p class="font-semibold">Waiting for helper response</p>
                                <p class="text-sm mt-1">The helper will review your request and activate the match when ready.</p>
                            </div>
                        @endif
                    @endif

                    @if($match->status === 'active')
                        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4">
                            <p class="font-semibold">Match is active</p>
                            <p class="text-sm mt-1">You can complete this match when the support session is finished.</p>
                        </div>
                        
                        <form method="POST" action="{{ route('matches.complete', $match->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                                Complete Match
                            </button>
                        </form>
                    @endif

                    @if($match->status === 'completed')
                        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
                            <p class="font-semibold">Match completed</p>
                            <p class="text-sm mt-1">This match has been successfully completed.</p>
                        </div>
                    @endif

                    @if($match->status === 'cancelled')
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            <p class="font-semibold">Match cancelled</p>
                            <p class="text-sm mt-1">This match was cancelled.</p>
                        </div>
                    @endif

                    @if(in_array($match->status, ['pending', 'active']))
                        <div class="mt-4">
                            <form method="POST" action="{{ route('matches.cancel', $match->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to cancel this match?');">
                                @csrf
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold">
                                    Cancel Match
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>