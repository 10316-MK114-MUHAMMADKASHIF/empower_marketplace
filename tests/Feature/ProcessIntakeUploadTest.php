<?php

namespace Tests\Feature;

use App\Enums\AiExtractionStatus;
use App\Enums\DocumentType;
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

    public function test_dispatches_generate_jobs_after_all_uploads_processed(): void
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

        $upload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'storage_path' => 'uploads/4/intake.pdf',
            'mime_type' => 'application/pdf',
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response($this->openaiResponse('{}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        // Essential tier = EmployeeHandbookBasic + OshaSafetyPlan → 2 jobs
        Queue::assertPushed(GenerateComplianceDocument::class, 2);
        Queue::assertPushed(GenerateComplianceDocument::class, function ($job) {
            return $job->documentType === DocumentType::EmployeeHandbookBasic;
        });
        Queue::assertPushed(GenerateComplianceDocument::class, function ($job) {
            return $job->documentType === DocumentType::OshaSafetyPlan;
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

    // ── Sibling propagation (one upload shared across a cart checkout) ─────

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
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);
        $siblingUpload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submissionB->id,
            'storage_path' => 'uploads/batch/shared.pdf',
            'mime_type' => 'application/pdf',
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

        // Only one OpenAI API call for the shared document.
        Http::assertSentCount(1);

        // Both orders' compliance documents should be generated (essential tier = 2 docs each).
        Queue::assertPushed(GenerateComplianceDocument::class, 4);
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

    public function test_dispatches_per_location_jobs_for_advanced_tier(): void
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
            'ai_extraction_status' => AiExtractionStatus::Pending,
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response($this->openaiResponse('{}')),
        ]);

        ProcessIntakeUpload::dispatchSync($upload);

        // Advanced = 4 non-location docs + OshaLocationReport × 2 locations = 6 jobs
        Queue::assertPushed(GenerateComplianceDocument::class, 6);
        Queue::assertPushed(GenerateComplianceDocument::class, function ($job) {
            return $job->documentType === DocumentType::OshaLocationReport && $job->oshaLocation !== null;
        });
    }
}
