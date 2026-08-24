<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\GeneratedDocument;
use App\Models\Order;
use App\Models\OshaLocation;
use App\Services\CompliancePdfGenerator;
use App\Support\ManualQuestionSets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class GenerateComplianceDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly DocumentType $documentType,
        public readonly ?OshaLocation $oshaLocation = null,
    ) {}

    public function handle(CompliancePdfGenerator $pdfGenerator): void
    {
        $doc = GeneratedDocument::firstOrCreate(
            [
                'order_id' => $this->order->id,
                'document_type' => $this->documentType,
                'osha_location_id' => $this->oshaLocation?->id,
            ],
            ['status' => DocumentStatus::Pending]
        );

        // Revoke any prior admin approval — a (re)generated document must be reviewed again.
        $doc->update(['status' => DocumentStatus::Generating, 'reviewed_at' => null, 'reviewed_by' => null]);

        try {
            $viewData = $this->buildViewData();
            $basePath = 'private/compliance/'.$this->order->id;
            $slug = $this->documentType->value.($this->oshaLocation ? '_'.$this->oshaLocation->id : '');

            // A manual assembled from the client's own questionnaire answers — merge the
            // real answers into the template, then convert the result to a protected PDF.
            if ($this->documentType->linkedQuestionnaireType() !== null) {
                $this->generateFromQuestionnaireTemplate($doc, $pdfGenerator, $basePath, $slug, $viewData);

                return;
            }

            if ($this->documentType->isDocxOnly()) {
                $docxPath = $this->generateDocx($basePath, $slug, $viewData);

                if (! $docxPath) {
                    throw new \RuntimeException("Template not found for {$this->documentType->value}");
                }

                $doc->update([
                    'status' => DocumentStatus::Completed,
                    'pdf_storage_path' => null,
                    'docx_storage_path' => $docxPath,
                    'pdf_owner_password' => null,
                    'is_stale' => false,
                    'failure_reason' => null,
                    'generated_at' => now(),
                ]);

                return;
            }

            $html = view('documents.'.$this->documentType->value, $viewData)->render();

            $ownerPassword = Str::random(32);
            $pdfContent = $pdfGenerator->generate($html, $ownerPassword);

            $pdfPath = "{$basePath}/{$slug}.pdf";
            Storage::disk('local')->put($pdfPath, $pdfContent);

            $docxPath = $this->generateDocx($basePath, $slug, $viewData);

            $doc->update([
                'status' => DocumentStatus::Completed,
                'pdf_storage_path' => $pdfPath,
                'docx_storage_path' => $docxPath,
                'pdf_owner_password' => $ownerPassword,
                'is_stale' => false,
                'failure_reason' => null,
                'generated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('GenerateComplianceDocument failed', [
                'order_id' => $this->order->id,
                'document_type' => $this->documentType->value,
                'error' => $e->getMessage(),
            ]);

            $doc->update([
                'status' => DocumentStatus::Failed,
                'failure_reason' => $e->getMessage(),
            ]);
        }
    }

    /** @param array<string, mixed> $viewData */
    private function generateFromQuestionnaireTemplate(
        GeneratedDocument $doc,
        CompliancePdfGenerator $pdfGenerator,
        string $basePath,
        string $slug,
        array $viewData,
    ): void {
        $docxPath = $this->generateDocx($basePath, $slug, $viewData);

        if (! $docxPath) {
            throw new \RuntimeException("Template not found for {$this->documentType->value}");
        }

        $html = $this->convertDocxToHtml($docxPath);

        $ownerPassword = Str::random(32);
        $pdfContent = $pdfGenerator->generate($html, $ownerPassword);

        $pdfPath = "{$basePath}/{$slug}.pdf";
        Storage::disk('local')->put($pdfPath, $pdfContent);

        $doc->update([
            'status' => DocumentStatus::Completed,
            'pdf_storage_path' => $pdfPath,
            'docx_storage_path' => $docxPath,
            'pdf_owner_password' => $ownerPassword,
            'is_stale' => false,
            'failure_reason' => null,
            'generated_at' => now(),
        ]);
    }

    private function convertDocxToHtml(string $docxPath): string
    {
        // Converting a large, heavily-formatted manual to HTML and then parsing that HTML's
        // CSS in TCPDF comfortably exceeds PHP's default 128M CLI/worker memory limit.
        if ((int) ini_get('memory_limit') !== -1 && $this->parseMemoryLimit(ini_get('memory_limit')) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

        $absoluteDocxPath = Storage::disk('local')->path($docxPath);
        $phpWord = IOFactory::load($absoluteDocxPath);
        $writer = IOFactory::createWriter($phpWord, 'HTML');

        $tempHtmlPath = tempnam(sys_get_temp_dir(), 'compliance_doc_').'.html';

        try {
            $writer->save($tempHtmlPath);

            return (string) file_get_contents($tempHtmlPath);
        } finally {
            if (file_exists($tempHtmlPath)) {
                unlink($tempHtmlPath);
            }
        }
    }

    private function parseMemoryLimit(string $limit): int
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /** @return array<string, mixed> */
    private function buildViewData(): array
    {
        $order = $this->order->load(['package', 'user.practice', 'intakeSubmission.intakeUploads']);
        $practice = $order->user->practice;
        $submission = $order->intakeSubmission;

        $aiData = [];
        if ($submission) {
            foreach ($submission->intakeUploads as $upload) {
                if ($upload->ai_extracted_data) {
                    $aiData = array_merge($aiData, $upload->ai_extracted_data);
                }
            }
        }

        return [
            'practice' => $practice,
            'order' => $order,
            'handbookAnswers' => $submission?->handbook_answers ?? [],
            'aiData' => $aiData,
            'oshaLocation' => $this->oshaLocation,
            'documentType' => $this->documentType,
            'generatedAt' => now(),
        ];
    }

    /** @param array<string, mixed> $viewData */
    private function generateDocx(string $basePath, string $slug, array $viewData): ?string
    {
        $templatePath = storage_path("app/templates/{$this->documentType->value}.docx");

        if (! file_exists($templatePath)) {
            return null;
        }

        $docxPath = "{$basePath}/{$slug}.docx";
        $absoluteOutput = Storage::disk('local')->path($docxPath);

        $dir = dirname($absoluteOutput);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $practice = $viewData['practice'];
        $processor = new TemplateProcessor($templatePath);

        $values = [
            'practice_name' => $practice?->name ?? '',
            'practice_address' => $practice?->address ?? '',
            'npi_number' => $practice?->npi_number ?? '',
            'specialty' => $practice?->specialty ?? '',
            'provider_count' => (string) ($practice?->billable_providers_count ?? ''),
            'package_name' => $viewData['order']->package?->name ?? '',
            'date' => $viewData['generatedAt']->format('F j, Y'),
            'osha_location_name' => $viewData['oshaLocation']?->name ?? '',
        ];

        $schema = ManualQuestionSets::forDocumentType($this->documentType);

        if ($schema !== null) {
            $aiData = $viewData['aiData'];

            foreach (ManualQuestionSets::mergeFieldNames($schema) as $field) {
                $values[$field] = (string) ($aiData[$field] ?? '[No response provided]');
            }
        }

        $processor->setValues($values);
        $processor->saveAs($absoluteOutput);

        return $docxPath;
    }
}
