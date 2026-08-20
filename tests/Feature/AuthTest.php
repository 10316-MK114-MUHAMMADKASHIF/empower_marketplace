<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // --- Login page ---

    public function test_login_page_renders(): void
    {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_login_with_valid_credentials_redirects_to_portal(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        Livewire::test('auth.login-form')
            ->set('email', $user->email)
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect(route('portal'));
    }

    public function test_login_with_admin_credentials_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['password' => 'secret123', 'role' => UserRole::Admin]);

        Livewire::test('auth.login-form')
            ->set('email', $admin->email)
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_with_wrong_password_shows_error(): void
    {
        $user = User::factory()->create();

        Livewire::test('auth.login-form')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        Livewire::test('auth.login-form')
            ->call('login')
            ->assertHasErrors(['email', 'password']);
    }

    // --- Register page ---

    public function test_register_page_renders(): void
    {
        $this->withoutVite()
            ->get(route('register'))
            ->assertOk()
            ->assertSee('Sign Up');
    }

    public function test_visiting_register_with_a_package_stores_it_in_session(): void
    {
        $this->withoutVite()->get(route('register', ['package' => 'essential']))->assertOk();

        $this->assertSame('essential', session('intended_package'));
    }

    public function test_registration_creates_user_and_practice_and_logs_in(): void
    {
        Livewire::test('auth.register-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'secret123')
            ->call('register')
            ->assertRedirect(route('portal'));

        $this->assertDatabaseHas('users', ['email' => 'jane@practice.com']);

        $user = User::where('email', 'jane@practice.com')->first();
        $this->assertNotNull($user->practice);
    }

    public function test_registration_with_package_redirects_to_portal_with_package(): void
    {
        Livewire::test('auth.register-form', ['package' => 'essential'])
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'secret123')
            ->call('register')
            ->assertRedirect(route('portal', ['package' => 'essential']));
    }

    public function test_registration_falls_back_to_session_intended_package(): void
    {
        session(['intended_package' => 'essential']);

        Livewire::test('auth.register-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'secret123')
            ->call('register')
            ->assertRedirect(route('portal', ['package' => 'essential']));
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test('auth.register-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'taken@example.com')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'secret123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_registration_fails_with_password_mismatch(): void
    {
        Livewire::test('auth.register-form')
            ->set('name', 'Jane Provider')
            ->set('email', 'jane@practice.com')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'different')
            ->call('register')
            ->assertHasErrors(['passwordConfirmation']);
    }

    public function test_registration_requires_all_fields(): void
    {
        Livewire::test('auth.register-form')
            ->call('register')
            ->assertHasErrors(['name', 'email', 'password', 'passwordConfirmation']);
    }

    // --- Logout ---

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->withoutVite()
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_guest_can_access_portal_to_sign_up_and_pay(): void
    {
        $this->withoutVite()
            ->get(route('portal'))
            ->assertOk();
    }
}
