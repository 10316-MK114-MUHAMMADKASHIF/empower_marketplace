<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Jobs\GenerateComplianceDocument;
use App\Models\GeneratedDocument;
use App\Models\IntakeSubmission;
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
