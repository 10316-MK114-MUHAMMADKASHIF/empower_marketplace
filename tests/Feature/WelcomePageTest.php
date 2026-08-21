<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    private function seedActivePackage(): void
    {
        Package::factory()->create(['slug' => 'essential', 'is_active' => true]);
    }

    public function test_guest_can_see_a_select_package_link_on_the_pricing_page(): void
    {
        $this->seedActivePackage();

        $response = $this->withoutVite()->get('/');

        $response->assertOk();
        $response->assertSee('Select Package');
    }

    public function test_authenticated_user_can_see_a_select_package_link_on_the_pricing_page(): void
    {
        $this->seedActivePackage();
        $user = User::factory()->create();

        $response = $this->withoutVite()->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Select Package');
    }
}
