<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SafeSpace') - SafeSpace</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-blue-100">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-blue-600">SafeSpace</span>
                </a>
                
                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="#" class="text-gray-600 hover:text-blue-600 transition">About</a>
                    <a href="#" class="text-gray-600 hover:text-blue-600 transition">Resources</a>
                    <a href="#" class="text-gray-600 hover:text-blue-600 transition">Contact</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12 flex items-center justify-center min-h-[calc(100vh-180px)]">
        <div class="w-full max-w-md">
            
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-blue-100 overflow-hidden">
                
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-8 py-6 text-center">
                    <h1 class="text-2xl font-bold text-white">@yield('heading')</h1>
                    <p class="text-blue-100 mt-2">@yield('subheading')</p>
                </div>

                <!-- Card Body -->
                <div class="px-8 py-8">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif

                    @yield('content')
                </div>

                <!-- Card Footer -->
                <div class="px-8 py-4 bg-gray-50 border-t border-gray-100">
                    @yield('footer')
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-6 text-center text-sm text-gray-600">
                @yield('additional_info')
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-blue-100 py-6">
        <div class="container mx-auto px-4 text-center text-gray-600 text-sm">
            <p>&copy; {{ date('Y') }} SafeSpace. A safe space for student mental health support.</p>
            <p class="mt-2">
                <span class="text-blue-600 font-semibold">Crisis Hotline:</span> 
                <a href="tel:1-800-273-8255" class="hover:text-blue-600">1-800-273-8255</a>
            </p>
        </div>
    </footer>

</body>
</html>