<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Empower') }} — @yield('title', 'Compliance Marketplace')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white font-sans antialiased">

    {{-- Public navbar --}}
    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Empower" class="h-8 w-auto" onerror="this.style.display='none'">
                    <span class="text-xl font-semibold text-[#1a2e4a]">Empower</span>
                </a>

                <div class="hidden md:flex items-center gap-8">
                    <a href="{{ route('home') }}#services" class="text-sm text-gray-600 hover:text-[#1a7aad] transition-colors">Services</a>
                    <a href="{{ route('home') }}#pricing" class="text-sm text-gray-600 hover:text-[#1a7aad] transition-colors">Pricing</a>
                    <a href="{{ route('home') }}#process" class="text-sm text-gray-600 hover:text-[#1a7aad] transition-colors">Process</a>
                    <a href="{{ route('contact') }}" class="text-sm text-gray-600 hover:text-[#1a7aad] transition-colors">Contact</a>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('portal') }}" wire:navigate class="text-sm font-medium text-[#1a7aad] hover:underline">My Portal</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-[#1a7aad] transition-colors">Log in</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-[#1a7aad] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#1a2e4a] transition-colors">Get Started</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    <footer class="bg-[#1a2e4a] text-white py-10 mt-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-400">&copy; {{ date('Y') }} Empower Marketplace. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="{{ route('home') }}#pricing" class="text-sm text-gray-400 hover:text-white transition-colors">Pricing</a>
                    <a href="{{ route('contact') }}" class="text-sm text-gray-400 hover:text-white transition-colors">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
