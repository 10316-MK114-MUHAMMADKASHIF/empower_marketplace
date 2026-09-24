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
        $response->assertSeeText('Select Package');
    }

    public function test_authenticated_user_can_see_a_select_package_link_on_the_pricing_page(): void
    {
        $this->seedActivePackage();
        $user = User::factory()->create();

        $response = $this->withoutVite()->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSeeText('Select Package');
    }

    /**
     * The pricing cards' "Empower Provides" / "You Provide" copy is fixed marketing content
     * (hardcoded in welcome.blade.php), independent of Package.features — which is still used
     * elsewhere (e.g. the dashboard's "Services included" line) — so an admin editing a
     * package's features must not change what the pricing page shows.
     */
    public function test_pricing_card_features_are_the_fixed_marketing_copy_not_the_package_record(): void
    {
        Package::factory()->create([
            'slug' => 'essential',
            'is_active' => true,
            'features' => ['A custom feature set by the admin', 'Another admin-defined feature'],
        ]);

        $response = $this->withoutVite()->get('/');

        $response->assertOk();
        $response->assertDontSee('A custom feature set by the admin');
        $response->assertDontSee('Another admin-defined feature');
        $response->assertSee('Empower Provides');
        $response->assertSee('Exclusions Screening');
    }

    public function test_an_unmatched_url_redirects_to_the_home_page(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertRedirect(route('home'));
    }
}
