<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public string $token = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public bool $reset = false;

    public function mount(string $token, string $email = ''): void
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user) {
                $user->forceFill(['password' => Hash::make($this->password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset = true;
    }
};
?>

<div>
    @if($reset)
        <div class="text-center py-6">
            <div class="w-14 h-14 rounded-full bg-[#eef8f3] text-[#117a51] inline-flex items-center justify-center text-2xl mb-3 mx-auto">&#10003;</div>
            <h4 class="text-base font-semibold text-[#173045] mb-1">Password reset</h4>
            <p class="text-sm text-[#5d6e7f] mb-4">Your password has been reset successfully.</p>
            <a href="{{ route('login') }}" wire:navigate
                class="inline-flex items-center gap-1 rounded bg-[#76c8c0] px-5 py-2 text-sm font-bold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors">
                Go to Login &rarr;
            </a>
        </div>
    @else
        <form wire:submit="resetPassword" novalidate>
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#173045] mb-1.5" for="rpf-email">Email address</label>
                <input wire:model="email" id="rpf-email" type="email" autocomplete="email" autofocus
                    class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                    placeholder="you@practice.com">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-[#173045] mb-1.5" for="rpf-password">New password</label>
                <input wire:model="password" id="rpf-password" type="password" autocomplete="new-password"
                    class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                    placeholder="••••••••">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-[#173045] mb-1.5" for="rpf-password-confirmation">Confirm new password</label>
                <input wire:model="password_confirmation" id="rpf-password-confirmation" type="password" autocomplete="new-password"
                    class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                    placeholder="••••••••">
            </div>

            <button type="submit"
                class="inline-flex items-center gap-1 rounded bg-[#76c8c0] px-5 py-2 text-sm font-bold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors"
                wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                <span wire:loading.remove>Reset Password &rarr;</span>
                <span wire:loading>Resetting…</span>
            </button>
        </form>
    @endif
</div>
