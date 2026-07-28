<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-favicon-links />
    <title>{{ config('app.name', 'ClassBridge') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="gc-body antialiased">
    <div class="gc-shell">
        @include('layouts.navigation')

        <div class="gc-main">
            @if (isset($header))
                <header class="gc-contextbar">
                    <div class="gc-contextbar__inner">
                        {{ $header }}
                    </div>
                </header>
            @endif

            @include('components.flash-message')

            <main class="gc-content">
                {{ $slot }}
            </main>

            <footer class="gc-footer">© 2026 Developed by Muhammad Ali Nawaz</footer>
        </div>
    </div>

    @livewire('wire-elements-modal')
    @livewireScripts
</body>
</html>
