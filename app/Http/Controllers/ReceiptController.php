<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $order->isPaid()) {
            abort(404, 'Receipt not yet available.');
        }

        $order->loadMissing(['package', 'user.practice']);

        return view('receipts.show', [
            'order' => $order,
            'practice' => $order->user->practice,
        ]);
    }
}
