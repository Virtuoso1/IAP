<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <nav class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between">
            <a href="{{ route('dashboard') }}" class="font-bold text-lg">SafeSpace</a>
            <div class="space-x-4">
                <a href="{{ route('dashboard') }}" class="text-blue-600">Dashboard</a>
                <a href="{{ route('admin.index') }}" class="text-blue-600">Admin</a>
            </div>
        </div>
    </nav>

    <main class="py-6">
        @yield('content')
    </main>

</body>
</html>
