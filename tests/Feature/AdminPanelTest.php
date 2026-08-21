<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\IntakeSubmissionStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Jobs\GenerateComplianceDocument;
use App\Models\GeneratedDocument;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function makeSubmission(IntakeSubmissionStatus $status = IntakeSubmissionStatus::Submitted): IntakeSubmission
    {
        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        return IntakeSubmission::factory()->create([
            'order_id' => $order->id,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    // ── Access control ─────────────────────────────────────────────────────

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->withoutVite()->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_client_cannot_access_admin_routes(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->withoutVite()->actingAs($client)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->withoutVite()->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending Review');
    }

    // ── Submissions ─────────────────────────────────────────────────────────

    public function test_admin_can_view_submissions_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->makeSubmission();

        $this->withoutVite()->actingAs($admin)->get(route('admin.submissions'))->assertOk();
    }

    public function test_admin_can_view_submission_detail(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $submission = $this->makeSubmission();

        $this->withoutVite()->actingAs($admin)->get(route('admin.submissions.show', $submission))->assertOk();
    }

    public function test_admin_can_approve_a_submission(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $submission = $this->makeSubmission();

        Livewire::actingAs($admin)
            ->test('admin.submission-detail', ['submission' => $submission])
            ->call('approve');

        $submission->refresh();
        $this->assertSame(IntakeSubmissionStatus::Approved, $submission->status);
        $this->assertSame($admin->id, $submission->reviewed_by);
        $this->assertSame(OrderStatus::Approved, $submission->order->fresh()->status);

        $this->assertDatabaseHas('activity_logs', [
            'event_type' => 'submission.approved',
            'order_id' => $submission->order_id,
        ]);
    }

    public function test_admin_can_reject_a_submission_with_notes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $submission = $this->makeSubmission();

        Livewire::actingAs($admin)
            ->test('admin.submission-detail', ['submission' => $submission])
            ->set('reviewerNotes', 'Please re-upload a signed copy.')
            ->call('reject');

        $submission->refresh();
        $this->assertSame(IntakeSubmissionStatus::Rejected, $submission->status);
        $this->assertSame('Please re-upload a signed copy.', $submission->reviewer_notes);
    }

    public function test_rejecting_without_notes_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $submission = $this->makeSubmission();

        Livewire::actingAs($admin)
            ->test('admin.submission-detail', ['submission' => $submission])
            ->call('reject')
            ->assertHasErrors(['reviewerNotes']);
    }

    public function test_client_can_resubmit_after_rejection_without_duplicate_key_error(): void
    {
        Http::fake([
            'https://api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => '{}']]]]),
        ]);

        $user = User::factory()->create();
        Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);
        IntakeSubmission::factory()->create([
            'order_id' => $order->id,
            'status' => IntakeSubmissionStatus::Rejected,
            'reviewer_notes' => 'Fix the signature.',
            'submitted_at' => now()->subDay(),
        ]);

        $file = UploadedFile::fake()->create('intake.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test('portal')
            ->set('orderIds', [$order->id])
            ->set('questionnaireFiles.compliance_ethics_questionnaire', $file)
            ->call('submitIntake')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('intake_submissions', 1);
        $this->assertDatabaseHas('intake_submissions', [
            'order_id' => $order->id,
            'status' => IntakeSubmissionStatus::Submitted->value,
            'reviewer_notes' => null,
        ]);
    }

    // ── Documents ───────────────────────────────────────────────────────────

    public function test_admin_can_view_documents_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        GeneratedDocument::factory()->completed()->create();

        $this->withoutVite()->actingAs($admin)->get(route('admin.documents'))->assertOk();
    }

    public function test_admin_can_regenerate_a_document(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $document = GeneratedDocument::factory()->completed()->create([
            'document_type' => DocumentType::OshaSafetyPlan,
        ]);

        Livewire::actingAs($admin)
            ->test('admin.document-list')
            ->call('regenerate', $document->id);

        Bus::assertDispatched(GenerateComplianceDocument::class);

        $this->assertDatabaseHas('activity_logs', [
            'event_type' => 'document.regenerate_requested',
        ]);
    }

    // ── Leads ───────────────────────────────────────────────────────────────

    public function test_admin_can_view_leads_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Lead::factory()->create(['name' => 'Jane Provider']);

        $this->withoutVite()->actingAs($admin)->get(route('admin.leads'))
            ->assertOk()
            ->assertSee('Jane Provider');
    }

    public function test_admin_can_mark_a_lead_contacted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $lead = Lead::factory()->create();

        Livewire::actingAs($admin)
            ->test('admin.lead-list')
            ->call('markContacted', $lead->id);

        $this->assertTrue($lead->fresh()->is_contacted);
    }

    // ── Packages ────────────────────────────────────────────────────────────

    public function test_admin_can_view_packages_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Package::factory()->create(['slug' => 'essential', 'name' => 'Essential Compliance']);

        $this->withoutVite()->actingAs($admin)->get(route('admin.packages'))
            ->assertOk()
            ->assertSee('Essential Compliance');
    }

    public function test_client_cannot_access_packages_list(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->withoutVite()->actingAs($client)->get(route('admin.packages'))->assertForbidden();
    }

    public function test_admin_can_create_a_package_for_an_unused_tier(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Package::factory()->create(['slug' => 'essential']);

        Livewire::actingAs($admin)
            ->test('admin.package-form')
            ->set('slug', 'professional')
            ->set('name', 'Professional Compliance')
            ->set('billingType', 'annual')
            ->set('annualPrice', '2490')
            ->set('featuresText', "Feature One\nFeature Two")
            ->set('sortOrder', 2)
            ->call('save')
            ->assertRedirect(route('admin.packages'));

        $this->assertDatabaseHas('packages', [
            'slug' => 'professional',
            'name' => 'Professional Compliance',
        ]);

        $package = Package::where('slug', 'professional')->first();
        $this->assertSame(['Feature One', 'Feature Two'], $package->features);

        $this->assertDatabaseHas('activity_logs', ['event_type' => 'package.created']);
    }

    public function test_creating_a_package_requires_a_tier_not_already_in_use(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Package::factory()->create(['slug' => 'essential']);

        Livewire::actingAs($admin)
            ->test('admin.package-form')
            ->set('slug', 'essential')
            ->set('name', 'Duplicate')
            ->set('billingType', 'annual')
            ->call('save')
            ->assertHasErrors('slug');

        $this->assertSame(1, Package::where('slug', 'essential')->count());
    }

    public function test_admin_can_edit_an_existing_package(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $package = Package::factory()->create(['slug' => 'essential', 'name' => 'Essential Compliance']);

        Livewire::actingAs($admin)
            ->test('admin.package-form', ['package' => $package])
            ->assertSet('slug', 'essential')
            ->set('name', 'Essential Compliance Plus')
            ->set('annualPrice', '1999')
            ->call('save')
            ->assertRedirect(route('admin.packages'));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'Essential Compliance Plus',
            'annual_price' => 1999.00,
        ]);

        $this->assertDatabaseHas('activity_logs', ['event_type' => 'package.updated']);
    }

    public function test_admin_can_toggle_a_packages_active_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $package = Package::factory()->create(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test('admin.package-list')
            ->call('toggleActive', $package->id);

        $this->assertFalse($package->fresh()->is_active);
    }

    public function test_admin_can_delete_a_package_with_no_orders(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $package = Package::factory()->create();

        Livewire::actingAs($admin)
            ->test('admin.package-list')
            ->call('delete', $package->id);

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'package.deleted']);
    }

    public function test_admin_cannot_delete_a_package_with_existing_orders(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $package = Package::factory()->create();
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);

        Livewire::actingAs($admin)
            ->test('admin.package-list')
            ->call('delete', $package->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    // ── Intake upload download ───────────────────────────────────────────────

    public function test_admin_can_download_an_intake_upload(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $submission = $this->makeSubmission();
        Storage::disk('local')->put('uploads/test.pdf', 'fake-pdf-content');
        $upload = IntakeUpload::factory()->create([
            'intake_submission_id' => $submission->id,
            'storage_path' => 'uploads/test.pdf',
        ]);

        $this->actingAs($admin)->get(route('admin.uploads.download', $upload))->assertOk();
    }

    public function test_client_cannot_access_admin_upload_download_route(): void
    {
        $client = User::factory()->create(['role' => UserRole::Client]);
        $submission = $this->makeSubmission();
        $upload = IntakeUpload::factory()->create(['intake_submission_id' => $submission->id]);

        $this->actingAs($client)->get(route('admin.uploads.download', $upload))->assertForbidden();
    }
}
