<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'i8c') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Tailwind CSS & Alpine.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.1/dist/cdn.min.js"></script>
</head>
<body class="font-sans text-gray-900 antialiased" style="background-color: #1e2235;">

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">

        {{-- I8C Logo --}}
        <a href="/" class="mb-6">
            <span style="color: #da532c; font-size: 2.5rem; font-weight: 800; letter-spacing: -2px; line-height: 1;">
                i<span style="color: white;">8</span>c
            </span>
        </a>

        {{-- Auth card --}}
        <div class="w-full sm:max-w-md px-6 py-8 bg-white shadow-xl rounded-2xl">
            {{ $slot }}
        </div>

        <p class="mt-6 text-sm text-gray-500">
            © {{ date('Y') }} i8c — Integratie experts
        </p>
    </div>

</body>
</html>
