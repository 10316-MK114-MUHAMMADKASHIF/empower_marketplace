<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Practice;
use App\Models\User;
use Database\Seeders\QuestionnaireSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HeaderAccountMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(QuestionnaireSeeder::class);
    }

    private function fakeSuccessfulCharge(): void
    {
        Http::fake([
            config('services.clover_mtbc.base_url') => Http::response([
                'status' => true,
                'message' => 'Payment Successful',
                'data' => ['id' => 'TEST_TXN_ID', 'amount' => 1, 'paid' => true, 'status' => 'succeeded'],
            ]),
        ]);
    }

    public function test_guest_sees_login_link(): void
    {
        Livewire::test('header-account-menu')
            ->assertSee('Login')
            ->assertDontSee('Log out');
    }

    public function test_authenticated_user_sees_account_menu(): void
    {
        $user = User::factory()->create(['name' => 'Jane Provider']);

        Livewire::actingAs($user)
            ->test('header-account-menu')
            ->assertSee('Jane Provider')
            ->assertDontSee('Login');
    }

    /**
     * Guest checkout (pay() in ⚡portal.blade.php) logs the new account in mid-request, with no
     * page navigation — <livewire:header-account-menu /> lives in the layout, outside the portal
     * component, so it only learns about the new session via this dispatched event. Without it,
     * the header keeps showing "Login" until the browser does a full page reload.
     */
    public function test_guest_paying_dispatches_the_event_the_header_menu_listens_for(): void
    {
        $this->fakeSuccessfulCharge();
        Mail::fake();

        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('accountName', 'Jane Provider')
            ->set('accountEmail', 'jane@practice.com')
            ->set('billingAddress1', '7 Clyde Road')
            ->set('billingCity', 'Somerset')
            ->set('billingState', 'NJ')
            ->set('billingZip', '08873')
            ->call('pay', 'Jane Provider', '4242 4242 4242 4242', '12/27', '123', true)
            ->assertDispatched('user-logged-in');
    }

    public function test_paying_while_already_authenticated_does_not_dispatch_the_event(): void
    {
        $this->fakeSuccessfulCharge();
        Mail::fake();

        $user = User::factory()->create();
        Practice::factory()->create(['user_id' => $user->id]);

        $package = Package::factory()->create(['slug' => 'essential', 'annual_price' => 999, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test('portal')
            ->set('selectedPackageId', $package->id)
            ->set('billingAddress1', '7 Clyde Road')
            ->set('billingCity', 'Somerset')
            ->set('billingState', 'NJ')
            ->set('billingZip', '08873')
            ->call('pay', 'Jane Provider', '4242 4242 4242 4242', '12/27', '123', true)
            ->assertNotDispatched('user-logged-in');
    }
}
