<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — {{ config('app.name', 'Empower') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f4f7fb] font-sans antialiased flex flex-col">

    <nav class="sticky top-0 z-50 bg-[#12304f]/96 backdrop-blur border-b border-white/[0.08]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="inline-flex items-center bg-white rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none">
                        <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-[#12304f] text-xs\'>EMPOWER</span>'">
                    </span>
                    <span class="text-[0.7rem] font-extrabold tracking-[0.12em] uppercase text-[#9fb4ce]">Marketplace</span>
                </a>
                <a href="{{ route('home') }}#pricing"
                    class="border border-white/60 text-white text-sm font-medium px-4 py-2 rounded hover:bg-white/10 transition-colors">
                    Back to Packages
                </a>
            </div>
        </div>
    </nav>

    <div style="background: radial-gradient(circle at top right, rgba(118,200,192,0.2), transparent 32%), linear-gradient(145deg, #12304f 0%, #1c416a 100%); padding: 1.75rem 0;">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-[0.7rem] font-extrabold tracking-[0.08em] uppercase bg-[#76c8c0]/[0.16] text-[#dff7f3] mb-2">Account</span>
            <h1 class="text-2xl font-bold text-white mb-1">Welcome back</h1>
            <p class="text-white/50 text-sm">Log in to manage your packages and documents, or sign up to get started.</p>
        </div>
    </div>

    <main class="flex-1 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-xl mx-auto">
                <div class="bg-white border border-[#dbe4ee] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">

                    <div class="flex border-b border-[#dbe4ee] px-5 pt-4">
                        <span class="pb-3 px-4 text-sm font-semibold text-[#12304f] border-b-2 border-[#12304f] -mb-px cursor-default">Login</span>
                        <a href="{{ route('register') }}" class="pb-3 px-4 text-sm font-medium text-[#1a7aad] border-b-2 border-transparent -mb-px hover:text-[#12304f] transition-colors">Sign Up</a>
                    </div>

                    <div class="px-5 py-5">
                        <h3 class="text-lg font-semibold text-[#173045] mb-1">Log In</h3>
                        <p class="text-xs text-[#5d6e7f] mb-5">Enter your credentials to access your portal.</p>
                        <livewire:auth.login-form />
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-[#12304f] py-4">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3">
            <span class="inline-flex items-center bg-white rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none">
                <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-[#12304f] text-xs\'>EMPOWER</span>'">
            </span>
            <p class="text-xs text-white/50">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
