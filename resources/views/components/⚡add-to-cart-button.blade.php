<?php

use App\Models\Package;
use App\Support\Cart;
use Livewire\Component;

new class extends Component
{
    public int $packageId;

    public string $variant = 'navy';

    public function addToCart(): void
    {
        Cart::add($this->packageId);
        $this->dispatch('cart-updated');

        $name = Package::find($this->packageId)?->name ?? 'Package';
        $this->dispatch('toast', message: "{$name} added to cart");
    }

    public function removeFromCart(): void
    {
        Cart::remove($this->packageId);
        $this->dispatch('cart-updated');
    }
};
?>

<div>
    @if($packageId <= 0)
        <button type="button" disabled
            class="block w-full rounded-xl border border-empower-border bg-page py-3 text-center text-sm font-semibold text-empower-muted cursor-not-allowed">
            Currently Unavailable
        </button>
    @elseif(Cart::has($packageId))
        <button type="button" wire:click="removeFromCart"
            class="block w-full rounded-xl border border-[#76c8c0] bg-[#eafbf7] py-3 text-center text-sm font-semibold text-[#0f7a4f] hover:bg-[#d7f3ea] transition-colors">
            &#10003; In Cart &middot; Remove
        </button>
    @elseif($variant === 'accent')
        <button type="button" wire:click="addToCart"
            class="block w-full rounded-xl bg-[#76c8c0] py-3 text-center text-sm font-semibold text-[#0a2037] hover:bg-[#5bb2aa] transition-colors">
            Add to Cart
        </button>
    @else
        <button type="button" wire:click="addToCart"
            class="block w-full rounded-xl bg-[#12304f] py-3 text-center text-sm font-semibold text-white hover:bg-[#0a2037] transition-colors">
            Add to Cart
        </button>
    @endif
</div>
