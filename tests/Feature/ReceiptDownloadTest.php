<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_receipt(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'name' => 'Essential Compliance']);
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        $response = $this->withoutVite()->actingAs($user)->get(route('orders.receipt', $order));

        $response->assertOk();
        $response->assertSee('Payment Receipt');
        $response->assertSee('Essential Compliance');
        $response->assertSee('PAID');
    }

    public function test_other_users_cannot_download_someone_elses_receipt(): void
    {
        $owner = User::factory()->create();
        Practice::factory()->create(['user_id' => $owner->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id, 'package_id' => $package->id]);

        $other = User::factory()->create();

        $this->withoutVite()->actingAs($other)->get(route('orders.receipt', $order))->assertForbidden();
    }

    public function test_unpaid_order_receipt_is_not_available(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->pendingPayment()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        $this->withoutVite()->actingAs($user)->get(route('orders.receipt', $order))->assertNotFound();
    }
}
