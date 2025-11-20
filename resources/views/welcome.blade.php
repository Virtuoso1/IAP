<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeSpace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">

    <nav class="bg-white shadow">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-gray-800">SafeSpace</a>
            <div class="space-x-4 flex items-center">
                <a href="/dashboard" class="text-gray-700 hover:text-gray-900 font-medium">Dashboard</a>
                <a href="/admin" class="text-gray-700 hover:text-gray-900 font-medium">Admin</a>
            </div>
        </div>
    </nav>

    <header class="bg-blue-50 py-20">
        <div class="container mx-auto text-center">
            <h1 class="text-5xl font-bold text-blue-700 mb-6">Safe, Anonymous Support for Students</h1>
            <p class="text-xl text-gray-700 mb-8">
                Creating safe, anonymous spaces for student mental health support through empathy-driven design.
            </p>
            <a href="/dashboard" class="px-8 py-4 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition">Go to Dashboard</a>
        </div>
    </header>

    <section class="container mx-auto py-16 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="p-6 bg-green-100 rounded shadow text-center">
            <h3 class="font-semibold text-xl text-green-800 mb-2">Get Help</h3>
            <p class="text-gray-700">Connect with helpers who are ready to support you.</p>
        </div>
        <div class="p-6 bg-red-100 rounded shadow text-center">
            <h3 class="font-semibold text-xl text-red-800 mb-2">Offer Support</h3>
            <p class="text-gray-700">Sign up as a helper and guide students in need.</p>
        </div>
        <div class="p-6 bg-purple-100 rounded shadow text-center">
            <h3 class="font-semibold text-xl text-purple-800 mb-2">Safe & Anonymous</h3>
            <p class="text-gray-700">All interactions are private and secure for mental well-being.</p>
        </div>
    </section>

    <footer class="bg-gray-200 py-6 mt-12 text-center text-gray-700">
        &copy; 2025 SafeSpace. All rights reserved.
    </footer>

</body>
</html>
