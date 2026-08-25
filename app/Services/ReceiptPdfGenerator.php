<?php

namespace App\Services;

use App\Models\Order;
use TCPDF;

class ReceiptPdfGenerator
{
    /** Renders the order's payment receipt to PDF bytes. */
    public function generate(Order $order): string
    {
        $order->loadMissing(['package', 'user']);

        $html = view('documents.receipt-pdf', ['order' => $order])->render();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setCreator(config('app.name'));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return (string) $pdf->Output('', 'S');
    }
}
