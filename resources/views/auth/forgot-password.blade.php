<x-layouts.auth title="Forgot Password" hero-title="Forgot your password?"
    hero-subtitle="No problem. We'll email you a link to reset it.">
    <div
        class="bg-white border border-[#d4e5f1] rounded-[1.25rem] shadow-[0_18px_50px_rgba(10,32,55,0.08)] overflow-hidden">

        <div class="flex border-b border-[#d4e5f1] px-5 pt-4">
            <a href="{{ route('login') }}"
                class="pb-3 px-4 text-sm font-medium text-[#0b9ed0] border-b-2 border-transparent -mb-px hover:text-[#0e3a61] transition-colors">Back
                to Login</a>
            <span
                class="pb-3 px-4 text-sm font-semibold text-[#0e3a61] border-b-2 border-[#0e3a61] -mb-px cursor-default">Reset
                Password</span>
        </div>

        <div class="px-5 py-5">
            <h3 class="text-lg font-semibold text-[#173a59] mb-1">Reset Your Password</h3>
            <p class="text-xs text-[#5c778d] mb-5">Enter the email address associated with your account.</p>
            <livewire:auth.forgot-password-form />
        </div>
    </div>
</x-layouts.auth>
