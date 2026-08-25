<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\GeneratedDocument;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use App\Models\Order;
use App\Models\OshaLocation;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUsersOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // ── Users ─────────────────────────────────────────────────────────────

    public function test_admin_users_and_orders_pages_render(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create();
        Practice::factory()->create(['user_id' => $client->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $client->id, 'package_id' => $package->id]);

        $this->withoutVite()->actingAs($admin);

        $this->get(route('admin.users'))->assertOk();
        $this->get(route('admin.users.create'))->assertOk();
        $this->get(route('admin.users.edit', $client))->assertOk();
        $this->get(route('admin.orders'))->assertOk();
        $this->get(route('admin.orders.edit', $order))->assertOk();
    }

    public function test_admin_can_view_and_search_the_users_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['name' => 'Findable Client']);
        User::factory()->create(['name' => 'Someone Else']);

        Livewire::actingAs($admin)
            ->test('admin.user-list')
            ->set('search', 'Findable')
            ->assertSee('Findable Client')
            ->assertDontSee('Someone Else');
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test('admin.user-form')
            ->set('name', 'New Admin')
            ->set('email', 'new-admin@example.com')
            ->set('role', UserRole::Admin->value)
            ->set('password', 'password123')
            ->call('save')
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseHas('users', ['email' => 'new-admin@example.com', 'role' => UserRole::Admin->value]);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'user.created']);
    }

    public function test_admin_can_update_a_users_profile_and_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['name' => 'Old Name', 'password' => 'old-password']);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $user])
            ->set('name', 'New Name')
            ->set('password', 'brand-new-password')
            ->call('save')
            ->assertRedirect(route('admin.users'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
    }

    public function test_admin_can_deactivate_a_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['is_active' => true]);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $user])
            ->set('isActive', false)
            ->call('save')
            ->assertRedirect(route('admin.users'));

        $this->assertFalse($user->refresh()->is_active);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $admin])
            ->set('role', UserRole::Client->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $admin])
            ->set('isActive', false)
            ->call('save');

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $admin])
            ->call('delete')
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_a_user_and_all_their_files_are_removed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();
        Storage::disk('local')->put('practice-logos/logo.png', 'fake-logo');
        $practice = Practice::factory()->create(['user_id' => $user->id, 'logo_path' => 'practice-logos/logo.png']);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);
        $submission = IntakeSubmission::factory()->create(['order_id' => $order->id]);
        Storage::disk('local')->put('intake/upload.pdf', 'fake-upload');
        $upload = IntakeUpload::factory()->create(['intake_submission_id' => $submission->id, 'storage_path' => 'intake/upload.pdf']);
        Storage::disk('local')->put('compliance/doc.pdf', 'fake-doc');
        $document = GeneratedDocument::factory()->create(['order_id' => $order->id, 'pdf_storage_path' => 'compliance/doc.pdf']);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $user])
            ->call('delete')
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('practices', ['id' => $practice->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('intake_uploads', ['id' => $upload->id]);
        $this->assertDatabaseMissing('generated_documents', ['id' => $document->id]);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'user.deleted']);

        Storage::disk('local')->assertMissing('practice-logos/logo.png');
        Storage::disk('local')->assertMissing('intake/upload.pdf');
        Storage::disk('local')->assertMissing('compliance/doc.pdf');
    }

    // ── Practice / OSHA locations ────────────────────────────────────────

    public function test_admin_can_edit_a_users_practice_profile(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create();
        $practice = Practice::factory()->create(['user_id' => $client->id, 'name' => 'Old Practice Name']);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $client])
            ->set('practiceName', 'New Practice Name')
            ->set('practiceIsLocked', true)
            ->call('save')
            ->assertRedirect(route('admin.users'));

        $practice->refresh();
        $this->assertSame('New Practice Name', $practice->name);
        $this->assertTrue($practice->is_profile_locked);
        $this->assertNotNull($practice->locked_at);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'practice.updated']);
    }

    public function test_admin_can_add_an_osha_location(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create();
        $practice = Practice::factory()->create(['user_id' => $client->id]);

        $component = Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $client])
            ->call('addOshaLocation')
            ->set('oshaLocations.0.name', 'New Satellite Office')
            ->call('saveOshaLocation', 0);

        $component->assertHasNoErrors();
        $this->assertDatabaseHas('osha_locations', ['practice_id' => $practice->id, 'name' => 'New Satellite Office']);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'osha_location.created']);
    }

    public function test_admin_can_edit_an_osha_location(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create();
        $practice = Practice::factory()->create(['user_id' => $client->id]);
        $location = OshaLocation::factory()->create(['practice_id' => $practice->id, 'name' => 'Old Location Name']);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $client])
            ->set('oshaLocations.0.name', 'Updated Location Name')
            ->call('saveOshaLocation', 0)
            ->assertHasNoErrors();

        $this->assertSame('Updated Location Name', $location->refresh()->name);
    }

    public function test_admin_can_delete_an_osha_location(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create();
        $practice = Practice::factory()->create(['user_id' => $client->id]);
        $location = OshaLocation::factory()->create(['practice_id' => $practice->id]);

        Livewire::actingAs($admin)
            ->test('admin.user-form', ['user' => $client])
            ->call('deleteOshaLocation', 0);

        $this->assertDatabaseMissing('osha_locations', ['id' => $location->id]);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'osha_location.deleted']);
    }

    // ── Orders ────────────────────────────────────────────────────────────

    public function test_admin_can_view_and_filter_the_orders_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create(['name' => 'Filterable Client']);
        $package = Package::factory()->create();
        Order::factory()->create(['user_id' => $client->id, 'package_id' => $package->id, 'status' => OrderStatus::Cancelled]);

        Livewire::actingAs($admin)
            ->test('admin.order-list')
            ->assertSee('Filterable Client')
            ->set('status', OrderStatus::Paid->value)
            ->assertDontSee('Filterable Client');
    }

    public function test_admin_can_update_an_orders_status_and_amount(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create();
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $client->id, 'package_id' => $package->id]);

        Livewire::actingAs($admin)
            ->test('admin.order-form', ['order' => $order])
            ->set('status', OrderStatus::Cancelled->value)
            ->set('amountPaid', '0')
            ->set('notes', 'Refunded per client request.')
            ->call('save')
            ->assertRedirect(route('admin.orders'));

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame('Refunded per client request.', $order->notes);
        $this->assertDatabaseHas('activity_logs', ['event_type' => 'order.updated']);
    }

    public function test_admin_can_delete_an_order_and_its_files_are_removed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $client = User::factory()->create();
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $client->id, 'package_id' => $package->id]);
        Storage::disk('local')->put('compliance/doc.pdf', 'fake-doc');
        $document = GeneratedDocument::factory()->create(['order_id' => $order->id, 'pdf_storage_path' => 'compliance/doc.pdf']);

        Livewire::actingAs($admin)
            ->test('admin.order-form', ['order' => $order])
            ->call('delete')
            ->assertRedirect(route('admin.orders'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('generated_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing('compliance/doc.pdf');
    }
}
