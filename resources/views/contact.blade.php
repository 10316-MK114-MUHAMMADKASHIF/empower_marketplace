<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request a Quote — Proactive Compliance by Empower</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#f2f8fd] text-[#173a59] antialiased font-sans">

{{-- Sticky Nav (same menu as home) --}}
<nav x-data="{ open: false }" class="sticky top-0 z-50 bg-white/96 backdrop-blur border-b border-[#d4e5f1] shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1.5">
                    <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[34px] sm:h-[45px]" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-[#0e3a61] text-sm\'>EMPOWER</span>'">
                </span>
                <span class="hidden sm:block text-[0.6rem] font-extrabold tracking-widest uppercase text-[#5c778d]">Marketplace</span>
            </a>

            <div class="hidden md:flex items-center gap-7">
                <a href="{{ route('home') }}#services" class="text-sm font-medium text-[#5c778d] hover:text-[#0e3a61] transition-colors">Services</a>
                <a href="{{ route('home') }}#process" class="text-sm font-medium text-[#5c778d] hover:text-[#0e3a61] transition-colors">Process</a>
                <a href="{{ route('home') }}#pricing" class="text-sm font-medium text-[#5c778d] hover:text-[#0e3a61] transition-colors">Pricing</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-[#0e3a61] hover:text-[#0b9ed0] transition-colors">Contact</a>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="inline-flex md:hidden items-center justify-center rounded-lg border border-[#9ed3e9] bg-white p-2 text-[#087fa9] hover:bg-[#eef8fd] transition-colors" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-controls="mobile-menu" aria-label="Toggle navigation menu">
                    <svg x-show="!open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="open" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                @auth
                    <a href="{{ route('portal') }}" class="rounded-lg bg-[#2299dd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors">My Portal</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-[#5c778d] hover:text-[#0e3a61] transition-colors">Log in</a>
                    <a href="{{ route('home') }}#pricing" class="rounded-lg bg-[#2299dd] px-4 py-2 text-sm font-semibold text-white hover:bg-[#087fa9] transition-colors">Get Started</a>
                @endauth
            </div>
        </div>

        <div id="mobile-menu" x-show="open" x-transition x-cloak class="md:hidden border-t border-[#d4e5f1] py-3">
            <div class="flex flex-col gap-1">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-[#5c778d] hover:bg-[#eef8fd] hover:text-[#0e3a61] transition-colors">Home</a>
                <a href="{{ route('home') }}#services" class="rounded-lg px-3 py-2 text-sm font-medium text-[#5c778d] hover:bg-[#eef8fd] hover:text-[#0e3a61] transition-colors">Services</a>
                <a href="{{ route('home') }}#process" class="rounded-lg px-3 py-2 text-sm font-medium text-[#5c778d] hover:bg-[#eef8fd] hover:text-[#0e3a61] transition-colors">Process</a>
                <a href="{{ route('home') }}#pricing" class="rounded-lg px-3 py-2 text-sm font-medium text-[#5c778d] hover:bg-[#eef8fd] hover:text-[#0e3a61] transition-colors">Pricing</a>
                <a href="{{ route('contact') }}" x-on:click="open = false" class="rounded-lg px-3 py-2 text-sm font-medium text-[#0e3a61] hover:bg-[#eef8fd] hover:text-[#0b9ed0] transition-colors">Contact</a>
            </div>
        </div>
    </div>
</nav>

{{-- Compact hero --}}
<section class="py-12" style="background: radial-gradient(circle at 84% 18%, rgba(11, 158, 208, 0.36), transparent 34%), radial-gradient(circle at 8% 0%, rgba(34, 153, 221, 0.20), transparent 30%), linear-gradient(115deg, #f2f8fd 0%, #dff1fb 44%, #c7e7f6 100%);">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <span class="inline-block rounded-full border border-[#0b9ed0]/30 bg-[#e9f7fc] px-4 py-1.5 text-xs font-semibold text-[#087fa9] tracking-wide mb-4">
            Complete Compliance
        </span>
        <h1 class="text-2xl font-bold text-[#0e3a61] mb-2">Let's talk about your practice</h1>
        <p class="text-sm text-[#5c778d]">Complete Compliance is fully managed and custom-priced. Share a few details and Empower's team will follow up with a quote.</p>
    </div>
</section>

<section class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex justify-center">
            <div class="w-full max-w-lg rounded-2xl bg-white border border-[#d4e5f1] shadow-sm p-8">
                <livewire:contact-form />
            </div>
        </div>
    </div>
</section>

<footer class="bg-white border-t border-[#d4e5f1] py-8 mt-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-3 text-center md:text-left">
        <span class="inline-flex items-center rounded-lg bg-[#f9fcff] px-2.5 py-1.5 ring-1 ring-[#d4e5f1]">
            <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[34px] sm:h-[45px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-[#0e3a61] text-sm\'>EMPOWER</span>'">
        </span>
        <p class="text-xs text-[#5c778d]">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
    </div>
</footer>

@livewireScripts
</body>
</html>
