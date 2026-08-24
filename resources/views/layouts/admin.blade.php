<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Empower') }} Admin — @yield('title', 'Dashboard')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-100 font-sans antialiased">

    <nav class="sticky top-0 z-50 bg-[#1a2e4a] shadow">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <span class="text-lg font-semibold text-white">Empower</span>
                        <span class="rounded bg-[#1a7aad] px-2 py-0.5 text-xs font-bold text-white uppercase tracking-wider">Admin</span>
                    </a>

                    <div class="hidden md:flex items-center gap-6">
                        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-sm text-gray-300 hover:text-white transition-colors">Dashboard</a>
                        <a href="{{ route('admin.submissions') }}" wire:navigate class="text-sm text-gray-300 hover:text-white transition-colors">Submissions</a>
                        <a href="{{ route('admin.documents') }}" wire:navigate class="text-sm text-gray-300 hover:text-white transition-colors">Documents</a>
                        <a href="{{ route('admin.leads') }}" wire:navigate class="text-sm text-gray-300 hover:text-white transition-colors">Leads</a>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-400">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-400 hover:text-white transition-colors">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
