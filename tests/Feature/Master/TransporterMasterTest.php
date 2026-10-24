<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\User;
use App\Models\UserRole;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransporterMasterTest extends TestCase
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
        UserRole::create(['id' => 6, 'name' => 'transporter']);
    }

    protected function makeUser(string $role): User
    {
        $company = Company::create([
            'name' => 'Company '.$role,
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

    public function test_master_admin_can_manage_transporter_masters(): void
    {
        $masterAdmin = $this->makeUser('master_admin');

        $this->actingAs($masterAdmin)
            ->get(route('master.transporter-masters.index'))
            ->assertOk();

        $this->actingAs($masterAdmin)
            ->post(route('master.transporter-masters.store'), [
                'name' => 'Alpha Logistics',
                'email' => 'alpha@example.com',
                'pan_number' => 'ABCDE1234F',
                'gst_number' => null,
                'gst_type' => null,
                'pan_active' => '1',
                'address' => 'Main Road',
            ])
            ->assertRedirect(route('master.transporter-masters.index'));

        $transporterMaster = TransporterMaster::query()->where('pan_number', 'ABCDE1234F')->firstOrFail();

        $this->actingAs($masterAdmin)
            ->put(route('master.transporter-masters.update', $transporterMaster), [
                'name' => 'Alpha Logistics Pvt Ltd',
                'email' => 'alpha.updated@example.com',
                'pan_number' => 'ABCDE1234F',
                'gst_number' => null,
                'gst_type' => null,
                'pan_active' => '0',
                'address' => 'Updated Address',
            ])
            ->assertRedirect(route('master.transporter-masters.index'));

        $this->assertDatabaseHas('transporter_masters', [
            'id' => $transporterMaster->id,
            'name' => 'Alpha Logistics Pvt Ltd',
            'email' => 'alpha.updated@example.com',
            'pan_active' => 0,
        ]);
    }

    public function test_master_admin_can_store_transporter_master_without_email(): void
    {
        $masterAdmin = $this->makeUser('master_admin');

        $this->actingAs($masterAdmin)
            ->post(route('master.transporter-masters.store'), [
                'name' => 'No Email Transport',
                'pan_number' => 'ABCDE1234F',
                'gst_number' => null,
                'gst_type' => null,
                'pan_active' => '1',
                'address' => null,
            ])
            ->assertRedirect(route('master.transporter-masters.index'));

        $this->assertDatabaseHas('transporter_masters', [
            'name' => 'No Email Transport',
            'pan_number' => 'ABCDE1234F',
            'email' => null,
        ]);
    }

    public function test_admin_cannot_access_transporter_masters_module(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('master.transporter-masters.index'))
            ->assertForbidden();
    }

    public function test_master_admin_cannot_delete_master_linked_to_transporters(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        $company = Company::firstOrFail();
        $transporterMaster = TransporterMaster::factory()->create();

        Transporter::factory()->create([
            'company_id' => $company->id,
            'transporter_master_id' => $transporterMaster->id,
            'is_owner' => 0,
        ]);

        $this->actingAs($masterAdmin)
            ->delete(route('master.transporter-masters.destroy', $transporterMaster))
            ->assertRedirect(route('master.transporter-masters.index'));

        $this->assertDatabaseHas('transporter_masters', ['id' => $transporterMaster->id, 'deleted_at' => null]);
    }

    public function test_master_admin_can_delete_unlinked_transporter_master(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        $transporterMaster = TransporterMaster::factory()->create();

        $this->actingAs($masterAdmin)
            ->delete(route('master.transporter-masters.destroy', $transporterMaster))
            ->assertRedirect(route('master.transporter-masters.index'));

        $this->assertSoftDeleted('transporter_masters', ['id' => $transporterMaster->id]);
    }

    public function test_edit_page_displays_associated_companies(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        $transporterMaster = TransporterMaster::factory()->create();

        $companyA = Company::create([
            'name' => 'Alpha Transport Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $companyB = Company::create([
            'name' => 'Beta Logistics',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        Transporter::factory()->create([
            'company_id' => $companyA->id,
            'transporter_master_id' => $transporterMaster->id,
            'is_owner' => 0,
        ]);

        Transporter::factory()->create([
            'company_id' => $companyB->id,
            'transporter_master_id' => $transporterMaster->id,
            'is_owner' => 0,
        ]);

        $this->actingAs($masterAdmin)
            ->get(route('master.transporter-masters.edit', $transporterMaster))
            ->assertOk()
            ->assertSee('Associated Companies')
            ->assertSee('Alpha Transport Co')
            ->assertSee('Beta Logistics');
    }

    public function test_update_syncs_linked_portal_user(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        $transporterMaster = TransporterMaster::factory()->create([
            'name' => 'Portal Transporter',
            'email' => 'portal@example.com',
        ]);

        $portalUser = User::factory()->create([
            'name' => 'Portal Transporter',
            'email' => 'portal@example.com',
            'user_role_id' => UserRole::where('name', 'transporter')->value('id'),
            'company_id' => null,
            'transporter_master_id' => $transporterMaster->id,
        ]);

        $this->actingAs($masterAdmin)
            ->put(route('master.transporter-masters.update', $transporterMaster), [
                'name' => 'Portal Transporter Updated',
                'email' => 'portal.updated@example.com',
                'pan_number' => $transporterMaster->pan_number,
                'gst_number' => null,
                'gst_type' => null,
                'pan_active' => '1',
                'address' => null,
            ])
            ->assertRedirect(route('master.transporter-masters.index'));

        $portalUser->refresh();

        $this->assertSame('Portal Transporter Updated', $portalUser->name);
        $this->assertSame('portal.updated@example.com', $portalUser->email);
    }
public function test_name_is_required_when_creating_transporter_master(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    $response = $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => '',
            'pan_number' => 'ABCDE1234F',
            'pan_active' => '1',
        ]);

    $response->assertSessionHasErrors('name');

    $this->assertDatabaseMissing('transporter_masters', [
        'pan_number' => 'ABCDE1234F',
    ]);
}
public function test_invalid_pan_number_is_rejected(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    $response = $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => 'Test Transporter',
            'pan_number' => 'INVALID123',
            'pan_active' => '1',
        ]);

    $response->assertSessionHasErrors('pan_number');

    $this->assertDatabaseMissing('transporter_masters', [
        'pan_number' => 'INVALID123',
    ]);
}

