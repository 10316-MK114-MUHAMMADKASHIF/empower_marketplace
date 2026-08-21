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
<body class="bg-[#f4f7fb] text-[#173045] antialiased font-sans">

{{-- Minimal nav for quote page --}}
<nav class="sticky top-0 z-50 bg-[#12304f]/96 backdrop-blur border-b border-white/8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="{{ route('home') }}#pricing" class="flex items-center gap-2.5">
                <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1.5">
                    <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px]" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-[#12304f] text-sm\'>EMPOWER</span>'">
                </span>
                <span class="hidden sm:block text-[0.6rem] font-extrabold tracking-widest uppercase text-[#9fb4ce]">Marketplace</span>
            </a>
            <a href="{{ route('home') }}#pricing" class="rounded-lg border border-white/25 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10 transition-colors">
                Back to Packages
            </a>
        </div>
    </div>
</nav>

{{-- Compact hero --}}
<section class="bg-gradient-to-br from-[#0a2037] via-[#12304f] to-[#1a4a70] py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <span class="inline-block rounded-full border border-[#76c8c0]/40 bg-[#76c8c0]/10 px-4 py-1.5 text-xs font-semibold text-[#76c8c0] tracking-wide mb-4">
            Complete Compliance
        </span>
        <h1 class="text-2xl font-bold text-white mb-2">Let's talk about your practice</h1>
        <p class="text-sm text-white/50">Complete Compliance is fully managed and custom-priced. Share a few details and Empower's team will follow up with a quote.</p>
    </div>
</section>

<section class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex justify-center">
            <div class="w-full max-w-lg rounded-2xl bg-white border border-[#dbe4ee] shadow-sm p-8">
                <livewire:contact-form />
            </div>
        </div>
    </div>
</section>

<footer class="bg-[#0a2037] py-8 mt-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
        <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1.5">
            <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px]" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-[#12304f] text-sm\'>EMPOWER</span>'">
        </span>
        <p class="text-xs text-white/40 text-center">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
    </div>
</footer>

@livewireScripts
</body>
</html>
