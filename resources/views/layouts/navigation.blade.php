<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeSpace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <!-- Logo / Home link -->
            <a href="{{ url('/') }}" class="text-xl font-bold text-gray-800">SafeSpace</a>

            <!-- Navigation Links -->
            <div class="space-x-4 flex items-center">
                <a href="{{ url('/') }}" class="text-gray-700 hover:text-gray-900">Home</a>
                <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                <a href="{{ route('about') }}" class="text-gray-700 hover:text-gray-900">About Us</a>
                <a href="{{ url('/login') }}" class="text-gray-700 hover:text-gray-900">Login</a>

            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-6">
        @yield('content')
    </main>
</body>
</html>
