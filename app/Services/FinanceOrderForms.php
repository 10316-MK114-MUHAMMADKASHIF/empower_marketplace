<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\OrderFormMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/** Who Finance is, and the immediate hand-off of a new order's Order Form to them. */
class FinanceOrderForms
{
    /** The configured Finance address(es), else every admin. @return Collection<int, string> */
    public function recipients(): Collection
    {
        $configured = config('services.finance.report_email');

        if ($configured) {
            return collect(explode(',', $configured))->map(fn ($email) => trim($email))->filter()->values();
        }

        return User::where('role', UserRole::Admin)->pluck('email');
    }

    /** Addresses copied on every order form and report. @return Collection<int, string> */
    public function copies(): Collection
    {
        return collect(explode(',', (string) config('services.finance.order_form_copy_emails')))
            ->map(fn ($email) => trim($email))->filter()->unique()->diff($this->recipients())->values();
    }

    /** Emails the order's Order Form PDF to Finance (cc the copy addresses) the moment it is placed. */
    public function sendNewOrder(Order $order): void
    {
        $to = $this->recipients();

        if ($to->isEmpty()) {
            return;
        }

        try {
            Mail::to($to->all())->cc($this->copies()->all())->queue(new OrderFormMail($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
