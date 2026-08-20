<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
