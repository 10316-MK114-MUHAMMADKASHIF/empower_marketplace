<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Annual => 'Annual',
        };
    }

    public function period(): string
    {
        return match ($this) {
            self::Monthly => 'month',
            self::Annual => 'year',
        };
    }
}
