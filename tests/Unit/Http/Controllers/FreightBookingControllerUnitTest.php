<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\FreightBookingController;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class FreightBookingControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([1 => 'master_admin', 2 => 'super_admin', 3 => 'admin'] as $id => $name) {
            UserRole::create(['id' => $id, 'name' => $name]);
        }
    }

    public function test_redirect_to_freight_bookings_index_without_flash(): void
    {
        $company = Company::create([
            'name' => 'FB Unit Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin);

        $controller = app(FreightBookingController::class);
        $method = new ReflectionMethod(FreightBookingController::class, 'redirectToFreightBookingsIndex');
        $method->setAccessible(true);

        $request = Request::create('/freight_bookings', 'GET');
        $response = $method->invoke($controller, $request, null, null);

        $this->assertFalse($response->getSession()->has('success'));
        $this->assertStringContainsString(route('freight_bookings.index'), $response->getTargetUrl());
    }
}
