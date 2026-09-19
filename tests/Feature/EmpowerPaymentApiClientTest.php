<?php

namespace Tests\Feature;

use App\Exceptions\EmpowerPaymentApiException;
use App\Services\EmpowerPaymentApiClient;
use App\Services\MtbcCardCipher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EmpowerPaymentApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.empower_payment_api.aes_key' => '2595874569321569']);
    }

    private function fakeAuthToken(): void
    {
        Http::fake([
            '*/api/auth/token' => Http::response([
                'status' => true,
                'message' => 'Token generated successfully',
                'data' => ['accessToken' => 'fake-jwt-token', 'tokenType' => 'Bearer', 'expiresAt' => now()->addMinutes(10)->toIso8601String()],
            ]),
        ]);
    }

    /**
     * The auth/token fake entry, merged into every Http::fake() call in this file. Without this,
     * an un-faked call to /api/auth/token isn't stubbed at all and Http::fake() silently lets it
     * through as a REAL network request to MTBC's live UAT sandbox — which happened to "work" in
     * practice (the sandbox is genuinely reachable) but is exactly the kind of hidden live-network
     * dependency a test suite must never have: it's slow, it eats MTBC's 10-req/min rate limit on
     * every test run, and it would fail unpredictably the moment the sandbox is unreachable.
     *
     * @return array<string, Response>
     */
    private function authTokenFake(): array
    {
        return [
            '*/api/auth/token' => Http::response(['status' => true, 'data' => ['accessToken' => 'fake-jwt-token']]),
        ];
    }

    public function test_tokenize_sends_aes_encrypted_card_fields_not_plaintext(): void
    {
        Http::fake([
            '*/api/auth/token' => Http::response([
                'status' => true,
                'data' => ['accessToken' => 'fake-jwt-token'],
            ]),
            '*/api/payment/tokenize' => Http::response([
                'status' => true,
                'message' => 'Card tokenized successfully',
                'data' => ['token' => '4111114281501111', 'firstSix' => '411111', 'lastFour' => '1111', 'referenceNumber' => 'REF123'],
            ]),
        ]);

        $result = app(EmpowerPaymentApiClient::class)->tokenize('4111111111111111', '123');

        $this->assertSame('4111114281501111', $result['token']);
        $this->assertSame('411111', $result['firstSix']);
        $this->assertSame('1111', $result['lastFour']);
        $this->assertSame('REF123', $result['referenceNumber']);

        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/api/payment/tokenize')) {
                return true;
            }

            return $request['cardNumber'] !== '4111111111111111'
                && $request['cvv'] !== '123'
                && $request->hasHeader('Authorization', 'Bearer fake-jwt-token');
        });
    }

    public function test_access_token_is_cached_across_multiple_calls(): void
    {
        Http::fake([
            '*/api/auth/token' => Http::response(['status' => true, 'data' => ['accessToken' => 'fake-jwt-token']]),
            '*/api/payment/tokenize' => Http::response([
                'status' => true,
                'data' => ['token' => '4111114281501111', 'firstSix' => '411111', 'lastFour' => '1111', 'referenceNumber' => 'REF123'],
            ]),
        ]);

        $client = app(EmpowerPaymentApiClient::class);
        $client->tokenize('4111111111111111', '123');
        $client->tokenize('4111111111111111', '123');

        Http::assertSentCount(3); // one auth/token + two tokenize
    }

    public function test_detokenize_decrypts_the_returned_card_and_cvv(): void
    {
        $this->fakeAuthToken();

        $cipher = new MtbcCardCipher;

        Http::fake([
            '*/api/auth/token' => Http::response(['status' => true, 'data' => ['accessToken' => 'fake-jwt-token']]),
            '*/api/payment/detokenize' => Http::response([
                'status' => true,
                'message' => 'Card detokenized successfully',
                'data' => [
                    'value' => $cipher->encrypt('4111111111111111'),
                    'cvv' => $cipher->encrypt('123'),
                    'referenceNumber' => 'REF123',
                ],
            ]),
        ]);

        $result = app(EmpowerPaymentApiClient::class)->detokenize('4111114281501111');

        $this->assertSame('4111111111111111', $result['cardNumber']);
        $this->assertSame('123', $result['cvv']);
        $this->assertSame('REF123', $result['referenceNumber']);
    }

    public function test_tokenize_throws_on_a_business_error_response(): void
    {
        Http::fake([
            '*/api/auth/token' => Http::response(['status' => true, 'data' => ['accessToken' => 'fake-jwt-token']]),
            '*/api/payment/tokenize' => Http::response(['status' => false, 'message' => 'Invalid card number', 'data' => null]),
        ]);

        $this->expectException(EmpowerPaymentApiException::class);

        app(EmpowerPaymentApiClient::class)->tokenize('bad-card', '123');
    }

    /** @return array{name: string, address1: string, city: string, state: string, zip: string, product_Name: string, amount: float, cardNumber: string, expMonth: int, expYear: int, cvv: string} */
    private function chargeParams(): array
    {
        return [
            'name' => 'Jane Provider',
            'address1' => '7 Clyde Road',
            'city' => 'Somerset',
            'state' => 'NJ',
            'zip' => '08873',
            'product_Name' => 'Essential Compliance',
            'amount' => 999.0,
            'cardNumber' => '4111111111111111',
            'expMonth' => 9,
            'expYear' => 2029,
            'cvv' => '123',
        ];
    }

    public function test_successful_charge_returns_the_transaction_id(): void
    {
        Http::fake([
            ...$this->authTokenFake(),
            '*/api/payment/Create_Charge' => Http::response([
                'status' => true,
                'message' => 'Payment Successful',
                'data' => ['id' => '59VT21YY0WR2M', 'amount' => 99900, 'paid' => true, 'status' => 'succeeded'],
            ]),
        ]);

        $result = app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        $this->assertTrue($result->success);
        $this->assertSame('59VT21YY0WR2M', $result->transactionId);
    }

    public function test_charge_sends_plaintext_card_fields_charge_credentials_and_a_bearer_token(): void
    {
        Http::fake([
            ...$this->authTokenFake(),
            '*/api/payment/Create_Charge' => Http::response(['status' => true, 'data' => ['id' => 'TXN1']]),
        ]);

        app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        // Confirmed live (2026-09-19): unlike the older Clover_Api gateway, Create_Charge on this
        // API rejects requests with no Authorization header — a bare 401 with an empty body,
        // never reaching the app's own JSON error responses. This assertion is the regression test
        // for that: it would have caught the original bug (charge() sent no bearer token at all).
        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/api/payment/Create_Charge')) {
                return true;
            }

            return $request['username'] === config('services.empower_payment_api.charge_username')
                && $request['password'] === config('services.empower_payment_api.charge_password')
                && $request['cardNumber'] === '4111111111111111'
                && $request['cvv'] === '123'
                && $request['amount'] === 999.0
                && $request->hasHeader('Authorization', 'Bearer fake-jwt-token');
        });
    }

    public function test_incorrect_cvc_response_is_treated_as_a_decline_not_an_exception(): void
    {
        Http::fake([
            ...$this->authTokenFake(),
            '*/api/payment/Create_Charge' => Http::response([
                'status' => false,
                'message' => '{"message":"400 Bad Request","error":{"type":"invalid_request_error","code":"incorrect_cvc","message":"Please provide valid cvv value."}}',
                'data' => null,
            ], 400),
        ]);

        $result = app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        $this->assertFalse($result->success);
        $this->assertSame('Please provide valid cvv value.', $result->declineMessage);
    }

    public function test_charge_clears_the_cached_token_and_retries_once_on_a_gateway_401(): void
    {
        Http::fakeSequence('*/api/auth/token')
            ->push(['status' => true, 'data' => ['accessToken' => 'stale-token']])
            ->push(['status' => true, 'data' => ['accessToken' => 'fresh-token']]);

        Http::fake([
            '*/api/payment/Create_Charge' => Http::sequence()
                ->push('', 401) // the exact failure mode observed live: empty body, bare 401
                ->push(['status' => true, 'data' => ['id' => 'TXN1']]),
        ]);

        $result = app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        $this->assertTrue($result->success);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/payment/Create_Charge')
            && $request->hasHeader('Authorization', 'Bearer fresh-token'));
    }

    public function test_network_failure_is_treated_as_a_decline_not_an_exception(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        $this->assertFalse($result->success);
        $this->assertNotNull($result->declineMessage);
    }

    public function test_missing_base_url_configuration_fails_gracefully(): void
    {
        Http::fake();
        config(['services.empower_payment_api.base_url' => null]);

        $result = app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        $this->assertFalse($result->success);
        Http::assertNothingSent();
    }

    public function test_declined_charge_is_logged_without_leaking_the_raw_card_number(): void
    {
        Log::spy();

        Http::fake([
            ...$this->authTokenFake(),
            '*/api/payment/Create_Charge' => Http::response(['status' => false, 'message' => 'Card declined', 'data' => null], 400),
        ]);

        app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context) => ! str_contains(json_encode($context), '4111111111111111')
                && ! str_contains(json_encode($context), '123')
        )->once();
    }

    public function test_successful_charge_is_logged_without_leaking_the_raw_card_number(): void
    {
        Log::spy();

        Http::fake([
            ...$this->authTokenFake(),
            '*/api/payment/Create_Charge' => Http::response([
                'status' => true,
                'message' => 'Payment Successful',
                'data' => ['id' => 'TXNABC999', 'paid' => true, 'status' => 'succeeded'],
            ]),
        ]);

        app(EmpowerPaymentApiClient::class)->charge($this->chargeParams());

        Log::shouldHaveReceived('info')->withArgs(
            fn (string $message, array $context) => $message === 'Empower Payment API charge succeeded'
                && $context['body']['data']['id'] === 'TXNABC999'
                && ! str_contains(json_encode($context), '4111111111111111')
                && ! str_contains(json_encode($context), '123')
        )->once();
    }
}
