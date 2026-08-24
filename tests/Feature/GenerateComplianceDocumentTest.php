<?php

namespace Tests\Feature;

use App\Enums\AiExtractionStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\IntakeUploadType;
use App\Enums\PaymentStatus;
use App\Jobs\GenerateComplianceDocument;
use App\Models\GeneratedDocument;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use App\Models\Order;
use App\Models\OshaLocation;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use App\Services\CompliancePdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateComplianceDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function mockPdfGenerator(): void
    {
        $this->mock(CompliancePdfGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn('%PDF-1.4 fake-content');
        });
    }

    private function makeOrder(string $packageSlug = 'essential'): Order
    {
        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'slug' => $packageSlug,
            'annual_price' => 999,
            'is_active' => true,
        ]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
        ]);
        IntakeSubmission::factory()->approved()->create(['order_id' => $order->id]);

        return $order;
    }

    // ── Happy path ────────────────────────────────────────────────────────

    public function test_generates_pdf_and_creates_completed_document_record(): void
    {
        Storage::fake('local');
        $this->mockPdfGenerator();

        $order = $this->makeOrder();

        GenerateComplianceDocument::dispatchSync($order, DocumentType::EmployeeHandbookBasic);

        $this->assertDatabaseHas('generated_documents', [
            'order_id' => $order->id,
            'document_type' => DocumentType::EmployeeHandbookBasic->value,
            'status' => DocumentStatus::Completed->value,
        ]);

        $doc = GeneratedDocument::where('order_id', $order->id)->first();
        $this->assertNotNull($doc->pdf_storage_path);
        $this->assertNotNull($doc->pdf_owner_password);
        $this->assertNotNull($doc->generated_at);
        Storage::disk('local')->assertExists($doc->pdf_storage_path);
    }

    public function test_generates_osha_location_report_with_location(): void
    {
        Storage::fake('local');
        $this->mock(CompliancePdfGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn('%PDF-1.4 fake');
        });

        $order = $this->makeOrder('advanced');
        $location = OshaLocation::factory()->create([
            'practice_id' => $order->user->practice->id,
        ]);

        GenerateComplianceDocument::dispatchSync($order, DocumentType::OshaLocationReport, $location);

        $this->assertDatabaseHas('generated_documents', [
            'order_id' => $order->id,
            'document_type' => DocumentType::OshaLocationReport->value,
            'osha_location_id' => $location->id,
            'status' => DocumentStatus::Completed->value,
        ]);
    }

    public function test_pdf_stored_at_correct_path(): void
    {
        Storage::fake('local');
        $this->mockPdfGenerator();

        $order = $this->makeOrder();

        GenerateComplianceDocument::dispatchSync($order, DocumentType::OshaSafetyPlan);

        $doc = GeneratedDocument::where([
            'order_id' => $order->id,
            'document_type' => DocumentType::OshaSafetyPlan->value,
        ])->firstOrFail();

        $this->assertStringStartsWith("private/compliance/{$order->id}/", $doc->pdf_storage_path);
        $this->assertStringEndsWith('.pdf', $doc->pdf_storage_path);
    }

    public function test_regenerating_an_approved_document_revokes_its_approval(): void
    {
        Storage::fake('local');
        $this->mockPdfGenerator();

        $order = $this->makeOrder();
        $admin = User::factory()->create();
        $document = GeneratedDocument::factory()->completed()->approved()->create([
            'order_id' => $order->id,
            'document_type' => DocumentType::EmployeeHandbookBasic,
            'reviewed_by' => $admin->id,
        ]);

        GenerateComplianceDocument::dispatchSync($order, DocumentType::EmployeeHandbookBasic);

        $document->refresh();
        $this->assertNull($document->reviewed_at);
        $this->assertNull($document->reviewed_by);
        $this->assertEquals(DocumentStatus::Completed, $document->status);
    }

    public function test_idempotent_upsert_does_not_create_duplicate_records(): void
    {
        Storage::fake('local');
        $this->mock(CompliancePdfGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->twice()->andReturn('%PDF-1.4 fake');
        });

        $order = $this->makeOrder();

        GenerateComplianceDocument::dispatchSync($order, DocumentType::EmployeeHandbookBasic);
        GenerateComplianceDocument::dispatchSync($order, DocumentType::EmployeeHandbookBasic);

        $this->assertDatabaseCount('generated_documents', 1);
    }

    // ── Docx-only documents (Complete tier, merged from a real .docx template) ─

    public function test_docx_only_document_completes_with_merged_docx_and_no_pdf(): void
    {
        $order = $this->makeOrder('complete');

        GenerateComplianceDocument::dispatchSync($order, DocumentType::RevenueCycleBillingManual);

        $doc = GeneratedDocument::where('order_id', $order->id)->firstOrFail();

        $this->assertEquals(DocumentStatus::Completed, $doc->status);
        $this->assertNull($doc->pdf_storage_path);
        $this->assertNull($doc->pdf_owner_password);
        $this->assertNotNull($doc->docx_storage_path);
        Storage::disk('local')->assertExists($doc->docx_storage_path);

        $bytes = Storage::disk('local')->get($doc->docx_storage_path);
        $this->assertStringStartsWith('PK', $bytes);

        Storage::disk('local')->deleteDirectory("private/compliance/{$order->id}");
    }

    // ── Questionnaire-linked manuals (real answers merged into the template, then
    //    converted to a protected PDF) ────────────────────────────────────────────

    public function test_compliance_ethics_manual_merges_real_answers_and_produces_a_protected_pdf(): void
    {
        $this->mock(CompliancePdfGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn('%PDF-1.4 fake protected pdf');
        });

        $order = $this->makeOrder('complete');
        $submission = $order->intakeSubmission;

        $answers = [
            'compliance_officer_name' => 'Dr. Jane Rivera',
            'compliance_officer_email' => 'jane.rivera@example.com',
            'compliance_officer_phone' => '(555) 010-2200',
            'governing_body' => 'The Board of Partners',
            'compliance_committee_members' => 'Dr. Rivera, T. Lee, R. Chen',
        ];
        for ($i = 1; $i <= 17; $i++) {
            $answers[sprintf('cmp_%02d_answer', $i)] = "Answer for question {$i}.";
        }

        IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'ai_extraction_status' => AiExtractionStatus::Completed,
            'ai_extracted_data' => $answers,
        ]);

        GenerateComplianceDocument::dispatchSync($order, DocumentType::ComplianceEthicsManual);

        $doc = GeneratedDocument::where('order_id', $order->id)
            ->where('document_type', DocumentType::ComplianceEthicsManual)
            ->firstOrFail();

        $this->assertEquals(DocumentStatus::Completed, $doc->status);
        $this->assertNotNull($doc->docx_storage_path);
        $this->assertNotNull($doc->pdf_storage_path);
        $this->assertNotNull($doc->pdf_owner_password);
        Storage::disk('local')->assertExists($doc->docx_storage_path);
        Storage::disk('local')->assertExists($doc->pdf_storage_path);
        $this->assertSame('%PDF-1.4 fake protected pdf', Storage::disk('local')->get($doc->pdf_storage_path));

        // The merged docx must contain the client's real answers, not the template's blanks.
        $absoluteDocxPath = Storage::disk('local')->path($doc->docx_storage_path);
        $zip = new \ZipArchive;
        $zip->open($absoluteDocxPath);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('Answer for question 1.', $xml);
        $this->assertStringContainsString('Answer for question 17.', $xml);
        $this->assertStringContainsString('Dr. Jane Rivera', $xml);
        $this->assertStringNotContainsString('Click or tap here to enter text.', $xml);
        $this->assertStringNotContainsString('COMPANY', $xml);

        Storage::disk('local')->deleteDirectory("private/compliance/{$order->id}");
    }

    public function test_hipaa_business_associate_manual_merges_real_answers_and_produces_a_protected_pdf(): void
    {
        $this->assertQuestionnaireManualMergesRealAnswers(
            documentType: DocumentType::HipaaBusinessAssociateManual,
            uploadType: IntakeUploadType::HipaaBusinessAssociateQuestionnaire,
            extraAnswers: [
                'ba_officer_name' => 'Dr. Jane Rivera',
                'ba_officer_email' => 'jane.rivera@example.com',
                'ba_officer_phone' => '(555) 010-2200',
            ],
            questionPrefix: 'ba',
            questionCount: 46,
        );
    }

    public function test_hipaa_security_manual_merges_real_answers_and_produces_a_protected_pdf(): void
    {
        $this->assertQuestionnaireManualMergesRealAnswers(
            documentType: DocumentType::HipaaSecurityManual,
            uploadType: IntakeUploadType::HipaaSecurityQuestionnaire,
            extraAnswers: [
                'security_officer_name' => 'Dr. Jane Rivera',
                'security_officer_email' => 'jane.rivera@example.com',
                'security_officer_phone' => '(555) 010-2200',
            ],
            questionPrefix: 'sec',
            questionCount: 46,
        );
    }

    public function test_hipaa_privacy_policy_merges_real_answers_and_produces_a_protected_pdf(): void
    {
        $this->assertQuestionnaireManualMergesRealAnswers(
            documentType: DocumentType::HipaaPrivacyPolicy,
            uploadType: IntakeUploadType::HipaaPrivacyQuestionnaire,
            extraAnswers: [
                'privacy_officer_name' => 'Dr. Jane Rivera',
                'privacy_officer_email' => 'jane.rivera@example.com',
                'privacy_officer_phone' => '(555) 010-2200',
            ],
            questionPrefix: 'prv',
            questionCount: 38,
        );
    }

    /** @param array<string, string> $extraAnswers */
    private function assertQuestionnaireManualMergesRealAnswers(
        DocumentType $documentType,
        IntakeUploadType $uploadType,
        array $extraAnswers,
        string $questionPrefix,
        int $questionCount,
    ): void {
        $this->mock(CompliancePdfGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn('%PDF-1.4 fake protected pdf');
        });

        $order = $this->makeOrder('complete');
        $submission = $order->intakeSubmission;

        $answers = $extraAnswers;
        for ($i = 1; $i <= $questionCount; $i++) {
            $answers[sprintf('%s_%02d_answer', $questionPrefix, $i)] = "Answer for question {$i}.";
        }

        IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'upload_type' => $uploadType,
            'ai_extraction_status' => AiExtractionStatus::Completed,
            'ai_extracted_data' => $answers,
        ]);

        GenerateComplianceDocument::dispatchSync($order, $documentType);

        $doc = GeneratedDocument::where('order_id', $order->id)
            ->where('document_type', $documentType)
            ->firstOrFail();

        $this->assertEquals(DocumentStatus::Completed, $doc->status);
        $this->assertNotNull($doc->docx_storage_path);
        $this->assertNotNull($doc->pdf_storage_path);
        $this->assertNotNull($doc->pdf_owner_password);
        Storage::disk('local')->assertExists($doc->docx_storage_path);
        Storage::disk('local')->assertExists($doc->pdf_storage_path);
        $this->assertSame('%PDF-1.4 fake protected pdf', Storage::disk('local')->get($doc->pdf_storage_path));

        $absoluteDocxPath = Storage::disk('local')->path($doc->docx_storage_path);
        $zip = new \ZipArchive;
        $zip->open($absoluteDocxPath);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('Answer for question 1.', $xml);
        $this->assertStringContainsString("Answer for question {$questionCount}.", $xml);
        $this->assertStringContainsString(reset($extraAnswers), $xml);
        $this->assertStringNotContainsString('Click or tap here to enter text.', $xml);
        $this->assertStringNotContainsString('COMPANY', $xml);

        Storage::disk('local')->deleteDirectory("private/compliance/{$order->id}");
    }

    public function test_compliance_ethics_manual_defaults_missing_answers_when_questionnaire_not_uploaded(): void
    {
        $this->mock(CompliancePdfGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn('%PDF-1.4 fake');
        });

        $order = $this->makeOrder('complete');

        GenerateComplianceDocument::dispatchSync($order, DocumentType::ComplianceEthicsManual);

        $doc = GeneratedDocument::where('order_id', $order->id)
            ->where('document_type', DocumentType::ComplianceEthicsManual)
            ->firstOrFail();

        $this->assertEquals(DocumentStatus::Completed, $doc->status);

        $absoluteDocxPath = Storage::disk('local')->path($doc->docx_storage_path);
        $zip = new \ZipArchive;
        $zip->open($absoluteDocxPath);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('[No response provided]', $xml);
        $this->assertStringNotContainsString('Click or tap here to enter text.', $xml);

        Storage::disk('local')->deleteDirectory("private/compliance/{$order->id}");
    }

    public function test_docx_only_document_fails_cleanly_when_template_is_missing(): void
    {
        $order = $this->makeOrder('complete');

        $templatePath = storage_path('app/templates/revenue_cycle_billing_manual.docx');
        $backupPath = $templatePath.'.bak';
        rename($templatePath, $backupPath);

        try {
            GenerateComplianceDocument::dispatchSync($order, DocumentType::RevenueCycleBillingManual);
        } finally {
            rename($backupPath, $templatePath);
        }

        $doc = GeneratedDocument::where('order_id', $order->id)->firstOrFail();

        $this->assertEquals(DocumentStatus::Failed, $doc->status);
        $this->assertNotNull($doc->failure_reason);
        $this->assertNull($doc->docx_storage_path);
    }

    // ── Failure handling ──────────────────────────────────────────────────

    public function test_marks_document_failed_when_view_not_found(): void
    {
        Storage::fake('local');
        $this->mock(CompliancePdfGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->andThrow(new \RuntimeException('view not found'));
        });

        $order = $this->makeOrder();

        GenerateComplianceDocument::dispatchSync($order, DocumentType::EmployeeHandbookBasic);

        $this->assertDatabaseHas('generated_documents', [
            'order_id' => $order->id,
            'status' => DocumentStatus::Failed->value,
        ]);

        $doc = GeneratedDocument::where('order_id', $order->id)->first();
        $this->assertNotNull($doc->failure_reason);
    }
}