public function test_duplicate_pan_number_is_rejected(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    TransporterMaster::factory()->create([
        'pan_number' => 'ABCDE1234F',
    ]);

    $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => 'Another Transporter',
            'pan_number' => 'ABCDE1234F',
            'pan_active' => '1',
        ])
        ->assertSessionHasErrors('pan_number');

    $this->assertDatabaseCount('transporter_masters', 1);
}

public function test_invalid_email_is_rejected(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => 'Test Transporter',
            'email' => 'not-an-email',
            'pan_number' => 'ABCDE1234F',
            'pan_active' => '1',
        ])
        ->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('transporter_masters', [
        'pan_number' => 'ABCDE1234F',
    ]);
}

public function test_duplicate_email_is_rejected(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    TransporterMaster::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => 'Another Transporter',
            'email' => 'existing@example.com',
            'pan_number' => 'FGHIJ5678K',
            'pan_active' => '1',
        ])
        ->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('transporter_masters', [
        'pan_number' => 'FGHIJ5678K',
    ]);
}

public function test_invalid_gst_number_is_rejected(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => 'Test Transporter',
            'pan_number' => 'ABCDE1234F',
            'gst_number' => 'INVALID-GST',
            'pan_active' => '1',
        ])
        ->assertSessionHasErrors('gst_number');
}

public function test_invalid_gst_type_is_rejected(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => 'Test Transporter',
            'pan_number' => 'ABCDE1234F',
            'gst_type' => 'invalid_type',
            'pan_active' => '1',
        ])
        ->assertSessionHasErrors('gst_type');
}

