<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SafeSpace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-8">
                <h1 class="text-3xl font-bold text-blue-600 mb-4">Welcome, {{ auth()->user()->username }}!</h1>
                <p class="text-gray-600 mb-4">Your role: <span class="font-semibold">{{ ucfirst(auth()->user()->role) }}</span></p>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto mt-8">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-blue-600 mb-4">Your Groups</h2>
            <ul>
                @foreach(auth()->user()->groups as $group)
                    <li class="mb-2">
                        <span class="font-semibold">{{ $group->name }}</span>
                        <a href="{{ route('groups.messages.index', ['group' => $group->id]) }}" class="ml-4 text-blue-500 hover:underline">View Messages</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</body>
</html>