<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeSpace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('build/assets/app-BMEMqQ4x.css') }}">
</head>
<body class="bg-gray-50 font-sans">

    <!-- Navbar -->
    <nav class="bg-white shadow">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="{{ url('/') }}" class="text-xl font-bold text-gray-800">SafeSpace</a>
            <div class="space-x-4 flex items-center">
                <a href="{{ url('/') }}" class="text-gray-700 hover:text-gray-900">Home</a>
                <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                <a href="{{ url('/about') }}" class="text-gray-700 hover:text-gray-900">About Us</a>
                <a href="{{ url('/login') }}" class="text-gray-700 hover:text-gray-900">Login</a>

            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="py-6">
        @yield('content')
    </main>
    
</body>
</html>
