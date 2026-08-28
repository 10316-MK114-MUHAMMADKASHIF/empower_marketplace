<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('app.name', 'Empower') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f2f8fd] font-sans antialiased flex flex-col">

    <nav class="sticky top-0 z-50 bg-white/96 backdrop-blur border-b border-[#d4e5f1] shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="inline-flex items-center bg-white rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none">
                        <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[28px] sm:h-[45px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-[#0e3a61] text-xs\'>EMPOWER</span>'">
                    </span>
                </a>
                <a href="{{ route('home') }}#pricing"
                    class="border border-[#9ed3e9] bg-white text-[#087fa9] text-sm font-medium px-4 py-2 rounded hover:bg-[#eef8fd] transition-colors">
                    Back to Packages
                </a>
            </div>
        </div>
    </nav>

    <div style="{{ $heroStyle ?? 'background: radial-gradient(circle at 84% 18%, rgba(11, 158, 208, 0.36), transparent 34%), radial-gradient(circle at 8% 0%, rgba(34, 153, 221, 0.20), transparent 30%), linear-gradient(115deg, #f2f8fd 0%, #dff1fb 44%, #c7e7f6 100%);' }} padding: 1.75rem 0;">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-[0.7rem] font-extrabold tracking-[0.08em] uppercase bg-[#e9f7fc] text-[#087fa9] mb-2">{{ $heroBadge ?? 'Account' }}</span>
            <h1 class="text-2xl font-bold text-[#0e3a61] mb-1">{{ $heroTitle }}</h1>
            <p class="text-[#5c778d] text-sm">{{ $heroSubtitle }}</p>
        </div>
    </div>

    <main class="flex-1 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-xl mx-auto">
                {{ $slot }}
            </div>
        </div>
    </main>

    @isset($footer)
        {{ $footer }}
    @else
        <footer class="bg-white border-t border-[#d4e5f1] py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center gap-3 text-center">
                <span class="inline-flex items-center bg-[#f9fcff] rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none ring-1 ring-[#d4e5f1]">
                    <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[28px] sm:h-[45px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-[#0e3a61] text-xs\'>EMPOWER</span>'">
                </span>
                <p class="text-xs text-[#5c778d]">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
            </div>
        </footer>
    @endisset

    @livewireScripts
</body>
</html>
