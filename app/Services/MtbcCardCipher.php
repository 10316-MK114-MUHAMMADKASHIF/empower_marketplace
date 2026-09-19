<?php

namespace App\Services;

/**
 * AES-128-CBC encrypt/decrypt for card data sent to and received from MTBC's Empower Payment API
 * tokenize/detokenize endpoints. The scheme is dictated entirely by MTBC's own (undocumented, only
 * available as a C# reference) implementation — a static all-zero IV and a key used as its raw
 * bytes with no derivation — neither of which is a security choice we control or would otherwise
 * make ourselves.
 *
 * Verified compatible via a live round trip against MTBC's UAT sandbox: encrypting a card number
 * with this exact scheme, tokenizing it, detokenizing the resulting token, and decrypting the
 * response reproduces the original value.
 */
class MtbcCardCipher
{
    private const CIPHER = 'aes-128-cbc';

    /** MTBC's own reference implementation generates a random IV, then immediately overwrites it
     *  with 16 zero bytes before use — effectively a static, non-random IV. */
    private const IV = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    public function encrypt(string $plaintext): string
    {
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, self::IV);

        if ($ciphertext === false) {
            throw new \RuntimeException('Failed to encrypt card data.');
        }

        return base64_encode($ciphertext);
    }

    public function decrypt(string $base64Ciphertext): string
    {
        $raw = base64_decode($base64Ciphertext, true);

        if ($raw === false) {
            throw new \RuntimeException('Failed to base64-decode card data.');
        }

        $plaintext = openssl_decrypt($raw, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, self::IV);

        if ($plaintext === false) {
            throw new \RuntimeException('Failed to decrypt card data.');
        }

        return $plaintext;
    }

    private function key(): string
    {
        $key = config('services.empower_payment_api.aes_key');

        if (! is_string($key) || strlen($key) !== 16) {
            throw new \RuntimeException('services.empower_payment_api.aes_key must be a 16-byte AES-128 key.');
        }

        return $key;
    }
}
