<?php

namespace Tests\Feature;

use App\Mail\ClientPaymentReceiptMail;
use App\Models\Order;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPaymentReceiptMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_the_expected_subject_and_recipient_data(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id, 'name' => 'Sunrise Family Medicine']);
        $package = Package::factory()->create(['name' => 'Professional Compliance']);
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        $mail = new ClientPaymentReceiptMail($order);

        $this->assertSame('Payment Received — Professional Compliance', $mail->envelope()->subject);
        $this->assertSame($order->id, $mail->order->id);
    }

    public function test_it_attaches_a_valid_pdf_receipt(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        $mail = new ClientPaymentReceiptMail($order);
        $attachment = $mail->attachments()[0];

        $bytes = $attachment->attachWith(fn ($path) => null, fn ($data) => $data());

        $this->assertNotNull($bytes);
        $this->assertStringStartsWith('%PDF', $bytes);
    }

    public function test_it_renders_without_error(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        $html = (new ClientPaymentReceiptMail($order))->render();

        $this->assertStringContainsString('Payment Received', $html);
        $this->assertStringContainsString('Go to My Portal', $html);
    }
}
