<?php

namespace App\Services;

use TCPDF;

/**
 * TCPDF is extended (rather than configured via setHeaderData()) because that
 * built-in helper always resolves the logo path through the K_PATH_IMAGES
 * constant, which would silently break an absolute, per-practice logo path.
 */
class CompliancePdf extends TCPDF
{
    public ?string $headerTitle = null;

    public ?string $headerLogoPath = null;

    public function Header(): void
    {
        if ($this->headerTitle === null) {
            return;
        }

        $x = $this->original_lMargin;
        $y = $this->header_margin;

        if ($this->headerLogoPath !== null && file_exists($this->headerLogoPath)) {
            $this->Image($this->headerLogoPath, $x, $y, 0, 10);
            $x += 14;
        }

        $this->setTextColorArray([90, 90, 90]);
        $this->setFont('helvetica', 'B', 10);
        $this->setXY($x, $y + 2);
        $this->Cell(0, 6, $this->headerTitle, 0, 0, 'L');

        $this->setLineStyle(['width' => 0.2, 'color' => [200, 200, 200]]);
        $this->Line($this->original_lMargin, $y + 12, $this->w - $this->original_rMargin, $y + 12);
    }
}
