<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password — {{ config('app.name', 'Empower') }}</title>
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
                        <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-[#0e3a61] text-xs\'>EMPOWER</span>'">
                    </span>
                    <span class="text-[0.7rem] font-extrabold tracking-[0.12em] uppercase text-[#5c778d]">Marketplace</span>
                </a>
                <a href="{{ route('home') }}#pricing"
                    class="border border-[#9ed3e9] bg-white text-[#087fa9] text-sm font-medium px-4 py-2 rounded hover:bg-[#eef8fd] transition-colors">
                    Back to Packages
                </a>
            </div>
        </div>
    </nav>

    <div style="background: radial-gradient(circle at 84% 18%, rgba(11, 158, 208, 0.36), transparent 34%), radial-gradient(circle at 8% 0%, rgba(34, 153, 221, 0.20), transparent 30%), linear-gradient(115deg, #f2f8fd 0%, #dff1fb 44%, #c7e7f6 100%); padding: 1.75rem 0;">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-[0.7rem] font-extrabold tracking-[0.08em] uppercase bg-[#e9f7fc] text-[#087fa9] mb-2">Account</span>
            <h1 class="text-2xl font-bold text-[#0e3a61] mb-1">Forgot your password?</h1>
            <p class="text-[#5c778d] text-sm">No problem. We'll email you a link to reset it.</p>
        </div>
    </div>

    <main class="flex-1 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-xl mx-auto">
                <div class="bg-white border border-[#d4e5f1] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">

                    <div class="flex border-b border-[#d4e5f1] px-5 pt-4">
                        <a href="{{ route('login') }}" class="pb-3 px-4 text-sm font-medium text-[#0b9ed0] border-b-2 border-transparent -mb-px hover:text-[#0e3a61] transition-colors">Back to Login</a>
                        <span class="pb-3 px-4 text-sm font-semibold text-[#0e3a61] border-b-2 border-[#0e3a61] -mb-px cursor-default">Reset Password</span>
                    </div>

                    <div class="px-5 py-5">
                        <h3 class="text-lg font-semibold text-[#173a59] mb-1">Reset Your Password</h3>
                        <p class="text-xs text-[#5c778d] mb-5">Enter the email address associated with your account.</p>
                        <livewire:auth.forgot-password-form />
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-[#d4e5f1] py-4">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3">
            <span class="inline-flex items-center bg-[#f9fcff] rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none ring-1 ring-[#d4e5f1]">
                <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[22px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-[#0e3a61] text-xs\'>EMPOWER</span>'">
            </span>
            <p class="text-xs text-[#5c778d]">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
