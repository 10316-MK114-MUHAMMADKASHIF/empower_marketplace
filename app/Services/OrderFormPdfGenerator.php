<?php

namespace App\Services;

use App\Models\Order;
use TCPDF;

/** The Order Form Finance uses to set up the account and bill the product — built from the client's
 *  practice and user data, billed to their current form of payment. */
class OrderFormPdfGenerator
{
    /** Renders the order's Order Form to PDF bytes. */
    public function generate(Order $order): string
    {
        $order->loadMissing(['package', 'user.practice']);

        $html = view('documents.order-form-pdf', ['order' => $order, 'practice' => $order->user->practice])->render();

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setCreator(config('app.name'));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return (string) $pdf->Output('', 'S');
    }
}
