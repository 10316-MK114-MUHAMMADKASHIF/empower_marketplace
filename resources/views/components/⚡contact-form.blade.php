<?php

use App\Enums\UserRole;
use App\Mail\LeadConfirmationMail;
use App\Mail\NewLeadNotificationMail;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|email|max:150')]
    public string $email = '';

    #[Validate('required|string|max:30')]
    public string $phone = '';

    #[Validate('required|string|max:2000')]
    public string $message = '';

    public string $packageInterest = '';

    public bool $submitted = false;

    public function mount(): void
    {
        $this->packageInterest = request()->query('package', '');
    }

    public function submit(): void
    {
        $this->validate();

        $lead = Lead::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            'package_interest' => $this->packageInterest ?: null,
        ]);

        Mail::to($lead->email)->send(new LeadConfirmationMail($lead));

        User::where('role', UserRole::Admin)->pluck('email')->each(
            fn (string $adminEmail) => Mail::to($adminEmail)->send(new NewLeadNotificationMail($lead))
        );

        $this->submitted = true;
    }
};
?>

<div>
    @if($submitted)
        <div class="text-center py-10">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-[#76c8c0]/20">
                <svg class="h-8 w-8 text-[#5bb2aa]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-[#12304f] mb-2">Thanks, {{ $name }}!</h3>
            <p class="text-sm text-[#5d6e7f] mb-6">We've received your message and will be in touch shortly about Complete Compliance.</p>
            <a href="{{ route('home') }}#pricing" class="inline-block rounded-xl border border-[#dbe4ee] px-6 py-2.5 text-sm font-semibold text-[#12304f] hover:bg-[#f4f7fb] transition-colors">Back to Packages</a>
        </div>
    @else
        <h3 class="text-lg font-semibold text-[#12304f] mb-1">Request a Quote</h3>
        <p class="text-sm text-[#5d6e7f] mb-6">Fields marked required help our team follow up quickly.</p>

        <form wire:submit="submit" novalidate>
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#173045] mb-1.5" for="cf-name">Name</label>
                <input wire:model="name" id="cf-name" type="text" placeholder="Jane Provider"
                    class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-[#173045] mb-1.5" for="cf-email">Email</label>
                <input wire:model="email" id="cf-email" type="email" placeholder="jane@practice.com"
                    class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-[#173045] mb-1.5" for="cf-phone">Phone Number</label>
                <input wire:model="phone" id="cf-phone" type="tel" placeholder="(555) 123-4567"
                    class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-[#173045] mb-1.5" for="cf-message">Message</label>
                <textarea wire:model="message" id="cf-message" rows="4"
                    placeholder="Tell us a bit about your practice and what you're looking for."
                    class="w-full rounded-xl border border-[#dbe4ee] bg-white px-4 py-2.5 text-sm text-[#173045] placeholder-[#5d6e7f]/60 focus:outline-none focus:ring-2 focus:ring-[#76c8c0] focus:border-transparent transition resize-none"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-[#76c8c0] px-6 py-3 text-sm font-semibold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors"
                wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                <span wire:loading.remove>Send Message</span>
                <span wire:loading>Sending…</span>
                <svg wire:loading.remove class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </button>
        </form>
    @endif
</div>
