<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} — My Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 font-sans antialiased">

    <nav class="sticky top-0 z-50 bg-[#1a2e4a] shadow">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Empower" class="h-7 w-auto brightness-0 invert" onerror="this.style.display='none'">
                    <span class="text-lg font-semibold text-white">Empower</span>
                </a>

                <div class="flex items-center gap-4">
                    <span class="hidden sm:block text-sm text-gray-300">{{ auth()->user()->name }}</span>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-full bg-[#1a7aad] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#1d8fc4] transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Account</span>
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition
                            class="absolute right-0 mt-2 w-44 rounded-lg bg-white shadow-lg ring-1 ring-black/5 py-1 z-50">
                            <a href="{{ route('portal') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">My Portal</a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Admin Panel</a>
                            @endif
                            <div class="my-1 border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>

