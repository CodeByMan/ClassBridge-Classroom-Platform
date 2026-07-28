<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon-links />
    <title>Page not found — {{ config('app.name', 'ClassBridge') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <main class="min-h-screen grid place-items-center p-6 bg-gray-50">
        <section class="gc-card gc-card__body max-w-xl text-center">
            <div class="mx-auto mb-5 gc-assignment-icon"><i class="fa-regular fa-file-lines"></i></div>
            <p class="text-sm font-medium text-blue-700">404</p>
            <h1 class="mt-2 text-3xl font-medium">This page is not available</h1>
            <p class="mt-3 text-gray-600">The link may be outdated, or the classroom resource may no longer exist.</p>
            <a href="{{ auth()->check() ? route('home') : route('welcome') }}" class="gc-button gc-button--primary mt-7">Back to home</a>
        </section>
    </main>
</body>
</html>
