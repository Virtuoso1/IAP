<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Match - SafeSpace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            <div class="max-w-3xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h1 class="text-3xl font-bold text-blue-600 mb-2">Create New Match</h1>
                    <p class="text-gray-700">Connect with a helper who can support you.</p>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Create Match Form -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <form method="POST" action="{{ route('matches.store') }}">
                        @csrf
                        
                        <!-- Helper Selection -->
                        <div class="mb-6">
                            <label for="helper_id" class="block text-gray-700 font-semibold mb-2">
                                Select a Helper (Optional)
                            </label>
                            <p class="text-sm text-gray-600 mb-3">
                                You can choose a specific helper or leave this blank to create an open request that any available helper can accept.
                            </p>
                            @if($availableHelpers->count() > 0)
                                <select name="helper_id" id="helper_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Leave open for any helper...</option>
                                    @foreach($availableHelpers as $helper)
                                        <option value="{{ $helper->id }}" {{ request('helper_id') == $helper->id ? 'selected' : '' }}>
                                            {{ $helper->username }} ({{ ucfirst($helper->role) }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                                    <p class="font-semibold">No helpers currently available</p>
                                    <p class="text-sm mt-1">Don't worry! You can still create a match request, and a helper will be able to accept it when they become available.</p>
                                </div>
                                <input type="hidden" name="helper_id" value="">
                            @endif
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <label for="notes" class="block text-gray-700 font-semibold mb-2">Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add any notes or specific needs you'd like the helper to know...">{{ old('notes') }}</textarea>
                            <p class="text-sm text-gray-500 mt-1">Maximum 500 characters</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-4">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Create Match Request
                            </button>
                            <a href="{{ route('matches.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Available Helpers Preview -->
                @if($availableHelpers->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Available Helpers</h2>
                        <div class="grid gap-4">
                            @foreach($availableHelpers as $helper)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="font-semibold text-gray-800">{{ $helper->username }}</h3>
                                            <p class="text-sm text-gray-600">{{ ucfirst($helper->role) }} • Available</p>
                                        </div>
                                        <button onclick="document.getElementById('helper_id').value = '{{ $helper->id }}'" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                                            Select
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>