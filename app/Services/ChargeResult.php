<?php

namespace App\Services;

/** Normalizes the Clover/MTBC charge API's several response shapes into one simple result. */
class ChargeResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId = null,
        public readonly ?string $declineMessage = null,
    ) {}
}
