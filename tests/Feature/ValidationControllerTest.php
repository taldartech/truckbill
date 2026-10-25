<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        $role = UserRole::create(['id' => 3, 'name' => 'admin']);

        $this->company = Company::create([
            'name' => 'Validation Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);

        CompanySubscription::create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $this->user = User::factory()->create([
            'user_role_id' => $role->id,
            'company_id' => $this->company->id,
        ]);

        $this->company->update(['user_id' => $this->user->id]);
    }

    public function test_check_gst_number_returns_available_when_not_in_use(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/validate/gst-number', [
            'gst_number' => '24ABCDE1234F1Z5',
        ]);

        $response->assertOk()
            ->assertJson([
                'available' => true,
                'message' => 'GST Number is available',
            ]);
    }

    public function test_check_gst_number_returns_unavailable_when_in_use_in_same_company(): void
    {
        $consignee = Consignee::create([
            'company_id' => $this->company->id,
            'name' => 'Existing Consignee',
            'gst_number' => '24ABCDE1234F1Z5',
        ]);

        // Same GST number in same company
        $response = $this->actingAs($this->user)->postJson('/api/validate/gst-number', [
            'gst_number' => '24abcde1234f1z5', // lowercase to test strtoupper
        ]);

        $response->assertOk()
            ->assertJson([
                'available' => false,
                'message' => 'GST Number already in use',
            ]);

        // With exclude_id matching existing consignee, should be available
        $excludeResponse = $this->actingAs($this->user)->postJson('/api/validate/gst-number', [
            'gst_number' => '24ABCDE1234F1Z5',
            'exclude_id' => $consignee->id,
        ]);

        $excludeResponse->assertOk()
            ->assertJson([
                'available' => true,
                'message' => 'GST Number is available',
            ]);
    }

    public function test_check_pan_number_returns_available_when_not_in_use(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/validate/pan-number', [
            'pan_number' => 'ABCDE1234F',
        ]);

        $response->assertOk()
            ->assertJson([
                'available' => true,
                'message' => 'PAN Number is available',
            ]);
    }

    public function test_check_pan_number_returns_unavailable_when_in_use_in_same_company(): void
    {
        $consignee = Consignee::create([
            'company_id' => $this->company->id,
            'name' => 'Existing Consignee PAN',
            'pan_number' => 'ABCDE1234F',
        ]);

        // Same PAN in same company
        $response = $this->actingAs($this->user)->postJson('/api/validate/pan-number', [
            'pan_number' => 'abcde1234f', // lowercase to test strtoupper
        ]);

        $response->assertOk()
            ->assertJson([
                'available' => false,
                'message' => 'PAN Number already in use',
            ]);

        // With exclude_id matching existing consignee, should be available
        $excludeResponse = $this->actingAs($this->user)->postJson('/api/validate/pan-number', [
            'pan_number' => 'ABCDE1234F',
            'exclude_id' => $consignee->id,
        ]);

        $excludeResponse->assertOk()
            ->assertJson([
                'available' => true,
                'message' => 'PAN Number is available',
            ]);
    }
}
