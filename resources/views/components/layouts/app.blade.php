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
@php
    $containerClass = request()->routeIs('admin.*') ? 'max-w-[96rem]' : 'max-w-7xl';
@endphp
<body class="min-h-screen flex flex-col bg-page font-sans antialiased">

    <nav class="sticky top-0 z-50 bg-white/96 backdrop-blur border-b border-empower-border shadow-sm">
        <div class="mx-auto {{ $containerClass }} px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1.5">
                        <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[28px] sm:h-[45px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-navy text-sm\'>EMPOWER</span>'">
                    </span>
                    <span class="hidden sm:block text-[0.6rem] font-extrabold tracking-widest uppercase text-empower-muted">Marketplace</span>
                </a>

                <livewire:header-account-menu />
            </div>
        </div>
    </nav>

    <main class="mx-auto w-full {{ $containerClass }} flex-1 px-4 sm:px-6 lg:px-8 py-6">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-empower-border py-4">
        <div class="mx-auto {{ $containerClass }} px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-3 text-center md:text-left">
            <span class="inline-flex items-center bg-white rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none">
                <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[28px] sm:h-[45px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-navy text-xs\'>EMPOWER</span>'">
            </span>
            <p class="text-xs text-empower-muted">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
