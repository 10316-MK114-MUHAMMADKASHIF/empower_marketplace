<?php

use App\Support\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        // Listening is enough — Livewire re-renders this component after the event fires.
    }
};
?>

<a href="{{ route('portal') }}" wire:navigate class="relative inline-flex items-center justify-center h-10 w-10 rounded-lg text-white/85 hover:text-white hover:bg-white/10 transition-colors" aria-label="View cart">
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m-9-4h9m-9 4a1 1 0 100 2 1 1 0 000-2zm9 0a1 1 0 100 2 1 1 0 000-2z" />
    </svg>
    @if(Cart::count() > 0)
        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center h-4.5 w-4.5 rounded-full bg-accent text-[0.62rem] font-extrabold text-navy-dark">
            {{ Cart::count() }}
        </span>
    @endif
</a>
