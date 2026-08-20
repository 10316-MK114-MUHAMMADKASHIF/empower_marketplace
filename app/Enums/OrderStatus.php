<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case IntakeSubmitted = 'intake_submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
