<?php

namespace Tests\Feature\Drivers;

use App\Models\Company;
use App\Models\Driver;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Main Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    public function test_index_returns_view(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/drivers');

        $response->assertOk()->assertViewIs('drivers.index');
    }

    public function test_store_creates_driver_with_company_scope(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/drivers', [
            'name' => 'John Driver',
            'mobile' => '9876543210',
            'license_number' => 'LIC12345',
            'address' => '123 Street',
        ]);

        $response->assertRedirect(route('drivers.index'));
        $this->assertDatabaseHas('drivers', [
            'name' => 'John Driver',
            'mobile' => '9876543210',
            'company_id' => $admin->company_id,
        ]);
    }

    public function test_store_rejects_duplicate_mobile_in_company(): void
    {
        $admin = $this->makeAdmin();

        Driver::create([
            'name' => 'Existing',
            'mobile' => '9876543210',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)->from('/drivers/create')->post('/drivers', [
            'name' => 'New Driver',
            'mobile' => '9876543210',
        ]);

        $response->assertRedirect('/drivers/create');
        $response->assertSessionHasErrors(['mobile']);
    }

    public function test_update_changes_driver_and_uniqueness_rules(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Old Name',
            'mobile' => '9876543210',
            'company_id' => $admin->company_id,
            'license_number' => 'LIC123',
        ]);

        $response = $this->actingAs($admin)->put("/drivers/{$driver->id}", [
            'name' => 'New Name',
            'mobile' => '9998887776',
            'license_number' => 'LIC999',
            'address' => 'New Address',
        ]);

        $response->assertRedirect(route('drivers.index'));
        $driver->refresh();
        $this->assertSame('New Name', $driver->name);
        $this->assertSame('9998887776', $driver->mobile);
        $this->assertSame('LIC999', $driver->license_number);
        $this->assertSame('New Address', $driver->address);
    }

    public function test_destroy_soft_deletes_driver(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Delete Me',
            'mobile' => '9990001111',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)->delete("/drivers/{$driver->id}", [
            'delete_remark' => 'Driver left',
        ]);

        $response->assertRedirect(route('drivers.index'));
        $this->assertSoftDeleted('drivers', ['id' => $driver->id, 'deleted_remark' => 'Driver left']);
    }

    public function test_get_drivers_api_returns_company_scoped_list(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin(); // different company

        Driver::create([
            'name' => 'In Company',
            'mobile' => '9000000001',
            'company_id' => $admin->company_id,
        ]);

        Driver::create([
            'name' => 'Other Company',
            'mobile' => '9000000002',
            'company_id' => $other->company_id,
        ]);

        $response = $this->actingAs($admin)->get('/api/drivers');

        $response->assertOk();
        $names = collect($response->json())->pluck('name');
        $this->assertTrue($names->contains('In Company'));
        $this->assertFalse($names->contains('Other Company'));
    }
}

