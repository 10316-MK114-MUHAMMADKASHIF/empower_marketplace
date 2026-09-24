<?php

namespace Tests\Feature;

use App\Mail\WelcomeCredentialsMail;
use App\Models\Practice;
use App\Models\SsoLoginToken;
use App\Models\SsoPartner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SsoTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: SsoPartner, 1: string} */
    private function createPartner(string $name = 'talkehr'): array
    {
        $rawKey = Str::random(64);

        $partner = SsoPartner::create([
            'name' => $name,
            'api_key_hash' => hash('sha256', $rawKey),
            'is_active' => true,
        ]);

        return [$partner, $rawKey];
    }

    // ── Token issuance ──────────────────────────────────────────────────────

    public function test_valid_key_and_new_email_creates_a_user_practice_and_token(): void
    {
        Mail::fake();
        [$partner, $rawKey] = $this->createPartner();

        $response = $this->postJson('/api/sso/token', [
            'email' => 'new-client@example.com',
            'name' => 'Jane Provider',
        ], ['X-Sso-Api-Key' => $rawKey]);

        $response->assertOk()->assertJsonStructure(['login_url']);

        $user = User::where('email', 'new-client@example.com')->firstOrFail();
        $this->assertSame('Jane Provider', $user->name);
        $this->assertDatabaseHas('practices', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sso_login_tokens', [
            'user_id' => $user->id,
            'sso_partner_id' => $partner->id,
        ]);
        Mail::assertQueued(WelcomeCredentialsMail::class);
    }

    public function test_valid_key_and_existing_email_reuses_the_account(): void
    {
        Mail::fake();
        [, $rawKey] = $this->createPartner();
        $existing = User::factory()->create(['email' => 'already-here@example.com']);
        Practice::factory()->create(['user_id' => $existing->id]);

        $response = $this->postJson('/api/sso/token', [
            'email' => 'already-here@example.com',
        ], ['X-Sso-Api-Key' => $rawKey]);

        $response->assertOk();
        $this->assertSame(1, User::where('email', 'already-here@example.com')->count());
        Mail::assertNotQueued(WelcomeCredentialsMail::class);
    }

    public function test_each_partners_tokens_are_tagged_with_their_own_partner_id(): void
    {
        [$partnerA, $keyA] = $this->createPartner('talkehr');
        [$partnerB, $keyB] = $this->createPartner('moodle');

        $this->postJson('/api/sso/token', ['email' => 'a@example.com'], ['X-Sso-Api-Key' => $keyA])->assertOk();
        $this->postJson('/api/sso/token', ['email' => 'b@example.com'], ['X-Sso-Api-Key' => $keyB])->assertOk();

        $userA = User::where('email', 'a@example.com')->firstOrFail();
        $userB = User::where('email', 'b@example.com')->firstOrFail();

        $this->assertDatabaseHas('sso_login_tokens', ['user_id' => $userA->id, 'sso_partner_id' => $partnerA->id]);
        $this->assertDatabaseHas('sso_login_tokens', ['user_id' => $userB->id, 'sso_partner_id' => $partnerB->id]);
    }

    public function test_missing_api_key_is_rejected(): void
    {
        $this->postJson('/api/sso/token', ['email' => 'someone@example.com'])
            ->assertStatus(401);
    }

    public function test_invalid_api_key_is_rejected(): void
    {
        $this->postJson('/api/sso/token', ['email' => 'someone@example.com'], ['X-Sso-Api-Key' => 'not-a-real-key'])
            ->assertStatus(401);
    }

    public function test_inactive_partner_key_is_rejected(): void
    {
        [$partner, $rawKey] = $this->createPartner();
        $partner->update(['is_active' => false]);

        $this->postJson('/api/sso/token', ['email' => 'someone@example.com'], ['X-Sso-Api-Key' => $rawKey])
            ->assertStatus(401);
    }

    public function test_missing_email_is_rejected(): void
    {
        [, $rawKey] = $this->createPartner();

        $this->postJson('/api/sso/token', [], ['X-Sso-Api-Key' => $rawKey])
            ->assertStatus(422);
    }

    public function test_repeated_issuance_attempts_are_eventually_rate_limited(): void
    {
        [, $rawKey] = $this->createPartner();

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/sso/token', ['email' => "user{$i}@example.com"], ['X-Sso-Api-Key' => $rawKey])
                ->assertOk();
        }

        $this->postJson('/api/sso/token', ['email' => 'one-too-many@example.com'], ['X-Sso-Api-Key' => $rawKey])
            ->assertStatus(429);
    }

    // ── Token consumption ───────────────────────────────────────────────────

    public function test_consuming_a_fresh_token_logs_the_user_in_and_redirects_to_the_portal(): void
    {
        [, $rawKey] = $this->createPartner();
        $issue = $this->postJson('/api/sso/token', ['email' => 'consume-me@example.com'], ['X-Sso-Api-Key' => $rawKey]);
        $loginUrl = $issue->json('login_url');

        $response = $this->get($loginUrl);

        $response->assertRedirect(route('portal'));
        $this->assertAuthenticatedAs(User::where('email', 'consume-me@example.com')->firstOrFail());
    }

    public function test_consuming_an_expired_token_fails_and_does_not_log_in(): void
    {
        [$partner] = $this->createPartner();
        $user = User::factory()->create();
        $rawToken = Str::random(64);

        SsoLoginToken::create([
            'user_id' => $user->id,
            'sso_partner_id' => $partner->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('sso.consume', ['token' => $rawToken]));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_consuming_an_already_used_token_fails_on_the_second_attempt(): void
    {
        [, $rawKey] = $this->createPartner();
        $issue = $this->postJson('/api/sso/token', ['email' => 'once-only@example.com'], ['X-Sso-Api-Key' => $rawKey]);
        $loginUrl = $issue->json('login_url');

        $this->get($loginUrl)->assertRedirect(route('portal'));

        // A fresh, unauthenticated "browser" replaying the same link a second time.
        $this->post('/logout');
        $this->get($loginUrl)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_consuming_an_unknown_token_fails(): void
    {
        $response = $this->get(route('sso.consume', ['token' => Str::random(64)]));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
