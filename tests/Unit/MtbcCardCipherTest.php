<?php

namespace Tests\Unit;

use App\Services\MtbcCardCipher;
use Tests\TestCase;

class MtbcCardCipherTest extends TestCase
{
    public function test_encrypt_then_decrypt_round_trips_to_the_original_value(): void
    {
        config(['services.empower_payment_api.aes_key' => '2595874569321569']);

        $cipher = new MtbcCardCipher;

        $encrypted = $cipher->encrypt('4111111111111111');

        $this->assertNotSame('4111111111111111', $encrypted);
        $this->assertSame('4111111111111111', $cipher->decrypt($encrypted));
    }

    /**
     * Proves the exact documented algorithm (AES-128-CBC, PKCS7, static all-zero IV, raw key
     * bytes) — not just that encrypt()/decrypt() are internally self-consistent with each other.
     */
    public function test_encryption_matches_the_documented_algorithm_independently(): void
    {
        config(['services.empower_payment_api.aes_key' => '2595874569321569']);

        $cipher = new MtbcCardCipher;

        $expected = base64_encode(openssl_encrypt(
            '4111111111111111',
            'aes-128-cbc',
            '2595874569321569',
            OPENSSL_RAW_DATA,
            str_repeat("\0", 16),
        ));

        $this->assertSame($expected, $cipher->encrypt('4111111111111111'));
    }

    public function test_throws_when_the_configured_key_is_not_sixteen_bytes(): void
    {
        config(['services.empower_payment_api.aes_key' => 'too-short']);

        $this->expectException(\RuntimeException::class);

        (new MtbcCardCipher)->encrypt('4111111111111111');
    }
}
