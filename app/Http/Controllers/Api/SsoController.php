<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\WelcomeCredentialsMail;
use App\Models\ActivityLog;
use App\Models\Practice;
use App\Models\SsoLoginToken;
use App\Models\SsoPartner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /** Generous machine-to-machine limit — this is a backstop against a leaked/misbehaving
     *  partner key, not a brute-force guard (the API key is the actual defense). */
    private const MAX_ATTEMPTS = 20;

    private const DECAY_SECONDS = 60;

    /**
     * Issues a one-time login token for the calling partner (resolved by VerifySsoApiKey and
     * attached to the request — never trusted from anything the caller states about itself).
     * Mirrors ⚡portal.blade.php's pay()/payFreeTrial() guest-checkout provisioning: create the
     * user + an empty Practice + send welcome credentials only the first time this email is seen.
     */
    public function issueToken(Request $request): JsonResponse
    {
        /** @var SsoPartner $partner */
        $partner = $request->attributes->get('sso_partner');

        $throttleKey = "sso-token:{$partner->id}:{$request->ip()}";

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json(['message' => "Too many requests. Please try again in {$seconds} seconds."], 429);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        $validated = $request->validate([
            'email' => 'required|email:rfc,filter|max:255',
            'name' => 'nullable|string|max:150',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            $generatedPassword = Str::password(16);

            $user = User::create([
                'name' => $validated['name'] ?? $validated['email'],
                'email' => $validated['email'],
                'password' => $generatedPassword,
                'role' => UserRole::Client,
                'is_active' => true,
            ]);

            Practice::create([
                'user_id' => $user->id,
                'name' => '',
            ]);

            try {
                Mail::to($user->email)->queue(new WelcomeCredentialsMail($user, $generatedPassword));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $rawToken = Str::random(64);

        SsoLoginToken::create([
            'user_id' => $user->id,
            'sso_partner_id' => $partner->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addSeconds(60),
            'requested_ip' => $request->ip(),
        ]);

        ActivityLog::record(
            'sso.token_issued',
            "SSO login token issued for {$user->email} via {$partner->name}.",
            user: $user,
        );

        return response()->json([
            'login_url' => route('sso.consume', ['token' => $rawToken]),
        ]);
    }
}
