<?php

namespace App\Services;

class CompliancePdfGenerator
{
    /**
     * Renders HTML to an AES-256-protected, read-only PDF and returns the raw bytes.
     *
     * Passing $headerTitle switches on the source manual's running header (shown
     * on every page after the cover, matching the original Word template) and a
     * generated Table of Contents built from the HTML's <h1>/<h2> headings. Other
     * document types keep rendering exactly as before by leaving it null.
     */
    public function generate(
        string $html,
        string $ownerPassword,
        ?string $headerTitle = null,
        ?string $headerLogoPath = null,
    ): string {
        $pdf = new CompliancePdf('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->setCreator(config('app.name'));
        $pdf->setPrintFooter(false);
        // 3 = AES-256; user password empty = opens without password; read-only (print only)
        $pdf->setProtection(['print'], '', $ownerPassword, 3);

        $pdf->setPrintHeader(false);
        $pdf->AddPage();

        if ($headerTitle === null) {
            $pdf->writeHTML($html, true, false, true, false, '');

            return (string) $pdf->Output('', 'S');
        }

        ['cover' => $cover, 'sections' => $sections] = $this->splitIntoHeadingSections($html);
        $pdf->writeHTML($cover, true, false, true, false, '');

        $pdf->headerTitle = $headerTitle;
        $pdf->headerLogoPath = $headerLogoPath;
        $pdf->setMargins(15, $headerLogoPath !== null ? 26 : 20, 15);
        $pdf->setHeaderMargin(8);
        $pdf->setPrintHeader(true);
        $pdf->AddPage();

        foreach ($sections as $section) {
            if ($section['title'] !== '') {
                $pdf->Bookmark($section['title'], $section['level'], -1);
            }
            $pdf->writeHTML($section['html'], true, false, true, false, '');
        }

        if ($sections !== []) {
            $pdf->addTOCPage();
            $pdf->writeHTML('<h1>Table of Contents</h1>', true, false, true, false, '');
            $pdf->addTOC(2, '', '.', 'Table of Contents');
            $pdf->endTOCPage();
        }

        return (string) $pdf->Output('', 'S');
    }

    /**
     * Splits the converted manual HTML into its cover-page content and one
     * entry per top-level <h1>/<h2> heading, each paired with the HTML that
     * follows it up to the next heading. Bookmarking each section exactly
     * where it starts is what powers the generated Table of Contents.
     *
     * @return array{cover: string, sections: array<int, array{level: int, title: string, html: string}>}
     */
    private function splitIntoHeadingSections(string $html): array
    {
        $parts = preg_split('/(<h[12][^>]*>.*?<\/h[12]>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false || count($parts) < 3) {
            return ['cover' => $html, 'sections' => []];
        }

        $cover = $parts[0];
        // The transition onto page 2 is now made explicitly (so the running
        // header can be switched on first) instead of via this trailing div.
        $cover = preg_replace('/<div style="page-break-before: always;.*?<\/div>\s*$/s', '', $cover, 1) ?? $cover;

        $sections = [];
        for ($i = 1; $i < count($parts); $i += 2) {
            $tag = $parts[$i];
            $body = $parts[$i + 1] ?? '';
            $level = stripos($tag, '<h1') === 0 ? 0 : 1;
            $title = trim(html_entity_decode(strip_tags($tag), ENT_QUOTES));

            $sections[] = ['level' => $level, 'title' => $title, 'html' => $tag.$body];
        }

        return ['cover' => $cover, 'sections' => $sections];
    }
}
