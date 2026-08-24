<?php

namespace Tests\Feature;

use App\Enums\AiExtractionStatus;
use App\Enums\DocumentType;
use App\Enums\IntakeUploadType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\GenerateComplianceDocument;
use App\Jobs\ProcessIntakeUpload;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use App\Models\Order;
use App\Models\OshaLocation;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProcessIntakeUploadTest extends TestCase
{
    use RefreshDatabase;

    private function openaiResponse(string $json): array
    {
        return ['choices' => [['message' => ['content' => $json]]]];
    }

    // ── Vision extraction (PDF / image) ───────────────────────────────────

    public function test_processes_pdf_upload_with_openai_vision(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/1/intake.pdf', '%PDF-1.4 fake content');

        $upload = IntakeUpload::factory()->create([
            'storage_path' => 'uploads/1/intake.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response(
                $this->openaiResponse('{"practice_name":"Sunrise Clinic","npi_number":"1234567890"}')
            ),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        $upload->refresh();
        $this->assertEquals(AiExtractionStatus::Completed, $upload->ai_extraction_status);
        $this->assertEquals('Sunrise Clinic', $upload->ai_extracted_data['practice_name']);
        $this->assertNotNull($upload->processed_at);
    }

    public function test_extracts_json_wrapped_in_markdown_code_fences(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/2/doc.pdf', 'fake pdf');

        $upload = IntakeUpload::factory()->create([
            'storage_path' => 'uploads/2/doc.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response(
                $this->openaiResponse("```json\n{\"specialty\":\"Cardiology\"}\n```")
            ),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        $upload->refresh();
        $this->assertEquals(AiExtractionStatus::Completed, $upload->ai_extraction_status);
        $this->assertEquals('Cardiology', $upload->ai_extracted_data['specialty']);
    }

    // ── Docx text extraction ────────────────────────────────────────────────

    public function test_extracts_text_from_table_based_docx_questionnaire(): void
    {
        Storage::fake('local');

        $phpWord = new PhpWord;
        $table = $phpWord->addSection()->addTable();
        $table->addRow();
        $table->addCell(3000)->addText('Legal Practice Name');
        $table->addCell(6000)->addText('Sunrise Family Medicine');

        $tempPath = tempnam(sys_get_temp_dir(), 'docx').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);
        Storage::disk('local')->put('uploads/4/questionnaire.docx', file_get_contents($tempPath));
        unlink($tempPath);

        $upload = IntakeUpload::factory()->create([
            'storage_path' => 'uploads/4/questionnaire.docx',
            'original_filename' => 'questionnaire.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response(
                $this->openaiResponse('{"practice_name":"Sunrise Family Medicine"}')
            ),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], 'Sunrise Family Medicine'));

        $upload->refresh();
        $this->assertEquals(AiExtractionStatus::Completed, $upload->ai_extraction_status);
        $this->assertEquals('Sunrise Family Medicine', $upload->ai_extracted_data['practice_name']);
    }

    // ── Structured extraction & AI verification pass (questionnaire-linked types) ──

    /** @return array<string, array{0: IntakeUploadType, 1: string}> */
    public static function questionnaireLinkedTypes(): array
    {
        return [
            'Compliance & Ethics' => [IntakeUploadType::ComplianceEthicsQuestionnaire, 'cmp_01_answer'],
            'HIPAA Business Associate' => [IntakeUploadType::HipaaBusinessAssociateQuestionnaire, 'ba_01_answer'],
            'HIPAA Privacy' => [IntakeUploadType::HipaaPrivacyQuestionnaire, 'prv_01_answer'],
            'HIPAA Security' => [IntakeUploadType::HipaaSecurityQuestionnaire, 'sec_01_answer'],
        ];
    }

    #[DataProvider('questionnaireLinkedTypes')]
    public function test_questionnaire_linked_upload_uses_a_structured_extraction_prompt(
        IntakeUploadType $uploadType,
        string $expectedField,
    ): void {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/7/questionnaire.pdf', 'fake pdf');

        $upload = IntakeUpload::factory()->create([
            'storage_path' => 'uploads/7/questionnaire.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => $uploadType,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response($this->openaiResponse('{}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        Http::assertSent(function ($request) use ($expectedField) {
            $content = $request['messages'][0]['content'];
            $text = is_array($content) ? ($content[1]['text'] ?? '') : $content;

            return str_contains($text, $expectedField);
        });
    }

    public function test_compliance_ethics_extraction_runs_a_verification_pass_and_saves_its_output(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/8/compliance.pdf', 'fake pdf');

        $upload = IntakeUpload::factory()->create([
            'storage_path' => 'uploads/8/compliance.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::sequence()
                ->push($this->openaiResponse('{"cmp_01_answer":"raw noisy answer"}'))
                ->push($this->openaiResponse('{"cmp_01_answer":"Cleaned answer."}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        Http::assertSentCount(2);

        $upload->refresh();
        $this->assertEquals(AiExtractionStatus::Completed, $upload->ai_extraction_status);
        $this->assertSame('Cleaned answer.', $upload->ai_extracted_data['cmp_01_answer']);
    }

    public function test_verification_pass_failure_falls_back_to_the_raw_extraction(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/9/compliance.pdf', 'fake pdf');

        $upload = IntakeUpload::factory()->create([
            'storage_path' => 'uploads/9/compliance.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::sequence()
                ->push($this->openaiResponse('{"cmp_01_answer":"raw answer"}'))
                ->push(['error' => 'overloaded'], 529),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        $upload->refresh();
        $this->assertEquals(AiExtractionStatus::Completed, $upload->ai_extraction_status);
        $this->assertSame('raw answer', $upload->ai_extracted_data['cmp_01_answer']);
    }

    // ── Failure handling ──────────────────────────────────────────────────

    public function test_marks_upload_failed_when_openai_returns_http_error(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/3/doc.pdf', 'fake');

        $upload = IntakeUpload::factory()->create([
            'storage_path' => 'uploads/3/doc.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => 'overloaded'], 529),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        $upload->refresh();
        $this->assertEquals(AiExtractionStatus::Failed, $upload->ai_extraction_status);
        $this->assertStringContainsString('529', $upload->ai_error_message);
        $this->assertNotNull($upload->processed_at);
    }

    // ── Document generation dispatch ──────────────────────────────────────

    public function test_an_upload_with_no_matching_manual_dispatches_no_generation(): void
    {
        Queue::fake([GenerateComplianceDocument::class]);
        Storage::fake('local');
        Storage::disk('local')->put('uploads/4/intake.pdf', 'fake');

        $user = User::factory()->create();
        $practice = Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);
        $submission = IntakeSubmission::factory()->submitted()->create(['order_id' => $order->id]);

        // PracticeIntake is a retired, generic upload type with no matching manual —
        // generation is driven entirely by which of the 4 real questionnaires were
        // uploaded, never by package tier.
        $upload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'storage_path' => 'uploads/4/intake.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => IntakeUploadType::PracticeIntake,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response($this->openaiResponse('{}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        Queue::assertNotPushed(GenerateComplianceDocument::class);
    }

    public function test_uploading_a_linked_questionnaire_also_dispatches_its_matching_manual(): void
    {
        Queue::fake([GenerateComplianceDocument::class]);
        Storage::fake('local');
        Storage::disk('local')->put('uploads/4b/compliance.pdf', 'fake');

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'status' => OrderStatus::Paid,
        ]);
        $submission = IntakeSubmission::factory()->submitted()->create(['order_id' => $order->id]);

        $upload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'storage_path' => 'uploads/4b/compliance.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response($this->openaiResponse('{}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        // Exactly the Compliance & Ethics Manual dispatches, since that's the one
        // questionnaire uploaded — regardless of package tier. The other 3 linked
        // manuals don't dispatch, since they weren't uploaded.
        Queue::assertPushed(GenerateComplianceDocument::class, 1);
        Queue::assertPushed(GenerateComplianceDocument::class, function ($job) {
            return $job->documentType === DocumentType::ComplianceEthicsManual;
        });
        Queue::assertNotPushed(GenerateComplianceDocument::class, function ($job) {
            return $job->documentType === DocumentType::HipaaBusinessAssociateManual;
        });
    }

    public function test_does_not_dispatch_generate_jobs_until_all_uploads_complete(): void
    {
        Queue::fake([GenerateComplianceDocument::class]);
        Storage::fake('local');
        Storage::disk('local')->put('uploads/5/intake.pdf', 'fake');
        Storage::disk('local')->put('uploads/5/osha.pdf', 'fake');

        $submission = IntakeSubmission::factory()->submitted()->create();

        // Two uploads — only processing the first one
        $upload1 = IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'storage_path' => 'uploads/5/intake.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);
        // Second upload still pending
        IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'storage_path' => 'uploads/5/osha.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response($this->openaiResponse('{}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload1);

        Queue::assertNotPushed(GenerateComplianceDocument::class);
    }

    // ── Sibling propagation (one upload shared across a batch checkout) ─────

    public function test_propagates_extraction_to_sibling_uploads_sharing_the_same_file(): void
    {
        Queue::fake([GenerateComplianceDocument::class]);
        Storage::fake('local');
        Storage::disk('local')->put('uploads/batch/shared.pdf', 'fake pdf');

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        $orderA = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id, 'payment_status' => PaymentStatus::SimulatedPaid]);
        $orderB = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id, 'payment_status' => PaymentStatus::SimulatedPaid]);
        $submissionA = IntakeSubmission::factory()->submitted()->create(['order_id' => $orderA->id]);
        $submissionB = IntakeSubmission::factory()->submitted()->create(['order_id' => $orderB->id]);

        $primaryUpload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submissionA->id,
            'storage_path' => 'uploads/batch/shared.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);
        $siblingUpload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submissionB->id,
            'storage_path' => 'uploads/batch/shared.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response(
                $this->openaiResponse('{"practice_name":"Shared Practice"}')
            ),
        ]);

        ProcessIntakeUpload::dispatchSync($primaryUpload);

        $primaryUpload->refresh();
        $siblingUpload->refresh();

        $this->assertEquals(AiExtractionStatus::Completed, $primaryUpload->ai_extraction_status);
        $this->assertEquals(AiExtractionStatus::Completed, $siblingUpload->ai_extraction_status);
        $this->assertEquals('Shared Practice', $siblingUpload->ai_extracted_data['practice_name']);

        // Two OpenAI calls total for the shared document (extraction + verification pass,
        // since Compliance & Ethics has a structured schema) — not once per order.
        Http::assertSentCount(2);

        // Both orders get their own Compliance & Ethics Manual generated, since both
        // share the uploaded questionnaire.
        Queue::assertPushed(GenerateComplianceDocument::class, 2);
    }

    public function test_marks_sibling_uploads_failed_when_primary_extraction_fails(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('uploads/batch/shared.pdf', 'fake pdf');

        $orderA = Order::factory()->create(['package_id' => Package::factory()->create(['slug' => 'essential'])->id]);
        $orderB = Order::factory()->create(['package_id' => Package::factory()->create(['slug' => 'professional'])->id]);
        $submissionA = IntakeSubmission::factory()->submitted()->create(['order_id' => $orderA->id]);
        $submissionB = IntakeSubmission::factory()->submitted()->create(['order_id' => $orderB->id]);

        $primaryUpload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submissionA->id,
            'storage_path' => 'uploads/batch/shared.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);
        $siblingUpload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submissionB->id,
            'storage_path' => 'uploads/batch/shared.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => 'overloaded'], 529),
        ]);

        ProcessIntakeUpload::dispatchSync($primaryUpload);

        $this->assertEquals(AiExtractionStatus::Failed, $primaryUpload->fresh()->ai_extraction_status);
        $this->assertEquals(AiExtractionStatus::Failed, $siblingUpload->fresh()->ai_extraction_status);
    }

    public function test_configured_osha_locations_do_not_multiply_a_non_per_location_linked_manual(): void
    {
        Queue::fake([GenerateComplianceDocument::class]);
        Storage::fake('local');
        Storage::disk('local')->put('uploads/6/intake.pdf', 'fake');

        $user = User::factory()->create();
        $practice = Practice::factory()->locked()->create(['user_id' => $user->id]);
        OshaLocation::factory()->count(2)->create(['practice_id' => $practice->id]);

        $package = Package::factory()->create(['slug' => 'advanced', 'annual_price' => 1699, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
        ]);
        $submission = IntakeSubmission::factory()->submitted()->create(['order_id' => $order->id]);

        $upload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'storage_path' => 'uploads/6/intake.pdf',
            'mime_type' => 'application/pdf',
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response($this->openaiResponse('{}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        // Exactly one Compliance & Ethics Manual job, regardless of package tier or how
        // many OSHA locations the practice has configured — it isn't a per-location type.
        Queue::assertPushed(GenerateComplianceDocument::class, 1);
        Queue::assertPushed(GenerateComplianceDocument::class, function ($job) {
            return $job->documentType === DocumentType::ComplianceEthicsManual && $job->oshaLocation === null;
        });
    }
}
