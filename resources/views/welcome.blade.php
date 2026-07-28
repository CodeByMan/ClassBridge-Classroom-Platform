<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon-links />
    <title>{{ config('app.name', 'ClassBridge') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="gc-welcome">
        <header class="gc-welcome-header">
            <a href="{{ route('welcome') }}" class="gc-brand" aria-label="ClassBridge home">
                <x-classbridge-mark />
                <span class="gc-brand__title"><strong>Class</strong>Bridge</span>
            </a>
            <a href="{{ auth()->check() ? route('home') : route('login') }}" class="gc-button gc-button--outline">
                {{ auth()->check() ? 'Open app' : 'Sign in' }}
            </a>
        </header>

        <main class="gc-welcome-main">
            <section class="gc-welcome-copy">
                <p class="font-medium text-blue-700 mb-3">A classroom workspace built for learning</p>
                <h1>Organize classwork. Connect with your group.</h1>
                <p>Create or join groups, publish assignments, manage deadlines, submit work, and keep discussions together in one clean workspace.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('home') : route('login') }}" class="gc-button gc-button--primary">
                        Open the application <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    @guest
                        <a href="{{ route('register') }}" class="gc-button gc-button--outline">Create account</a>
                    @endguest
                </div>
            </section>

            <figure class="gc-welcome-visual">
                <img src="{{ asset('images/classbridge-hero.jpg') }}"
                    alt="Teacher guiding students in a technology-enabled collaborative classroom"
                    width="1120" height="980" loading="eager" fetchpriority="high">
                <figcaption class="gc-welcome-visual__caption">
                    <span><i class="fa-solid fa-users" aria-hidden="true"></i> Collaborative classes</span>
                    <span><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i> ZIP submissions</span>
                    <span><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Marks and progress</span>
                </figcaption>
            </figure>
        </main>

        <footer class="gc-footer">© 2026 Developed by Muhammad Ali Nawaz</footer>
    </div>
</body>
</html>
