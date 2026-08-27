<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps MTBC's Clover charge gateway (a direct card-charge API — no client-side tokenization
 * step). Card data reaches this service only for the single request that charges it; nothing
 * about the card is retained here or returned back to the caller beyond a transaction id.
 */
class CloverChargeService
{
    /**
     * @param  array{name: string, address1: string, city: string, state: string, zip: string, product_Name: string, amount: float, cardNumber: string, expMonth: int, expYear: int, cvv: string}  $params
     */
    public function charge(array $params): ChargeResult
    {
        $baseUrl = config('services.clover_mtbc.base_url');

        if (! $baseUrl) {
            return new ChargeResult(success: false, declineMessage: 'Payment processing is not configured.');
        }

        try {
            $response = Http::asJson()->timeout(30)->post($baseUrl, [
                'username' => config('services.clover_mtbc.username'),
                'password' => config('services.clover_mtbc.password'),
                'business_Name' => config('services.clover_mtbc.business_name'),
                ...$params,
            ]);
        } catch (\Throwable $e) {
            Log::error('Clover charge request failed to send', ['error' => $e->getMessage()]);

            return new ChargeResult(success: false, declineMessage: 'Could not reach the payment processor. Please try again.');
        }

        $json = $response->json();

        if ($response->successful() && ($json['status'] ?? false) === true) {
            return new ChargeResult(success: true, transactionId: $json['data']['id'] ?? null);
        }

        Log::warning('Clover charge declined or failed', [
            'http_status' => $response->status(),
            'body' => $json ?? $response->body(),
        ]);

        return new ChargeResult(success: false, declineMessage: $this->extractErrorMessage($json));
    }

    /** @param  array<string, mixed>|null  $json */
    private function extractErrorMessage(?array $json): string
    {
        if ($json === null) {
            return 'The payment could not be processed. Please try again.';
        }

        // The framework's own model-validation shape (missing/malformed fields):
        // {"errors": {"CardNumber": ["The CardNumber field is required."]}}
        if (isset($json['errors']) && is_array($json['errors'])) {
            $first = collect($json['errors'])->flatten()->first();

            return is_string($first) ? $first : 'Please check your payment details and try again.';
        }

        // The API's own business-error shape: {"status": false, "message": "..."}. "message" is
        // sometimes itself a JSON-encoded string carrying the real processor-level error.
        $message = $json['message'] ?? null;

        if (is_string($message)) {
            $decoded = json_decode($message, true);

            if (is_array($decoded)) {
                return $decoded['error']['message'] ?? $decoded['message'] ?? $message;
            }

            return $message;
        }

        return 'The payment could not be processed. Please try again.';
    }
}
