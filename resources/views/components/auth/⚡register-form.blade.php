<?php

use App\Enums\UserRole;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public ?string $package = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|email|max:150|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|string|same:password')]
    public string $passwordConfirmation = '';

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => UserRole::Client,
        ]);

        Practice::create([
            'user_id' => $user->id,
            'name' => '',
        ]);

        event(new Registered($user));

        Auth::login($user);

        $packageSlug = $this->package ?: session('intended_package');

        $this->redirect(route('portal', $packageSlug ? ['package' => $packageSlug] : []), navigate: true);
    }
};
?>

<div>
    <form wire:submit="register" novalidate>
        <div class="mb-4">
            <label class="block text-sm font-medium text-[#173045] mb-1.5" for="rf-name">Your name</label>
            <input wire:model="name" id="rf-name" type="text" autocomplete="name" autofocus
                class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                placeholder="Jane Provider">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-[#173045] mb-1.5" for="rf-email">Email address</label>
            <input wire:model="email" id="rf-email" type="email" autocomplete="email"
                class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                placeholder="jane@practice.com">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-[#173045] mb-1.5" for="rf-password">Password</label>
            <input wire:model="password" id="rf-password" type="password" autocomplete="new-password"
                class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                placeholder="Min. 8 characters">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-[#173045] mb-1.5" for="rf-confirm">Confirm password</label>
            <input wire:model="passwordConfirmation" id="rf-confirm" type="password" autocomplete="new-password"
                class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition"
                placeholder="Repeat password">
            @error('passwordConfirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="inline-flex items-center gap-1 rounded bg-[#76c8c0] px-5 py-2 text-sm font-bold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors"
            wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
            <span wire:loading.remove>Sign Up &rarr;</span>
            <span wire:loading>Creating account…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-[#5d6e7f]">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-[#12304f] hover:text-[#76c8c0] transition-colors">Sign in</a>
    </p>
</div>
