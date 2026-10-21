<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case SimulatedPaid = 'simulated_paid';
    case ExternallyBilled = 'externally_billed';
    case Paid = 'paid';
    case Trialing = 'trialing';
    case PastDue = 'past_due';
}
