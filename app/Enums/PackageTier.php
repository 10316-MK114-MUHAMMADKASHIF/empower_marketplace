<?php

namespace App\Enums;

enum PackageTier: string
{
    case Essential = 'essential';
    case Professional = 'professional';
    case Advanced = 'advanced';
    case Complete = 'complete';

    public function label(): string
    {
        return match ($this) {
            self::Essential => 'Essential Compliance',
            self::Professional => 'Professional Compliance',
            self::Advanced => 'Advanced Compliance',
            self::Complete => 'Complete Compliance',
        };
    }

    /** Whether this tier requires a custom quote (no self-serve payment) */
    public function isCustomQuote(): bool
    {
        return $this === self::Complete;
    }
}
