<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\LeadConfirmationMail;
use App\Mail\NewLeadNotificationMail;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders(): void
    {
        $this->withoutVite()
            ->get(route('contact'))
            ->assertOk()
            ->assertSee('Request a Quote');
    }

    public function test_submitting_valid_form_creates_lead(): void
    {
        Livewire::test('contact-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('phone', '5551234567')
            ->set('message', 'Looking for compliance help for our practice.')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('leads', ['email' => 'jane@practice.com']);
    }

    public function test_submitting_form_without_required_fields_fails(): void
    {
        Livewire::test('contact-form')
            ->call('submit')
            ->assertHasErrors(['name', 'email', 'phone', 'message']);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_submitting_invalid_email_fails(): void
    {
        Livewire::test('contact-form')
            ->set('name', 'Jane')
            ->set('email', 'not-an-email')
            ->set('phone', '5551234567')
            ->set('message', 'Hello.')
            ->call('submit')
            ->assertHasErrors(['email']);
    }

    public function test_package_interest_stored_from_query_string(): void
    {
        Livewire::withQueryParams(['package' => 'advanced'])
            ->test('contact-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('phone', '5551234567')
            ->set('message', 'I need the advanced package.')
            ->call('submit');

        $this->assertDatabaseHas('leads', [
            'email' => 'jane@practice.com',
            'package_interest' => 'advanced',
        ]);
    }

    public function test_submitting_form_sends_a_confirmation_email_to_the_requestor(): void
    {
        Mail::fake();

        Livewire::test('contact-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('phone', '5551234567')
            ->set('message', 'Looking for compliance help for our practice.')
            ->call('submit');

        $lead = Lead::where('email', 'jane@practice.com')->first();

        Mail::assertSent(LeadConfirmationMail::class, function ($mail) use ($lead) {
            return $mail->hasTo('jane@practice.com') && $mail->lead->is($lead);
        });
    }

    public function test_submitting_form_notifies_every_admin_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin, 'email' => 'admin@empower.test']);
        $otherAdmin = User::factory()->create(['role' => UserRole::Admin, 'email' => 'admin2@empower.test']);
        User::factory()->create(['role' => UserRole::Client, 'email' => 'client@empower.test']);

        Livewire::test('contact-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('phone', '5551234567')
            ->set('message', 'Looking for compliance help for our practice.')
            ->call('submit');

        Mail::assertSent(NewLeadNotificationMail::class, fn ($mail) => $mail->hasTo($admin->email));
        Mail::assertSent(NewLeadNotificationMail::class, fn ($mail) => $mail->hasTo($otherAdmin->email));
        Mail::assertNotSent(NewLeadNotificationMail::class, fn ($mail) => $mail->hasTo('client@empower.test'));
    }

    public function test_submitting_form_does_not_error_when_no_admin_exists(): void
    {
        Mail::fake();

        Livewire::test('contact-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('phone', '5551234567')
            ->set('message', 'Looking for compliance help for our practice.')
            ->call('submit')
            ->assertSet('submitted', true);

        Mail::assertNotSent(NewLeadNotificationMail::class);
    }
}
