<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|current_password')]
    public string $currentPassword = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public bool $updated = false;

    public function updatePassword(): void
    {
        $this->validate();

        Auth::user()->forceFill(['password' => Hash::make($this->password)])->save();

        $this->reset(['currentPassword', 'password', 'password_confirmation']);
        $this->updated = true;
    }
};
?>

<div>
    @if($updated)
        <div class="flex items-center gap-3 rounded-xl bg-[#eef8f3] border border-[#bfe3d2] px-4 py-3.5 mb-4">
            <span class="text-[#117a51]">&#10003;</span>
            <p class="text-sm font-semibold text-[#0f7a4f]">Your password has been updated.</p>
        </div>
    @endif

    <form wire:submit="updatePassword" novalidate>
        <div class="mb-4">
            <label class="block text-sm font-medium text-[#173a59] mb-1.5" for="cpf-current-password">Current password</label>
            <input wire:model="currentPassword" id="cpf-current-password" type="password" autocomplete="current-password"
                class="w-full rounded-xl border border-[#d4e5f1] bg-white px-4 py-2.5 text-sm text-[#173a59] placeholder-[#5c778d]/60 focus:outline-none focus:ring-2 focus:ring-[#0b9ed0] focus:border-transparent transition"
                placeholder="••••••••">
            @error('currentPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-[#173a59] mb-1.5" for="cpf-password">New password</label>
            <input wire:model="password" id="cpf-password" type="password" autocomplete="new-password"
                class="w-full rounded-xl border border-[#d4e5f1] bg-white px-4 py-2.5 text-sm text-[#173a59] placeholder-[#5c778d]/60 focus:outline-none focus:ring-2 focus:ring-[#0b9ed0] focus:border-transparent transition"
                placeholder="••••••••">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-[#173a59] mb-1.5" for="cpf-password-confirmation">Confirm new password</label>
            <input wire:model="password_confirmation" id="cpf-password-confirmation" type="password" autocomplete="new-password"
                class="w-full rounded-xl border border-[#d4e5f1] bg-white px-4 py-2.5 text-sm text-[#173a59] placeholder-[#5c778d]/60 focus:outline-none focus:ring-2 focus:ring-[#0b9ed0] focus:border-transparent transition"
                placeholder="••••••••">
        </div>

        <button type="submit"
            class="inline-flex items-center gap-1 rounded bg-[#2299dd] px-5 py-2 text-sm font-bold text-white hover:bg-[#087fa9] transition-colors"
            wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
            <span wire:loading.remove>Update Password &rarr;</span>
            <span wire:loading>Updating…</span>
        </button>
    </form>
</div>
