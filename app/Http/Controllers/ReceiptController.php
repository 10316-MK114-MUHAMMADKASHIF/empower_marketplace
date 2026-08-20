<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiptController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $order->isPaid()) {
            abort(404, 'Receipt not yet available.');
        }

        $order->loadMissing(['package', 'user.practice']);
        $practice = $order->user->practice;

        $lines = [
            'EMPOWER MARKETPLACE',
            'Payment Receipt',
            str_repeat('-', 40),
            'Receipt #: '.$order->id,
            'Date: '.($order->paid_at?->format('F j, Y') ?? '—'),
            'Practice: '.($practice?->name ?? '—'),
            'Package: '.($order->package?->name ?? '—'),
            'Amount Paid: $'.number_format((float) $order->amount_paid, 2),
            'Payment Status: Paid (simulated)',
            str_repeat('-', 40),
            'Thank you for your business.',
        ];

        $filename = "receipt-order-{$order->id}.txt";

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
