<?php

namespace App\Services;

use TCPDF;

class CompliancePdfGenerator
{
    /** Renders HTML to an AES-256-protected, read-only PDF and returns the raw bytes. */
    public function generate(string $html, string $ownerPassword): string
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setCreator(config('app.name'));
        // 3 = AES-256; user password empty = opens without password; read-only (print only)
        $pdf->setProtection(['print'], '', $ownerPassword, 3);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return (string) $pdf->Output('', 'S');
    }
}
