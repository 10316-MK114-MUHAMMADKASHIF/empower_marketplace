<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case SimulatedPaid = 'simulated_paid';
    case Paid = 'paid';
}
