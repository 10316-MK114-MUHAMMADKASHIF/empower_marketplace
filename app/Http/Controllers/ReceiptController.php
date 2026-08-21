<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TCPDF;

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

        $html = view('receipts.pdf', [
            'order' => $order,
            'practice' => $order->user->practice,
        ])->render();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setCreator(config('app.name'));
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = "receipt-order-{$order->id}.pdf";

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
