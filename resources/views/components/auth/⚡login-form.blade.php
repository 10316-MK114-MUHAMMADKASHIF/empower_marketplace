<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        $destination = Auth::user()->isAdmin() ? route('admin.dashboard') : route('portal');

        $this->redirect($destination, navigate: true);
    }
};
?>

<div>
    <form wire:submit="login" novalidate>
        <div class="mb-4">
            <label class="block text-sm font-medium text-[#173045] mb-1.5" for="lf-email">Email address</label>
            <input wire:model="email" id="lf-email" type="email" autocomplete="email" autofocus
                class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                placeholder="you@practice.com">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-sm font-medium text-[#173045]" for="lf-password">Password</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-semibold text-[#1a7aad] hover:text-[#12304f] transition-colors">Forgot password?</a>
            </div>
            <input wire:model="password" id="lf-password" type="password" autocomplete="current-password"
                class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                placeholder="••••••••">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input wire:model="remember" type="checkbox"
                    class="h-4 w-4 rounded border-[#dbe4ee] text-[#76c8c0] focus:ring-[#76c8c0]">
                <span class="text-sm text-[#5d6e7f]">Remember me</span>
            </label>
        </div>

        <button type="submit"
            class="inline-flex items-center gap-1 rounded bg-[#76c8c0] px-5 py-2 text-sm font-bold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors"
            wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
            <span wire:loading.remove>Log In &rarr;</span>
            <span wire:loading>Signing in…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-[#5d6e7f]">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-[#12304f] hover:text-[#76c8c0] transition-colors">Get started</a>
    </p>
</div>
