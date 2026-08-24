<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} — Empower Marketplace</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-page font-sans antialiased">

    <nav class="sticky top-0 z-50 bg-navy/96 backdrop-blur border-b border-white/8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1.5">
                        <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px]" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-navy text-sm\'>EMPOWER</span>'">
                    </span>
                    <span class="hidden sm:block text-[0.6rem] font-extrabold tracking-widest uppercase text-[#9fb4ce]">Marketplace</span>
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 rounded-lg border border-white/60 px-4 py-2 text-sm font-medium text-white hover:bg-white/10 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ auth()->user()->name ?: 'Account' }}</span>
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open" @click.outside="open = false" x-transition
                                class="absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-lg ring-1 ring-black/5 py-1 z-50">
                                <a href="{{ route('home') }}#pricing" class="block px-4 py-2 text-sm text-empower-text hover:bg-page">Back to Packages</a>
                                <a href="{{ route('password.edit') }}" wire:navigate class="block px-4 py-2 text-sm text-empower-text hover:bg-page">Change Password</a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="block px-4 py-2 text-sm text-empower-text hover:bg-page">Admin Panel</a>
                                @endif
                                <div class="my-1 border-t border-empower-border"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-page">Log out</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('home') }}#pricing" class="rounded-lg border border-white/60 px-4 py-2 text-sm font-medium text-white hover:bg-white/10 transition-colors">Back to Packages</a>
                        <a href="{{ route('login') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-navy-dark hover:bg-accent-dark transition-colors">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="mx-auto w-full max-w-7xl flex-1 min-h-[calc(100dvh-4rem-5.5rem)] px-4 sm:px-6 lg:px-8 py-6">
        {{ $slot }}
    </main>

    <footer class="bg-navy-dark py-4 mt-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3">
            <span class="inline-flex items-center bg-white rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none">
                <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-navy text-xs\'>EMPOWER</span>'">
            </span>
            <p class="text-xs text-white/50">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