public function test_pan_active_is_required(): void
{
    $masterAdmin = $this->makeUser('master_admin');

    $this->actingAs($masterAdmin)
        ->post(route('master.transporter-masters.store'), [
            'name' => 'Test Transporter',
            'pan_number' => 'ABCDE1234F',
        ])
        ->assertSessionHasErrors('pan_active');
}

    public function test_address_longer_than_500_characters_is_rejected(): void
    {
        $masterAdmin = $this->makeUser('master_admin');

        $this->actingAs($masterAdmin)
            ->post(route('master.transporter-masters.store'), [
                'name' => 'Test Transporter',
                'pan_number' => 'ABCDE1234F',
                'pan_active' => '1',
                'address' => str_repeat('A', 501),
            ])
            ->assertSessionHasErrors('address');
    }

    public function test_master_admin_can_view_transporter_master_create_form(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        Country::create(['name' => 'India', 'code' => 'IN']);

        $response = $this->actingAs($masterAdmin)
            ->get(route('master.transporter-masters.create'));

        $response->assertOk();
        $response->assertViewIs('master.transporter-masters-form');
        $response->assertViewHas('transporterMaster', null);
        $this->assertTrue($response->viewData('associatedCompanies')->isEmpty());
        $this->assertNotEmpty($response->viewData('countries'));
    }

    public function test_transporter_masters_datatable_ajax_search_ordering_and_formatting(): void
    {
        $masterAdmin = $this->makeUser('master_admin');

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Maharashtra', 'code' => 'MH', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Mumbai', 'state_id' => $state->id, 'country_id' => $country->id]);

        $master1 = TransporterMaster::create([
            'name' => 'Alpha Express',
            'email' => 'alpha@express.com',
            'pan_number' => 'ABCDE1111A',
            'gst_number' => '27ABCDE1111A1Z5',
            'pan_active' => true,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $master2 = TransporterMaster::create([
            'name' => 'Beta Logistics',
            'email' => null,
            'pan_number' => 'BCDEF2222B',
            'gst_number' => null,
            'pan_active' => false,
            'country_id' => null,
            'state_id' => null,
            'city_id' => null,
        ]);

        // 1. Basic AJAX call without search or order (covers default ordering, location, null formatting, action buttons)
        $resDefault = $this->actingAs($masterAdmin)->getJson(
            route('master.transporter-masters.index', ['draw' => 1, 'start' => 0, 'length' => 10]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
        $resDefault->assertOk();
        $resDefault->assertJsonPath('recordsTotal', 2);
        $resDefault->assertJsonPath('recordsFiltered', 2);
        $data = $resDefault->json('data');
        $this->assertCount(2, $data);
        $this->assertStringContainsString('Mumbai, Maharashtra, India', $data[1]['location']);
        $this->assertEquals('-', $data[0]['location']);
        $this->assertEquals('-', $data[0]['email']);
        $this->assertEquals('-', $data[0]['gst_number']);
        $this->assertEquals('No', $data[0]['pan_active']);
        $this->assertEquals('Yes', $data[1]['pan_active']);
        $this->assertStringContainsString('btn-action-edit', $data[0]['actions']);
        $this->assertStringContainsString('btn-action-delete', $data[0]['actions']);

        // 2. Search filtering
        $resSearch = $this->actingAs($masterAdmin)->getJson(
            route('master.transporter-masters.index', [
                'draw' => 2,
                'search' => ['value' => 'Alpha'],
            ]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
        $resSearch->assertOk();
        $resSearch->assertJsonPath('recordsFiltered', 1);
        $this->assertEquals('Alpha Express', $resSearch->json('data.0.name'));

        // 3. Ordering by valid column (column 1 = name, asc)
        $resOrder = $this->actingAs($masterAdmin)->getJson(
            route('master.transporter-masters.index', [
                'draw' => 3,
                'order' => [
                    ['column' => 1, 'dir' => 'asc'],
                ],
            ]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
        $resOrder->assertOk();
        $this->assertEquals('Alpha Express', $resOrder->json('data.0.name'));
        $this->assertEquals('Beta Logistics', $resOrder->json('data.1.name'));

        // 4. Ordering with fallback column index (column 99)
        $resFallback = $this->actingAs($masterAdmin)->getJson(
            route('master.transporter-masters.index', [
                'draw' => 4,
                'order' => [
                    ['column' => 99, 'dir' => 'desc'],
                ],
            ]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
        $resFallback->assertOk();
        $this->assertEquals($master2->id, $resFallback->json('data.0.id'));
    }
}

