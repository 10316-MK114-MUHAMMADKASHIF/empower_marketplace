<?php

namespace Tests\Feature;

use App\Enums\AiExtractionStatus;
use App\Enums\IntakeSubmissionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Jobs\GenerateComplianceDocument;
use App\Mail\AdminIntakeSubmittedMail;
use App\Mail\AdminPaymentReceivedMail;
use App\Mail\WelcomeCredentialsMail;
use App\Models\GeneratedDocument;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use App\Models\Order;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    // ── Guest access ────────────────────────────────────────────────────────

    public function test_guest_can_view_portal_and_sees_account_creation_fields(): void
    {
        $this->withoutVite()->get(route('portal'))
            ->assertOk()
            ->assertSee('Account Information')
            ->assertSee('Payment Details');
    }

    public function test_authenticated_user_sees_portal(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);

        $this->withoutVite()->actingAs($user)->get(route('portal'))->assertOk();
    }

    public function test_admin_visiting_portal_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test('portal')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_visiting_portal_without_a_practice_creates_one(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->practice);

        Livewire::actingAs($user)->test('portal');

        $this->assertNotNull($user->fresh()->practice);
    }

    public function test_guest_cannot_call_save_profile_directly(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        Livewire::test('portal')->call('saveProfile');
    }

    public function test_guest_cannot_call_submit_intake_directly(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        Livewire::test('portal')->call('submitIntake');
    }

    public function test_osha_location_can_be_added_even_if_practice_was_missing_on_load(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test('portal');
        $practice = $user->fresh()->practice;

        Livewire::actingAs($user)->test('portal.osha-location-modal', ['practiceId' => $practice->id])
            ->dispatch('open-osha-modal')
            ->set('name', 'Main Office')
            ->call('save');

        $this->assertDatabaseHas('osha_locations', [
            'practice_id' => $practice->id,
            'name' => 'Main Office',
        ]);
    }

    // ── Step 1: Payment ─────────────────────────────────────────────────────

    public function test_guest_paying_creates_account_practice_and_order(): void
    {
        Mail::fake();

        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('accountName', 'Jane Provider')
            ->set('accountEmail', 'jane@practice.com')
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertSee('Payment received');

        $user = User::where('email', 'jane@practice.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->practice);
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid->value,
        ]);
    }

    public function test_guest_paying_emails_the_generated_password_and_it_works_for_login(): void
    {
        Mail::fake();

        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('accountName', 'Jane Provider')
            ->set('accountEmail', 'jane@practice.com')
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay');

        $capturedPassword = null;

        Mail::assertSent(WelcomeCredentialsMail::class, function ($mail) use (&$capturedPassword) {
            $capturedPassword = $mail->password;

            return $mail->hasTo('jane@practice.com') && strlen($mail->password) >= 16;
        });

        $user = User::where('email', 'jane@practice.com')->first();

        Livewire::test('auth.login-form')
            ->set('email', $user->email)
            ->set('password', $capturedPassword)
            ->call('login')
            ->assertRedirect(route('portal'));
    }

    public function test_guest_pay_requires_account_fields(): void
    {
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertHasErrors(['accountName', 'accountEmail']);
    }

    public function test_guest_pay_rejects_duplicate_email(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'taken@example.com']);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('accountName', 'Jane Provider')
            ->set('accountEmail', 'taken@example.com')
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertHasErrors(['accountEmail']);

        Mail::assertNothingSent();
    }

    public function test_authenticated_user_paying_does_not_require_account_fields(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertHasNoErrors();
    }

    public function test_paying_creates_order_and_shows_confirmation_on_step_1(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'slug' => 'essential',
            'annual_price' => 999,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertSet('step', 1)
            ->assertSee('Payment received');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'event_type' => 'order.paid',
        ]);
    }

    public function test_paying_notifies_every_admin_by_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherAdmin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay');

        Mail::assertSent(AdminPaymentReceivedMail::class, fn ($mail) => $mail->hasTo($admin->email));
        Mail::assertSent(AdminPaymentReceivedMail::class, fn ($mail) => $mail->hasTo($otherAdmin->email));
        Mail::assertNotSent(AdminPaymentReceivedMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_continuing_after_payment_advances_to_step_2(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->assertSet('step', 1)
            ->call('goToStep', 2)
            ->assertSet('step', 2);
    }

    public function test_registering_with_a_package_preselects_it_on_the_payment_step(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        $this->withoutVite()->actingAs($user)->get('/portal?package=essential')
            ->assertOk()
            ->assertSee('Payment Details');
    }

    public function test_falls_back_to_session_intended_package_when_no_query_string(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        session(['intended_package' => 'essential']);

        $this->withoutVite()->actingAs($user)->get('/portal')
            ->assertOk()
            ->assertSee('Payment Details');

        $this->assertNull(session('intended_package'));
    }

    public function test_selecting_a_new_package_after_an_existing_purchase_starts_a_fresh_order(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $this->makeApprovedOrder($user);
        Package::factory()->create(['slug' => 'advanced', 'annual_price' => 2499, 'is_active' => true]);

        $this->withoutVite()->actingAs($user)->get('/portal?package=advanced')
            ->assertOk()
            ->assertSee('Payment Details')
            ->assertDontSee('Your Dashboard');
    }

    public function test_selecting_an_already_purchased_package_returns_to_its_existing_order(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $this->makeApprovedOrder($user);

        $this->withoutVite()->actingAs($user)->get('/portal?package=essential')
            ->assertOk()
            ->assertSee('Your Dashboard');
    }

    public function test_pay_requires_package_and_card_fields(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('portal')
            ->call('pay')
            ->assertHasErrors(['selectedPackageId', 'cardName', 'cardNumber', 'cardExpiry', 'cardCvc']);
    }

    public function test_card_fields_validate_live_without_calling_pay(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardNumber', '123')
            ->assertHasErrors(['cardNumber'])
            ->set('cardNumber', '4242424242424242')
            ->assertHasNoErrors(['cardNumber'])
            ->set('cardExpiry', '13/20')
            ->assertHasErrors(['cardExpiry']);
    }

    public function test_pay_rejects_a_card_number_with_the_wrong_digit_count(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertHasErrors(['cardNumber']);
    }

    public function test_pay_accepts_a_spaced_out_card_number(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertHasNoErrors(['cardNumber']);
    }

    public function test_pay_rejects_an_expiry_month_outside_01_to_12(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '13/27')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertHasErrors(['cardExpiry']);
    }

    public function test_pay_rejects_an_expired_card(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '01/20')
            ->set('cardCvc', '123')
            ->call('pay')
            ->assertHasErrors(['cardExpiry']);
    }

    public function test_pay_rejects_a_cvc_that_is_too_short(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('cardName', 'Jane Provider')
            ->set('cardNumber', '4242 4242 4242 4242')
            ->set('cardExpiry', '12/27')
            ->set('cardCvc', '12')
            ->call('pay')
            ->assertHasErrors(['cardCvc']);
    }

    // ── Step 2: Practice Profile ────────────────────────────────────────────

    public function test_back_button_returns_to_step_1(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id, 'is_profile_locked' => false]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->call('goToStep', 2)
            ->call('goToStep', 1)
            ->assertSet('step', 1);
    }

    public function test_saving_profile_locks_practice_and_advances_to_step_3(): void
    {
        $user = User::factory()->create();
        $practice = Practice::factory()->create(['user_id' => $user->id, 'is_profile_locked' => false]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('practiceName', 'Sunrise Family Medicine')
            ->set('logoFile', UploadedFile::fake()->image('logo.png'))
            ->set('billableProviders', 3)
            ->call('saveProfile')
            ->assertSet('step', 3);

        $this->assertDatabaseHas('practices', [
            'id' => $practice->id,
            'name' => 'Sunrise Family Medicine',
            'is_profile_locked' => true,
        ]);
    }

    public function test_saving_profile_with_a_logo_stores_it_on_the_practice(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id, 'is_profile_locked' => false]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        $logo = UploadedFile::fake()->image('logo.png');

        Livewire::actingAs($user)
            ->test('portal')
            ->set('practiceName', 'Sunrise Family Medicine')
            ->set('logoFile', $logo)
            ->call('saveProfile')
            ->assertSet('step', 3);

        $practice = $user->fresh()->practice;
        $this->assertNotNull($practice->logo_path);
        Storage::disk('public')->assertExists($practice->logo_path);
    }

    public function test_save_profile_requires_practice_name(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('practiceName', '')
            ->call('saveProfile')
            ->assertHasErrors(['practiceName']);
    }

    public function test_save_profile_requires_logo_address_npi_and_specialty_on_first_submission(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id, 'is_profile_locked' => false]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('practiceName', 'Sunrise Family Medicine')
            ->set('practiceAddress', '')
            ->set('npiNumber', '')
            ->set('specialty', '')
            ->call('saveProfile')
            ->assertHasErrors(['logoFile', 'practiceAddress', 'npiNumber', 'specialty']);
    }

    public function test_save_profile_does_not_require_a_new_logo_once_profile_is_locked(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $this->makeApprovedOrder($user);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->call('editProfile')
            ->call('saveProfile')
            ->assertHasNoErrors(['logoFile']);
    }

    public function test_save_profile_validates_npi_number_and_specialty_length(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('practiceName', 'Sunrise Family Medicine')
            ->set('npiNumber', '123456789012345')
            ->set('specialty', str_repeat('x', 101))
            ->call('saveProfile')
            ->assertHasErrors(['npiNumber', 'specialty']);
    }

    public function test_save_profile_rejects_non_digit_npi_number(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('practiceName', 'Sunrise Family Medicine')
            ->set('npiNumber', 'sdfsdfsdfsdf')
            ->call('saveProfile')
            ->assertHasErrors(['npiNumber']);
    }

    public function test_save_profile_accepts_a_valid_ten_digit_npi_number(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('practiceName', 'Sunrise Family Medicine')
            ->set('npiNumber', '1234567890')
            ->call('saveProfile')
            ->assertHasNoErrors(['npiNumber']);
    }

    // ── Step 2: Questionnaire downloads ─────────────────────────────────────

    public function test_every_tier_sees_all_four_questionnaires_in_step2(): void
    {
        foreach (['essential', 'professional', 'advanced', 'complete'] as $slug) {
            $user = User::factory()->create();
            Practice::factory()->create(['user_id' => $user->id]);
            $package = Package::factory()->create([
                'slug' => $slug,
                'annual_price' => $slug === 'complete' ? null : 999,
                'billing_type' => $slug === 'complete' ? 'custom' : 'annual',
                'is_active' => true,
            ]);
            Order::factory()->create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_status' => PaymentStatus::SimulatedPaid,
                'status' => OrderStatus::Paid,
            ]);

            Livewire::actingAs($user)
                ->test('portal')
                ->call('goToStep', 2)
                ->assertSee('Compliance & Ethics Questionnaire')
                ->assertSee('HIPAA Business Associate Questionnaire')
                ->assertSee('HIPAA Privacy Questionnaire')
                ->assertSee('HIPAA Security Questionnaire');
        }
    }

    // ── Step 2: OSHA Modal ──────────────────────────────────────────────────

    public function test_osha_modal_opens_and_creates_location(): void
    {
        $user = User::factory()->create();
        $practice = Practice::factory()->create(['user_id' => $user->id]);

        $component = Livewire::actingAs($user)->test('portal.osha-location-modal', [
            'practiceId' => $practice->id,
        ]);

        $component->dispatch('open-osha-modal')
            ->assertSet('open', true);

        $component->set('name', 'Main Office')
            ->set('address', '123 Elm St')
            ->call('save')
            ->assertSet('open', false);

        $this->assertDatabaseHas('osha_locations', [
            'practice_id' => $practice->id,
            'name' => 'Main Office',
            'address' => '123 Elm St',
        ]);
    }

    // ── Step 3: Intake Upload ───────────────────────────────────────────────

    public function test_submitting_intake_creates_submission_and_upload(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]]]),
        ]);

        $user = User::factory()->create();
        $practice = Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        $file = UploadedFile::fake()->create('intake.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test('portal')
            ->set('questionnaireFiles.compliance_ethics_questionnaire', $file)
            ->call('submitIntake')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('intake_submissions', [
            'order_id' => $order->id,
            'status' => IntakeSubmissionStatus::Submitted->value,
        ]);

        $this->assertDatabaseHas('intake_uploads', [
            'original_filename' => 'intake.pdf',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'event_type' => 'submission.submitted',
        ]);
    }

    public function test_submitting_intake_notifies_every_admin_by_email(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]]]),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        $file = UploadedFile::fake()->create('intake.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test('portal')
            ->set('questionnaireFiles.compliance_ethics_questionnaire', $file)
            ->call('submitIntake');

        Mail::assertSent(AdminIntakeSubmittedMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_revisiting_step3_after_submission_shows_existing_upload_and_does_not_duplicate_it(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]]]),
        ]);

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        $file = UploadedFile::fake()->create('intake.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test('portal')
            ->set('questionnaireFiles.compliance_ethics_questionnaire', $file)
            ->call('submitIntake')
            ->assertSet('step', 4);

        $this->assertDatabaseCount('intake_uploads', 1);

        // Simulate the user navigating back to Step 3 on a fresh page load.
        $component = Livewire::actingAs($user)->test('portal')->call('goToStep', 3);
        $component->assertSee('Already uploaded: intake.pdf');

        // Resubmitting without choosing a new file must not create a second row.
        $component->call('submitIntake')->assertHasNoErrors();
        $this->assertDatabaseCount('intake_uploads', 1);

        // Resubmitting with a replacement file updates the existing row instead of adding a new one.
        $replacement = UploadedFile::fake()->create('intake-v2.pdf', 100, 'application/pdf');
        $component->set('questionnaireFiles.compliance_ethics_questionnaire', $replacement)
            ->call('submitIntake')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('intake_uploads', 1);
        $this->assertDatabaseHas('intake_uploads', ['original_filename' => 'intake-v2.pdf']);
    }

    public function test_step3_shows_an_upload_box_for_every_questionnaire_shown_in_step2(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->call('goToStep', 3)
            ->assertSee('Compliance & Ethics Questionnaire')
            ->assertSee('HIPAA Business Associate Questionnaire')
            ->assertSee('HIPAA Privacy Questionnaire')
            ->assertSee('HIPAA Security Questionnaire');
    }

    public function test_submitting_intake_stores_an_optional_questionnaire_upload(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]]]),
        ]);

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        $requiredFile = UploadedFile::fake()->create('compliance.pdf', 100, 'application/pdf');
        $optionalFile = UploadedFile::fake()->create('security.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test('portal')
            ->set('questionnaireFiles.compliance_ethics_questionnaire', $requiredFile)
            ->set('questionnaireFiles.hipaa_security_questionnaire', $optionalFile)
            ->call('submitIntake')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('intake_uploads', [
            'original_filename' => 'compliance.pdf',
            'upload_type' => 'compliance_ethics_questionnaire',
        ]);
        $this->assertDatabaseHas('intake_uploads', [
            'original_filename' => 'security.pdf',
            'upload_type' => 'hipaa_security_questionnaire',
        ]);
    }

    public function test_submit_intake_requires_a_file(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        Livewire::actingAs($user)
            ->test('portal')
            ->call('submitIntake')
            ->assertHasErrors(['questionnaireFiles.compliance_ethics_questionnaire']);
    }

    public function test_every_downloaded_questionnaire_becomes_mandatory_to_upload_and_stale_errors_clear_after_fixing(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]]]),
        ]);

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        $complianceFile = UploadedFile::fake()->create('compliance.pdf', 100, 'application/pdf');

        $component = Livewire::actingAs($user)
            ->test('portal')
            ->set('downloadedQuestionnaireKeys', ['compliance_ethics_questionnaire', 'hipaa_business_associate_questionnaire'])
            ->set('questionnaireFiles.compliance_ethics_questionnaire', $complianceFile)
            ->call('submitIntake');

        // The optional HIPAA Business Associate questionnaire was downloaded, so it's now
        // mandatory too — even though only Compliance & Ethics is required by default.
        $component->assertHasErrors(['questionnaireFiles.hipaa_business_associate_questionnaire'])
            ->assertSet('step', 3);

        // Uploading the missing file and resubmitting must clear the stale error, not just
        // leave it stuck on screen from the previous failed attempt.
        $hipaaFile = UploadedFile::fake()->create('hipaa-ba.pdf', 100, 'application/pdf');

        $component->set('questionnaireFiles.hipaa_business_associate_questionnaire', $hipaaFile)
            ->call('submitIntake')
            ->assertHasNoErrors()
            ->assertSet('step', 4);

        $this->assertDatabaseHas('intake_uploads', ['original_filename' => 'compliance.pdf']);
        $this->assertDatabaseHas('intake_uploads', ['original_filename' => 'hipaa-ba.pdf']);
    }

    public function test_submitting_intake_once_creates_a_submission_for_every_order_in_the_batch(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"practice_name":"Test Practice"}']]],
            ]),
        ]);

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $essential = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $professional = Package::factory()->create(['slug' => 'professional', 'annual_price' => 1299, 'is_active' => true]);

        $batchId = (string) Str::ulid();
        $orderA = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $essential->id,
            'checkout_batch_id' => $batchId,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);
        $orderB = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $professional->id,
            'checkout_batch_id' => $batchId,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);

        $file = UploadedFile::fake()->create('intake.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test('portal')
            ->set('questionnaireFiles.compliance_ethics_questionnaire', $file)
            ->call('submitIntake')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('intake_submissions', ['order_id' => $orderA->id, 'status' => IntakeSubmissionStatus::Submitted->value]);
        $this->assertDatabaseHas('intake_submissions', ['order_id' => $orderB->id, 'status' => IntakeSubmissionStatus::Submitted->value]);

        $uploads = IntakeUpload::all();
        $this->assertCount(2, $uploads);

        // Every upload in the batch — not just the primary one — should end up completed
        // with the same extracted data...
        $this->assertTrue($uploads->every(fn ($u) => $u->ai_extraction_status === AiExtractionStatus::Completed));
        $this->assertSame(1, $uploads->pluck('ai_extracted_data')->map(fn ($d) => json_encode($d))->unique()->count());

        // ...but only ONE OpenAI API call was made for the shared document.
        Http::assertSentCount(1);
    }

    // ── Step 4: Review Status ───────────────────────────────────────────────

    public function test_check_approval_advances_to_step_5_when_approved(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);
        IntakeSubmission::factory()->approved()->create(['order_id' => $order->id]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 4)
            ->call('checkApproval')
            ->assertSet('step', 5);
    }

    public function test_step_4_shows_every_order_in_the_batch_and_waits_for_all_to_be_approved(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $essential = Package::factory()->create(['slug' => 'essential', 'name' => 'Essential Compliance', 'annual_price' => 999, 'is_active' => true]);
        $professional = Package::factory()->create(['slug' => 'professional', 'name' => 'Professional Compliance', 'annual_price' => 1299, 'is_active' => true]);

        $batchId = (string) Str::ulid();
        $orderA = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $essential->id,
            'checkout_batch_id' => $batchId,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);
        $orderB = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $professional->id,
            'checkout_batch_id' => $batchId,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);
        IntakeSubmission::factory()->approved()->create(['order_id' => $orderA->id]);
        IntakeSubmission::factory()->create(['order_id' => $orderB->id, 'status' => IntakeSubmissionStatus::UnderReview]);

        $component = Livewire::actingAs($user)
            ->test('portal')
            ->set('orderIds', [$orderA->id, $orderB->id])
            ->set('step', 4)
            ->assertSee('Essential Compliance')
            ->assertSee('Professional Compliance')
            ->assertSee('Under review');

        // Not every order is approved yet — stays on step 4.
        $component->call('checkApproval')->assertSet('step', 4);

        IntakeSubmission::where('order_id', $orderB->id)->update(['status' => IntakeSubmissionStatus::Approved]);

        $component->call('checkApproval')->assertSet('step', 5);
    }

    // ── Step 5: Dashboard ───────────────────────────────────────────────────

    private function makeApprovedOrder(User $user): Order
    {
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Approved,
        ]);
        IntakeSubmission::factory()->approved()->create(['order_id' => $order->id]);

        return $order;
    }

    public function test_dashboard_shows_practice_info_bar_and_defaults_to_documents_tab(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id, 'name' => 'Sunrise Family Medicine']);
        $this->makeApprovedOrder($user);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->assertSee('Sunrise Family Medicine')
            ->assertSee('Update Practice Info')
            ->assertSee('Employee Handbook (Basic)')
            ->assertSee('Generating');
    }

    public function test_documents_tab_shows_a_contact_us_link(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $this->makeApprovedOrder($user);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->assertSee('For any queries')
            ->assertSee(route('contact'), false);
    }

    public function test_dashboard_can_switch_between_tabs(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $this->makeApprovedOrder($user);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->set('dashboardTab', 'payments')
            ->assertSee('Purchase History')
            ->set('dashboardTab', 'history')
            ->assertSee('Account Activity');
    }

    public function test_update_practice_info_button_enters_edit_mode_and_returns_to_dashboard(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id, 'address' => 'old address']);
        $this->makeApprovedOrder($user);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->call('editProfile')
            ->assertSet('step', 2)
            ->assertSet('editingProfile', true)
            ->set('practiceAddress', 'new address')
            ->call('saveProfile')
            ->assertSet('step', 5)
            ->assertSet('editingProfile', false);

        $this->assertDatabaseHas('practices', ['user_id' => $user->id, 'address' => 'new address']);
    }

    public function test_regenerating_a_stale_document_dispatches_job_and_logs_activity(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $order = $this->makeApprovedOrder($user);
        $document = GeneratedDocument::factory()->completed()->stale()->create(['order_id' => $order->id]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->call('regenerateDocument', $document->id);

        Bus::assertDispatched(GenerateComplianceDocument::class);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'document.regenerate_requested']);
    }

    public function test_cannot_regenerate_another_users_document(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $this->makeApprovedOrder($user);

        $otherPackage = Package::factory()->create(['slug' => 'professional']);
        $otherOrder = Order::factory()->create(['package_id' => $otherPackage->id]);
        $otherDocument = GeneratedDocument::factory()->completed()->create(['order_id' => $otherOrder->id]);

        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->call('regenerateDocument', $otherDocument->id);
    }

    public function test_dashboard_can_switch_between_multiple_purchased_orders_documents(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $orderA = $this->makeApprovedOrder($user);

        $professional = Package::factory()->create(['slug' => 'professional', 'name' => 'Professional Compliance', 'annual_price' => 1299, 'is_active' => true]);
        $orderB = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $professional->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Approved,
        ]);
        IntakeSubmission::factory()->approved()->create(['order_id' => $orderB->id]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->assertSee('Professional Compliance')
            ->call('switchOrder', $orderB->id)
            ->assertSet('dashboardOrderId', $orderB->id);
    }

    public function test_cannot_switch_to_another_users_order(): void
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $this->makeApprovedOrder($user);

        $otherPackage = Package::factory()->create(['slug' => 'advanced']);
        $otherOrder = Order::factory()->create(['package_id' => $otherPackage->id]);

        $this->withoutExceptionHandling();
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('step', 5)
            ->call('switchOrder', $otherOrder->id);
    }

    // ── Step navigation ─────────────────────────────────────────────────────

    public function test_cannot_navigate_to_unreachable_step(): void
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test('portal')
            ->assertSet('step', 1)
            ->call('goToStep', 3)
            ->assertSet('step', 1); // step 3 not reachable without payment + profile
    }
}
