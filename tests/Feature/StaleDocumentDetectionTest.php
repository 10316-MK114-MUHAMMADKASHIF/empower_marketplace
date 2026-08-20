<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Models\Order;
use App\Models\OshaLocation;
use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaleDocumentDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_locked_practice_marks_its_documents_stale(): void
    {
        $user = User::factory()->create();
        $practice = Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);
        $document = GeneratedDocument::factory()->completed()->create(['order_id' => $order->id]);

        $practice->update(['address' => '999 New Address Ave']);

        $this->assertTrue($document->fresh()->is_stale);
        $this->assertSame('practice_profile_updated', $document->fresh()->stale_reason);
    }

    public function test_editing_an_unlocked_practice_does_not_mark_documents_stale(): void
    {
        $user = User::factory()->create();
        $practice = Practice::factory()->create(['user_id' => $user->id, 'is_profile_locked' => false]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);
        $document = GeneratedDocument::factory()->completed()->create(['order_id' => $order->id]);

        $practice->update(['address' => '999 New Address Ave']);

        $this->assertFalse($document->fresh()->is_stale);
    }

    public function test_editing_unrelated_practice_field_does_not_mark_documents_stale(): void
    {
        $user = User::factory()->create();
        $practice = Practice::factory()->locked()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);
        $document = GeneratedDocument::factory()->completed()->create(['order_id' => $order->id]);

        $practice->update(['locked_at' => now()]);

        $this->assertFalse($document->fresh()->is_stale);
    }

    public function test_editing_an_osha_location_marks_its_document_stale(): void
    {
        $user = User::factory()->create();
        $practice = Practice::factory()->locked()->create(['user_id' => $user->id]);
        $location = OshaLocation::factory()->create(['practice_id' => $practice->id]);
        $package = Package::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $package->id]);
        $document = GeneratedDocument::factory()->completed()->create([
            'order_id' => $order->id,
            'osha_location_id' => $location->id,
        ]);

        $location->update(['waste_hauler' => 'New Hauler Co']);

        $this->assertTrue($document->fresh()->is_stale);
        $this->assertSame('osha_location_updated', $document->fresh()->stale_reason);
    }
}
