<?php

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case FreeTrial = 'free_trial';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage Discount',
            self::FreeTrial => 'Free Trial',
        };
    }
}
