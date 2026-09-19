<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\ClientRenewalFailedMail;
use App\Mail\ClientTrialCancelledMail;
use App\Mail\ClientTrialEndingReminderMail;
use App\Models\Order;
use App\Models\Package;
use App\Services\MtbcCardCipher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProcessSubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function fakeDetokenizeAndCharge(bool $chargeSucceeds): void
    {
        $cipher = new MtbcCardCipher;

        Http::fake([
            '*/api/auth/token' => Http::response(['status' => true, 'data' => ['accessToken' => 'fake-jwt-token']]),
            '*/api/payment/detokenize' => Http::response([
                'status' => true,
                'data' => ['value' => $cipher->encrypt('4111111111111111'), 'cvv' => $cipher->encrypt('123'), 'referenceNumber' => 'REF123'],
            ]),
            '*/api/payment/Create_Charge' => $chargeSucceeds
                ? Http::response(['status' => true, 'message' => 'Payment Successful', 'data' => ['id' => 'TXN']])
                : Http::response(['status' => false, 'message' => 'Card declined', 'data' => null], 400),
        ]);
    }

    public function test_reminder_is_sent_once_inside_the_window_and_not_resent(): void
    {
        Mail::fake();

        $package = Package::factory()->create();
        $dueSoon = Order::factory()->trialing()->create(['package_id' => $package->id, 'trial_ends_at' => now()->addDays(2)]);
        $dueLater = Order::factory()->trialing()->create(['package_id' => $package->id, 'trial_ends_at' => now()->addDays(10)]);

        $this->artisan('subscriptions:process-billing');

        $dueSoon->refresh();
        $dueLater->refresh();

        $this->assertNotNull($dueSoon->trial_reminder_sent_at);
        $this->assertNull($dueLater->trial_reminder_sent_at);
        Mail::assertQueued(ClientTrialEndingReminderMail::class, 1);

        // A second run the same day must not resend it.
        $this->artisan('subscriptions:process-billing');
        Mail::assertQueued(ClientTrialEndingReminderMail::class, 1);
    }

    public function test_lapsed_trial_without_a_client_response_is_auto_cancelled(): void
    {
        Mail::fake();

        $order = Order::factory()->trialing()->create(['trial_ends_at' => now()->subDay()]);

        $this->artisan('subscriptions:process-billing');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertTrue($order->blockedFromAiGeneration());
        Mail::assertQueued(ClientTrialCancelledMail::class);
    }

    public function test_successful_renewal_advances_next_bill_date_by_one_year_from_the_original_date(): void
    {
        Mail::fake();
        $this->fakeDetokenizeAndCharge(chargeSucceeds: true);

        $originalNextBillDate = now()->subDays(2)->startOfDay();
        $order = Order::factory()->convertedFromTrial()->create(['next_bill_date' => $originalNextBillDate]);

        $this->artisan('subscriptions:process-billing');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(0, $order->renewal_attempts);
        $this->assertTrue($order->next_bill_date->isSameDay($originalNextBillDate->copy()->addYear()));
    }

    public function test_failed_renewal_sets_past_due_and_only_retries_after_the_configured_delay(): void
    {
        Mail::fake();
        $this->fakeDetokenizeAndCharge(chargeSucceeds: false);

        $dueDate = now()->startOfDay();
        $order = Order::factory()->convertedFromTrial()->create(['next_bill_date' => $dueDate]);

        $this->artisan('subscriptions:process-billing');

        $order->refresh();
        $this->assertSame(PaymentStatus::PastDue, $order->payment_status);
        $this->assertSame(1, $order->renewal_attempts);
        $this->assertSame($dueDate->toDateString(), $order->next_bill_date->toDateString());
        Mail::assertQueued(ClientRenewalFailedMail::class, fn ($mail) => $mail->cancelled === false);

        // A second run the same day must not re-attempt yet (next retry is +3 days out).
        $this->fakeDetokenizeAndCharge(chargeSucceeds: false);
        $this->artisan('subscriptions:process-billing');
        $order->refresh();
        $this->assertSame(1, $order->renewal_attempts);

        // 3 days later, the second attempt fires.
        Carbon::setTestNow(now()->addDays(3));
        $this->fakeDetokenizeAndCharge(chargeSucceeds: false);
        $this->artisan('subscriptions:process-billing');
        $order->refresh();
        $this->assertSame(2, $order->renewal_attempts);
        $this->assertSame(OrderStatus::Paid, $order->status);
    }

    public function test_third_consecutive_renewal_failure_cancels_the_subscription(): void
    {
        Mail::fake();
        $this->fakeDetokenizeAndCharge(chargeSucceeds: false);

        $order = Order::factory()->pastDue(2)->create(['next_bill_date' => now()->subDays(8)]);

        $this->artisan('subscriptions:process-billing');

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(3, $order->renewal_attempts);
        $this->assertTrue($order->blockedFromAiGeneration());
        Mail::assertQueued(ClientRenewalFailedMail::class, fn ($mail) => $mail->cancelled === true);
    }
}
