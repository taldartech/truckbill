<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\TruckType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckTypeTest extends TestCase
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
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    protected function makeUser(string $role): User
    {
        $company = Company::create([
            'name' => 'Company ' . $role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $roleId = UserRole::where('name', $role)->value('id');

        $user = User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);

        $company->user_id = $user->id;
        $company->save();

        return $user;
    }

    public function test_admin_can_view_truck_types_index(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('master.truck-types.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_and_store_truck_type(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post(route('master.truck-types.store'), [
            'name' => 'Flatbed',
        ]);

        $response->assertRedirect(route('master.truck-types.index'));
        $this->assertDatabaseHas('truck_types', ['name' => 'Flatbed']);
    }

    public function test_admin_can_edit_and_update_truck_type(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($admin)->put(route('master.truck-types.update', $truckType), [
            'name' => 'Flatbed XL',
        ]);

        $response->assertRedirect(route('master.truck-types.index'));
        $this->assertDatabaseHas('truck_types', ['id' => $truckType->id, 'name' => 'Flatbed XL']);
    }

    public function test_admin_can_delete_truck_type(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($admin)->delete(route('master.truck-types.destroy', $truckType));

        $response->assertRedirect(route('master.truck-types.index'));
        $this->assertDatabaseMissing('truck_types', ['id' => $truckType->id]);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        $admin = $this->makeUser('admin');
        TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($admin)->post(route('master.truck-types.store'), [
            'name' => 'Flatbed',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('truck_types', 1);
    }

    public function test_moderator_can_view_and_create_but_not_edit_truck_type(): void
    {
        $moderator = $this->makeUser('moderator');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $this->actingAs($moderator)->get(route('master.truck-types.index'))->assertOk();
        $this->actingAs($moderator)->get(route('master.truck-types.create'))->assertOk();
        $this->actingAs($moderator)->post(route('master.truck-types.store'), ['name' => 'Tanker'])
            ->assertRedirect(route('master.truck-types.index'));

        $this->actingAs($moderator)->get(route('master.truck-types.edit', $truckType))->assertForbidden();
        $this->actingAs($moderator)->put(route('master.truck-types.update', $truckType), ['name' => 'Tanker XL'])
            ->assertForbidden();
        $this->actingAs($moderator)->delete(route('master.truck-types.destroy', $truckType))->assertForbidden();
    }
}
