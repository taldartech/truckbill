<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\Truck;
use App\Models\TruckType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        $response->assertViewIs('master.truck-types');
    }

    public function test_ajax_index_returns_datatable_json_with_admin_action_buttons(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($admin)->getJson(route('master.truck-types.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => ['id', 'name', 'actions'],
            ],
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals($truckType->id, $data[0]['id']);
        $this->assertEquals('Flatbed', $data[0]['name']);
        $this->assertStringContainsString('btn-action-edit', $data[0]['actions']);
        $this->assertStringContainsString('btn-action-delete', $data[0]['actions']);
        $this->assertStringContainsString(route('master.truck-types.edit', $truckType), $data[0]['actions']);
        $this->assertStringContainsString(route('master.truck-types.destroy', $truckType), $data[0]['actions']);
    }

    public function test_ajax_index_returns_empty_action_buttons_for_non_admin(): void
    {
        $moderator = $this->makeUser('moderator');
        TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($moderator)->getJson(route('master.truck-types.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertSame('', $data[0]['actions']);
    }

    public function test_ajax_index_search_filtering(): void
    {
        $admin = $this->makeUser('admin');
        TruckType::create(['name' => 'Open Body']);
        TruckType::create(['name' => 'Closed Container']);

        $response = $this->actingAs($admin)->getJson(route('master.truck-types.index', [
            'search' => ['value' => 'Container'],
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $this->assertEquals(2, $response->json('recordsTotal'));
        $this->assertEquals(1, $response->json('recordsFiltered'));
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Closed Container', $data[0]['name']);
    }

    public function test_ajax_index_column_search_and_edge_cases(): void
    {
        $admin = $this->makeUser('admin');
        TruckType::create(['name' => 'Open Body']);
        TruckType::create(['name' => 'Closed Container']);

        // Test column 1 match + column 0 non-empty (index !== 1) + column 2 empty search value
        $response = $this->actingAs($admin)->getJson(route('master.truck-types.index', [
            'columns' => [
                0 => ['search' => ['value' => 'ignored_value']],
                1 => ['search' => ['value' => 'Open']],
                2 => ['search' => ['value' => '']],
            ],
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));
        $this->assertEquals('Open Body', $response->json('data.0.name'));
    }

    public function test_ajax_index_ordering_branches_and_pagination(): void
    {
        $admin = $this->makeUser('admin');
        $t1 = TruckType::create(['name' => 'Alpha Type']);
        $t2 = TruckType::create(['name' => 'Beta Type']);

        // Case 0: orderColumn === 0, dir desc
        $resp0 = $this->actingAs($admin)->getJson(route('master.truck-types.index', [
            'order' => [
                0 => ['column' => 0, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $resp0->assertOk();
        $this->assertEquals($t2->id, $resp0->json('data.0.id'));

        // Case 1: orderColumn === 1, dir asc
        $resp1 = $this->actingAs($admin)->getJson(route('master.truck-types.index', [
            'order' => [
                0 => ['column' => 1, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $resp1->assertOk();
        $this->assertEquals('Alpha Type', $resp1->json('data.0.name'));

        // Case default: orderColumn === 99 (hits default switch branch: orderBy('id', 'asc'))
        $respDef = $this->actingAs($admin)->getJson(route('master.truck-types.index', [
            'order' => [
                0 => ['column' => 99, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respDef->assertOk();
        $this->assertEquals($t1->id, $respDef->json('data.0.id'));

        // No order.0 parameter: hits else branch: orderBy('name', 'asc')
        $respNoOrder = $this->actingAs($admin)->getJson(route('master.truck-types.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $respNoOrder->assertOk();
        $this->assertEquals('Alpha Type', $respNoOrder->json('data.0.name'));

        // Pagination: start = 1, length = 1
        $respPage = $this->actingAs($admin)->getJson(route('master.truck-types.index', [
            'start' => 1,
            'length' => 1,
            'order' => [
                0 => ['column' => 1, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respPage->assertOk();
        $this->assertCount(1, $respPage->json('data'));
        $this->assertEquals('Beta Type', $respPage->json('data.0.name'));

        // Pagination: length = 0 or negative (hits $length = $totalRecords)
        $respAll = $this->actingAs($admin)->getJson(route('master.truck-types.index', [
            'length' => 0,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $respAll->assertOk();
        $this->assertCount(2, $respAll->json('data'));
    }

    public function test_admin_can_view_create_form(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('master.truck-types.create'));

        $response->assertOk();
        $response->assertViewIs('master.truck-types-form');
        $response->assertViewHas('truckType', null);
    }

    public function test_admin_can_create_and_store_truck_type(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post(route('master.truck-types.store'), [
            'name' => 'Flatbed',
        ]);

        $response->assertRedirect(route('master.truck-types.index'));
        $response->assertSessionHas('success', 'Truck type created successfully.');
        $this->assertDatabaseHas('truck_types', ['name' => 'Flatbed']);
    }

    public function test_store_validation_errors(): void
    {
        $admin = $this->makeUser('admin');
        TruckType::create(['name' => 'Flatbed']);

        // Missing / empty name
        $this->actingAs($admin)->post(route('master.truck-types.store'), [
            'name' => '',
        ])->assertSessionHasErrors('name');

        // Name exceeding 100 characters
        $this->actingAs($admin)->post(route('master.truck-types.store'), [
            'name' => str_repeat('A', 101),
        ])->assertSessionHasErrors('name');

        // Duplicate name
        $response = $this->actingAs($admin)->post(route('master.truck-types.store'), [
            'name' => 'Flatbed',
        ]);
        $response->assertSessionHasErrors(['name' => 'This truck type name already exists.']);
        $this->assertDatabaseCount('truck_types', 1);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($admin)->get(route('master.truck-types.edit', $truckType));

        $response->assertOk();
        $response->assertViewIs('master.truck-types-form');
        $response->assertViewHas('truckType', function ($viewTruckType) use ($truckType) {
            return $viewTruckType->id === $truckType->id;
        });
    }

    public function test_admin_can_edit_and_update_truck_type(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($admin)->put(route('master.truck-types.update', $truckType), [
            'name' => 'Flatbed XL',
        ]);

        $response->assertRedirect(route('master.truck-types.index'));
        $response->assertSessionHas('success', 'Truck type updated successfully.');
        $this->assertDatabaseHas('truck_types', ['id' => $truckType->id, 'name' => 'Flatbed XL']);
    }

    public function test_update_validation_errors_and_unique_ignore(): void
    {
        $admin = $this->makeUser('admin');
        $t1 = TruckType::create(['name' => 'Flatbed']);
        $t2 = TruckType::create(['name' => 'Trailer']);

        // Updating with same name ignores self ID and succeeds
        $this->actingAs($admin)->put(route('master.truck-types.update', $t1), [
            'name' => 'Flatbed',
        ])->assertRedirect(route('master.truck-types.index'));

        // Updating with another truck type's name triggers unique error
        $response = $this->actingAs($admin)->put(route('master.truck-types.update', $t1), [
            'name' => 'Trailer',
        ]);
        $response->assertSessionHasErrors(['name' => 'This truck type name already exists.']);

        // Empty name
        $this->actingAs($admin)->put(route('master.truck-types.update', $t1), [
            'name' => '',
        ])->assertSessionHasErrors('name');

        // Name > 100 characters
        $this->actingAs($admin)->put(route('master.truck-types.update', $t1), [
            'name' => str_repeat('B', 101),
        ])->assertSessionHasErrors('name');
    }

    public function test_admin_can_delete_truck_type(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $response = $this->actingAs($admin)->delete(route('master.truck-types.destroy', $truckType), [
            'delete_remark' => 'No longer used',
        ]);

        $response->assertRedirect(route('master.truck-types.index'));
        $response->assertSessionHas('success', 'Truck type deleted successfully!');
        $this->assertSoftDeleted('truck_types', [
            'id' => $truckType->id,
            'deleted_by' => $admin->id,
            'deleted_remark' => 'No longer used',
        ]);
    }

    public function test_destroy_requires_delete_remark(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        // Missing remark
        $response = $this->actingAs($admin)->delete(route('master.truck-types.destroy', $truckType), []);
        $response->assertSessionHasErrors('delete_remark');
        $this->assertNotSoftDeleted('truck_types', ['id' => $truckType->id]);

        // Remark too long (>1000 characters)
        $responseLong = $this->actingAs($admin)->delete(route('master.truck-types.destroy', $truckType), [
            'delete_remark' => str_repeat('R', 1001),
        ]);
        $responseLong->assertSessionHasErrors('delete_remark');
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

    public function test_unauthenticated_user_cannot_access_truck_types(): void
    {
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $this->get(route('master.truck-types.index'))->assertRedirect(route('login'));
        $this->get(route('master.truck-types.create'))->assertRedirect(route('login'));
        $this->post(route('master.truck-types.store'), ['name' => 'New Type'])->assertRedirect(route('login'));
        $this->get(route('master.truck-types.edit', $truckType))->assertRedirect(route('login'));
        $this->put(route('master.truck-types.update', $truckType), ['name' => 'Update'])->assertRedirect(route('login'));
        $this->delete(route('master.truck-types.destroy', $truckType))->assertRedirect(route('login'));
    }

    public function test_truck_type_model_relationships_and_properties(): void
    {
        $admin = $this->makeUser('admin');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        // 1. Fillable and properties
        $this->assertEquals(['name'], $truckType->getFillable());
        $this->assertEquals('Flatbed', $truckType->name);

        // 2. HasMany relationship to Truck
        $relation = $truckType->trucks();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('truck_type', $relation->getForeignKeyName());
        $this->assertEquals('name', $relation->getLocalKeyName());

        $truck = Truck::create([
            'truck_number' => 'MH12AB9999',
            'truck_type' => 'Flatbed',
            'client_id' => $admin->company_id,
        ]);

        $this->assertTrue($truckType->trucks->contains($truck));
        $this->assertEquals(1, $truckType->trucks()->count());

        // 3. Soft deletes and restore
        $truckType->delete();
        $this->assertSoftDeleted('truck_types', ['id' => $truckType->id]);
        $truckType->restore();
        $this->assertNotSoftDeleted('truck_types', ['id' => $truckType->id]);
    }


    public function test_update_truck_type_request_authorization_and_rules_isolated(): void
    {
        $admin = $this->makeUser('admin');
        $moderator = $this->makeUser('moderator');
        $truckType = TruckType::create(['name' => 'Flatbed']);

        $request = new \App\Http\Requests\UpdateTruckTypeRequest();
        $request->setRouteResolver(function () use ($truckType) {
            $route = new \Illuminate\Routing\Route('PUT', 'master/truck-types/{truck_type}', []);
            $route->bind(request());
            $route->setParameter('truck_type', $truckType);
            return $route;
        });

        // 1. Unauthenticated -> authorize returns false
        auth()->logout();
        $this->assertFalse($request->authorize());

        // 2. Moderator -> authorize returns false
        $this->actingAs($moderator);
        $this->assertFalse($request->authorize());

        // 3. Admin -> authorize returns true
        $this->actingAs($admin);
        $this->assertTrue($request->authorize());

        // 4. Rules & Messages
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:100', $rules['name']);

        $messages = $request->messages();
        $this->assertEquals('This truck type name already exists.', $messages['name.unique']);
    }

    public function test_store_truck_type_request_authorization_and_rules_isolated(): void
    {
        $admin = $this->makeUser('admin');
        $accountant = $this->makeUser('accountant');
        $request = new \App\Http\Requests\StoreTruckTypeRequest();

        // 1. Unauthenticated -> returns false
        auth()->logout();
        $this->assertFalse($request->authorize());

        // 2. Non-company-panel role (accountant) -> returns false
        $this->actingAs($accountant);
        $this->assertFalse($request->authorize());

        // 3. Admin -> returns true
        $this->actingAs($admin);
        $this->assertTrue($request->authorize());

        // 4. Rules & Messages
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:100', $rules['name']);

        $messages = $request->messages();
        $this->assertEquals('This truck type name already exists.', $messages['name.unique']);
    }
}


