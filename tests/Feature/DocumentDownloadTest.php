<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Models\GeneratedDocument;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompletedDocument(User $user): GeneratedDocument
    {
        Storage::fake('local');
        $fakePdfPath = 'private/compliance/1/employee_handbook_basic.pdf';
        Storage::disk('local')->put($fakePdfPath, '%PDF-1.4 fake');

        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
        ]);

        return GeneratedDocument::factory()->create([
            'order_id' => $order->id,
            'document_type' => DocumentType::EmployeeHandbookBasic,
            'status' => DocumentStatus::Completed,
            'pdf_storage_path' => $fakePdfPath,
            'pdf_owner_password' => 'secret',
            'generated_at' => now(),
        ]);
    }

    // ── Access control ────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_download(): void
    {
        $document = $this->makeCompletedDocument(User::factory()->create());

        $this->get(route('documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_owner_can_download_their_pdf(): void
    {
        $user = User::factory()->create();
        $document = $this->makeCompletedDocument($user);

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_other_user_cannot_download_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = $this->makeCompletedDocument($owner);

        $this->actingAs($other)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    // ── Status checks ─────────────────────────────────────────────────────

    public function test_pending_document_returns_404(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        $document = GeneratedDocument::factory()->create([
            'order_id' => $order->id,
            'document_type' => DocumentType::OshaSafetyPlan,
            'status' => DocumentStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertNotFound();
    }

    // ── DOCX format ───────────────────────────────────────────────────────

    public function test_docx_format_query_param_downloads_docx(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $pdfPath = 'private/compliance/1/employee_handbook_basic.pdf';
        $docxPath = 'private/compliance/1/employee_handbook_basic.docx';
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 fake');
        Storage::disk('local')->put($docxPath, 'PK fake docx');

        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'payment_status' => PaymentStatus::SimulatedPaid,
        ]);

        $document = GeneratedDocument::factory()->create([
            'order_id' => $order->id,
            'document_type' => DocumentType::EmployeeHandbookBasic,
            'status' => DocumentStatus::Completed,
            'pdf_storage_path' => $pdfPath,
            'docx_storage_path' => $docxPath,
            'pdf_owner_password' => 'secret',
            'generated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('documents.download', $document).'?format=docx')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
