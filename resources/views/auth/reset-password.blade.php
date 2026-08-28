<x-layouts.auth
    title="Reset Password"
    hero-title="Choose a new password"
    hero-subtitle="Make it something secure that you haven't used before."
    :hero-style="'background: radial-gradient(circle at top right, rgba(11,158,208,0.12), transparent 32%), linear-gradient(145deg, #f9fcff 0%, #e7f3fb 100%);'"
>
    <div class="bg-white border border-[#d4e5f1] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">

        <div class="flex border-b border-[#d4e5f1] px-5 pt-4">
            <a href="{{ route('login') }}" class="pb-3 px-4 text-sm font-medium text-[#0b9ed0] border-b-2 border-transparent -mb-px hover:text-[#0e3a61] transition-colors">Back to Login</a>
            <span class="pb-3 px-4 text-sm font-semibold text-[#0e3a61] border-b-2 border-[#0e3a61] -mb-px cursor-default">New Password</span>
        </div>

        <div class="px-5 py-5">
            <h3 class="text-lg font-semibold text-[#173a59] mb-1">Set a New Password</h3>
            <p class="text-xs text-[#5c778d] mb-5">Enter your email and a new password for your account.</p>
            <livewire:auth.reset-password-form :token="$token" :email="request()->query('email', '')" />
        </div>
    </div>

    <x-slot:footer>
        <footer class="bg-[#0b2e4b] py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-3">
                <span class="inline-flex items-center bg-white rounded-[0.6rem] px-2.5 py-[0.35rem] leading-none">
                    <img src="{{ asset('images/logo.webp') }}" alt="Empower" class="h-[45px] w-auto" onerror="this.parentElement.innerHTML='<span class=\'font-extrabold text-[#0e3a61] text-xs\'>EMPOWER</span>'">
                </span>
                <p class="text-xs text-white/50">&copy; {{ date('Y') }} CareCloud, Inc. &middot; Empower, by CareCloud &middot; In collaboration with Frier Levitt</p>
            </div>
        </footer>
    </x-slot:footer>
</x-layouts.auth>
