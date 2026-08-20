<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    // ── Cart helper ─────────────────────────────────────────────────────────

    public function test_cart_add_and_remove(): void
    {
        $package = Package::factory()->create(['slug' => 'essential']);

        Cart::add($package->id);
        $this->assertTrue(Cart::has($package->id));
        $this->assertSame(1, Cart::count());

        Cart::remove($package->id);
        $this->assertFalse(Cart::has($package->id));
        $this->assertSame(0, Cart::count());
    }

    public function test_cart_add_is_idempotent(): void
    {
        $package = Package::factory()->create(['slug' => 'essential']);

        Cart::add($package->id);
        Cart::add($package->id);

        $this->assertSame(1, Cart::count());
    }

    public function test_cart_packages_and_total(): void
    {
        $essential = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999]);
        $professional = Package::factory()->create(['slug' => 'professional', 'annual_price' => 1299]);

        Cart::add($essential->id);
        Cart::add($professional->id);

        $this->assertEqualsCanonicalizing(
            [$essential->id, $professional->id],
            Cart::packages()->pluck('id')->all()
        );
        $this->assertEquals(2298, Cart::total());
    }

    public function test_cart_clear(): void
    {
        $package = Package::factory()->create(['slug' => 'essential']);
        Cart::add($package->id);

        Cart::clear();

        $this->assertSame(0, Cart::count());
    }

    // ── Add-to-cart button component ────────────────────────────────────────

    public function test_add_to_cart_button_adds_package_and_dispatches_event(): void
    {
        $package = Package::factory()->create(['slug' => 'essential']);

        Livewire::test('add-to-cart-button', ['packageId' => $package->id])
            ->call('addToCart')
            ->assertDispatched('cart-updated')
            ->assertDispatched('toast', message: "{$package->name} added to cart")
            ->assertSee('In Cart');

        $this->assertTrue(Cart::has($package->id));
    }

    public function test_add_to_cart_button_can_remove_package(): void
    {
        $package = Package::factory()->create(['slug' => 'essential']);
        Cart::add($package->id);

        Livewire::test('add-to-cart-button', ['packageId' => $package->id])
            ->call('removeFromCart')
            ->assertDispatched('cart-updated')
            ->assertSee('Add to Cart');

        $this->assertFalse(Cart::has($package->id));
    }

    public function test_add_to_cart_button_disabled_for_unavailable_package(): void
    {
        Livewire::test('add-to-cart-button', ['packageId' => 0])
            ->assertSee('Currently Unavailable');
    }

    // ── Cart badge component ─────────────────────────────────────────────────

    public function test_cart_badge_shows_item_count(): void
    {
        $package = Package::factory()->create(['slug' => 'essential']);
        Cart::add($package->id);

        Livewire::test('cart-badge')->assertSee('1');
    }

    public function test_cart_badge_reflects_cart_state_on_the_homepage(): void
    {
        $package = Package::factory()->create(['slug' => 'essential', 'is_active' => true]);

        // No badge count element until something is in the cart.
        $this->withoutVite()->get('/')->assertDontSee('-top-1 -right-1', false);

        Livewire::test('add-to-cart-button', ['packageId' => $package->id])->call('addToCart');

        $this->withoutVite()->get('/')->assertSee('-top-1 -right-1', false);
    }

    public function test_cart_persists_for_guest_across_requests(): void
    {
        $package = Package::factory()->create(['slug' => 'essential', 'is_active' => true]);

        Livewire::test('add-to-cart-button', ['packageId' => $package->id])->call('addToCart');

        $this->withoutVite()->get(route('portal'))->assertSee($package->name);
    }

    public function test_cart_persists_for_authenticated_user_across_requests(): void
    {
        $user = User::factory()->create();
        $package = Package::factory()->create(['slug' => 'essential', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('add-to-cart-button', ['packageId' => $package->id])
            ->call('addToCart');

        $this->withoutVite()->actingAs($user)->get(route('portal'))->assertSee($package->name);
    }
}
