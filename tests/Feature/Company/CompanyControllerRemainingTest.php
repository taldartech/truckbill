<?php

namespace Tests\Feature\Company;

use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyControllerRemainingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
        UserRole::firstOrCreate(['id' => 4], ['name' => 'moderator']);
        UserRole::firstOrCreate(['id' => 5], ['name' => 'accountant']);
    }

    protected function makeUser(string $role): User
    {
        $roleId = UserRole::where('name', $role)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => null,
        ]);
    }

    protected function createCompanyFor(User $user, array $attrs = []): Company
    {
        static $counter = 100;
        $counter++;

        return Company::create(array_merge([
            'name' => 'Test Company ' . $counter,
            'short_name' => 'TC' . $counter . strtoupper(substr(md5((string) $counter), 0, 4)),
            'user_id' => $user->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
            'is_blocked' => false,
            'gst_number' => '24AAACH' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT) . 'R1ZX',
            'pan_number' => 'AAACH' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT) . 'R',
            'contact_number' => '98' . str_pad((string) $counter, 8, '0', STR_PAD_LEFT),
            'contact_person' => 'Contact ' . $counter,
            'address' => 'Road ' . $counter . ' Industrial Area',
            'bank' => 'HDFC BANK',
            'account_number' => '12345678' . $counter,
            'ifsc' => 'HDFC0001234',
        ], $attrs));
    }

    public function test_company_index_datatables_ajax_filters_search_and_sorting(): void
    {
        $super = $this->makeUser('super_admin');
        $master = $this->makeUser('master_admin');

        $co1 = $this->createCompanyFor($super, [
            'name' => 'Alpha Logistics',
            'short_name' => 'ALPHALOG',
            'gst_number' => '24AAACH7409R1Z1',
            'pan_number' => 'AAACH7401R',
            'contact_number' => '9998887771',
            'address' => '100 Alpha Road',
            'contact_person' => 'Alpha Person',
            'is_blocked' => false,
        ]);

        $co2 = $this->createCompanyFor($super, [
            'name' => 'Beta Freight',
            'short_name' => 'BETAFR',
            'gst_number' => '24AAACH7409R1Z2',
            'pan_number' => 'AAACH7402R',
            'contact_number' => '9998887772',
            'address' => '200 Beta Street',
            'contact_person' => 'Beta Person',
            'is_blocked' => true,
        ]);

        // 1. AJAX as super admin (only owned companies, has enter/edit/delete buttons)
        $superResp = $this->actingAs($super)->getJson(route('company.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $superResp->assertOk();
        $this->assertCount(2, $superResp->json('data'));
        $this->assertStringContainsString('btn-action-edit', $superResp->json('data.0.actions'));
        $this->assertStringContainsString('btn-action-delete', $superResp->json('data.0.actions'));

        // 2. AJAX as master admin (has block/unblock action buttons)
        $masterResp = $this->actingAs($master)->getJson(route('company.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $masterResp->assertOk();
        $this->assertStringContainsString('btn-action-restore', $masterResp->json('data.0.actions') . $masterResp->json('data.1.actions'));

        // 3. Global search
        foreach (['Alpha', '24AAACH7409R1Z1', 'AAACH7401R', '9998887771', 'Alpha Road', 'Alpha Person'] as $term) {
            $searchResp = $this->actingAs($super)->getJson(route('company.index') . '?' . http_build_query([
                'search' => ['value' => $term],
            ]), ['X-Requested-With' => 'XMLHttpRequest']);
            $searchResp->assertOk();
            $this->assertCount(1, $searchResp->json('data'));
        }

        // 4. Column-specific search
        $colSearchResp = $this->actingAs($super)->getJson(route('company.index') . '?' . http_build_query([
            'columns' => [
                ['name' => 'name', 'search' => ['value' => 'Beta']],
                ['name' => 'gst_number', 'search' => ['value' => 'R1Z2']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $colSearchResp->assertOk();
        $this->assertCount(1, $colSearchResp->json('data'));

        // 5. Sorting across all sortable columns
        foreach (['name', 'gst_number', 'pan_number', 'address', 'contact_person', 'contact_number', 'unknown_col'] as $colName) {
            $sortResp = $this->actingAs($super)->getJson(route('company.index') . '?' . http_build_query([
                'order' => [['column' => 0, 'dir' => 'asc']],
                'columns' => [['name' => $colName]],
            ]), ['X-Requested-With' => 'XMLHttpRequest']);
            $sortResp->assertOk();
        }
    }

    public function test_company_store_with_files_and_duplicate_validations(): void
    {
        Storage::fake('public');
        $super = $this->makeUser('super_admin');
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $india->id]);
        $city = City::create(['name' => 'Surat', 'state_id' => $state->id, 'country_id' => $india->id]);

        // 1. Successful store with logo and digital signature
        $logo = UploadedFile::fake()->image('company_logo.jpg');
        $signature = UploadedFile::fake()->image('signature.png');

        $response = $this->actingAs($super)->post(route('company.store'), [
            'name' => 'Zenith Global Transport',
            'short_name' => 'ZENITH',
            'gst_number' => '24AAACH7409R1ZX',
            'pan_number' => 'AAACH7409R',
            'gst_scheme' => 'forward charge',
            'gst_type' => 'regular',
            'gst_slab' => config('payments.gst_slabs')[0],
            'contact_number' => '9876543210',
            'contact_person' => 'Sanjay Patel',
            'address' => 'GIDC Industrial Area',
            'country_id' => $india->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pin_code' => '395006',
            'bank' => 'STATE BANK OF INDIA',
            'account_number' => '30012345678',
            'ifsc' => 'sbin0001234', // test lowercase conversion
            'company_logo' => $logo,
            'digital_signature' => $signature,
        ]);

        $response->assertRedirect(route('company.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'name' => 'Zenith Global Transport',
            'ifsc' => 'SBIN0001234',
        ]);

        // 2. Duplicate GST, PAN, Contact validation on store
        $dupResp = $this->actingAs($super)->from(route('company.create'))->post(route('company.store'), [
            'name' => 'Duplicate Co',
            'short_name' => 'DUPCO',
            'gst_number' => '24AAACH7409R1ZX',
            'pan_number' => 'AAACH7409R',
            'contact_number' => '9876543210',
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);

        $dupResp->assertRedirect(route('company.create'))
            ->assertSessionHasErrors(['gst_number', 'pan_number', 'contact_number']);
    }

    public function test_company_update_with_files_and_duplicate_validation(): void
    {
        Storage::fake('public');
        $super = $this->makeUser('super_admin');
        $co1 = $this->createCompanyFor($super, [
            'name' => 'Primary Transport',
            'gst_number' => '24AAACH7409R1Z1',
            'pan_number' => 'AAACH7401R',
            'contact_number' => '9111111111',
        ]);
        $co2 = $this->createCompanyFor($super, [
            'name' => 'Secondary Transport',
            'gst_number' => '24AAACH7409R1Z2',
            'pan_number' => 'AAACH7402R',
            'contact_number' => '9222222222',
        ]);

        // 1. Duplicate checks when updating co2 with co1 values
        $failResp = $this->actingAs($super)->from(route('company.edit', $co2->id))->put(route('company.update', $co2->id), [
            'name' => 'Secondary Transport Updated',
            'gst_number' => '24AAACH7409R1Z1',
            'pan_number' => 'AAACH7401R',
            'contact_number' => '9111111111',
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        $failResp->assertRedirect(route('company.edit', $co2->id))
            ->assertSessionHasErrors(['gst_number', 'pan_number', 'contact_number']);

        // 2. Successful update with new files
        $logo = UploadedFile::fake()->image('updated_logo.png');
        $sig = UploadedFile::fake()->image('updated_sig.png');

        $okResp = $this->actingAs($super)->put(route('company.update', $co2->id), [
            'name' => 'Secondary Transport Renamed',
            'gst_number' => '24AAACH7409R1Z2',
            'pan_number' => 'AAACH7402R',
            'contact_number' => '9222222222',
            'gst_slab' => config('payments.gst_slabs')[0],
            'ifsc' => 'icic0001234',
            'company_logo' => $logo,
            'digital_signature' => $sig,
        ]);
        $okResp->assertRedirect(route('company.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $co2->id,
            'name' => 'Secondary Transport Renamed',
            'ifsc' => 'ICIC0001234',
        ]);
    }

    public function test_company_destroy_requires_remark_and_soft_deletes(): void
    {
        $super = $this->makeUser('super_admin');
        $company = $this->createCompanyFor($super);

        // Missing remark
        $failResp = $this->actingAs($super)->delete(route('company.destroy', $company->id), []);
        $failResp->assertSessionHasErrors(['delete_remark']);

        // Valid remark
        $succResp = $this->actingAs($super)->delete(route('company.destroy', $company->id), [
            'delete_remark' => 'Company dissolved',
        ]);
        $succResp->assertRedirect(route('company.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('companies', [
            'id' => $company->id,
            'deleted_by' => $super->id,
            'deleted_remark' => 'Company dissolved',
        ]);
    }

    public function test_template_previews_and_demos(): void
    {
        $super1 = $this->makeUser('super_admin');
        $super2 = $this->makeUser('super_admin');
        $master = $this->makeUser('master_admin');

        $company = $this->createCompanyFor($super1);

        // 1. Invoice template preview (html and pdf; classic, modern, compact)
        foreach (['classic', 'modern', 'compact'] as $tmpl) {
            $htmlResp = $this->actingAs($super1)->get(route('company.invoice-template-preview', [
                'company' => $company->id,
                'template' => $tmpl,
            ]));
            $htmlResp->assertOk();

            $pdfResp = $this->actingAs($super1)->get(route('company.invoice-template-preview', [
                'company' => $company->id,
                'template' => $tmpl,
                'format' => 'pdf',
            ]));
            $pdfResp->assertOk();
        }

        // 2. Invoice template demo (html and pdf)
        $demoResp = $this->actingAs($super1)->get(route('invoice.template.demo', ['template' => 'modern']));
        $demoResp->assertOk();

        $demoPdfResp = $this->actingAs($super1)->get(route('invoice.template.demo', ['template' => 'compact', 'format' => 'pdf']));
        $demoPdfResp->assertOk();

        // 3. LR copy template preview and demo (html and pdf)
        $lrPrevResp = $this->actingAs($super1)->get(route('company.lr-copy-template-preview', [
            'company' => $company->id,
            'template' => 'classic',
        ]));
        $lrPrevResp->assertOk();

        $lrPrevPdf = $this->actingAs($super1)->get(route('company.lr-copy-template-preview', [
            'company' => $company->id,
            'template' => 'classic',
            'format' => 'pdf',
        ]));
        $lrPrevPdf->assertOk();

        $lrDemoResp = $this->actingAs($super1)->get(route('lr-copy.template.demo', ['template' => 'classic']));
        $lrDemoResp->assertOk();

        $lrDemoPdf = $this->actingAs($super1)->get(route('lr-copy.template.demo', ['template' => 'classic', 'format' => 'pdf']));
        $lrDemoPdf->assertOk();

        // 4. Freight challan template preview and demo (html and pdf)
        $fcPrevResp = $this->actingAs($super1)->get(route('company.freight-challan-template-preview', [
            'company' => $company->id,
            'template' => 'classic',
        ]));
        $fcPrevResp->assertOk();

        $fcPrevPdf = $this->actingAs($super1)->get(route('company.freight-challan-template-preview', [
            'company' => $company->id,
            'template' => 'classic',
            'format' => 'pdf',
        ]));
        $fcPrevPdf->assertOk();

        $fcDemoResp = $this->actingAs($super1)->get(route('freight-challan.template.demo', ['template' => 'classic']));
        $fcDemoResp->assertOk();

        $fcDemoPdf = $this->actingAs($super1)->get(route('freight-challan.template.demo', ['template' => 'classic', 'format' => 'pdf']));
        $fcDemoPdf->assertOk();

        // 5. Unauthorized preview access (super2 accessing super1 company -> 403)
        $unauthResp = $this->actingAs($super2)->get(route('company.invoice-template-preview', [
            'company' => $company->id,
        ]));
        $unauthResp->assertForbidden();

        // 6. Master admin can access super1 company preview without 403
        $masterPrevResp = $this->actingAs($master)->get(route('company.invoice-template-preview', [
            'company' => $company->id,
        ]));
        $masterPrevResp->assertOk();

        // 7. Invoice template demo with default template
        $demoDefaultResp = $this->actingAs($super1)->get(route('invoice.template.demo'));
        $demoDefaultResp->assertOk();
    }

    public function test_company_enter_and_exit_unauthorized(): void
    {
        $master = $this->makeUser('master_admin');
        $super = $this->makeUser('super_admin');
        $company = $this->createCompanyFor($super);

        $enterResp = $this->actingAs($master)->get(route('company.enter', $company->id));
        $enterResp->assertForbidden();

        $exitResp = $this->actingAs($master)->get(route('company.exit'));
        $exitResp->assertForbidden();
    }

    public function test_company_edit_view_and_financial_year_invoices(): void
    {
        $super = $this->makeUser('super_admin');
        $country = Country::firstOrCreate(['code' => 'IN'], ['name' => 'India', 'phonecode' => 91]);
        $state = State::create(['name' => 'Maharashtra', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Mumbai', 'state_id' => $state->id, 'country_id' => $country->id]);

        $company = $this->createCompanyFor($super, [
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer FY',
        ]);

        // Test month >= 4 (May)
        Carbon::setTestNow(Carbon::create(2026, 5, 15, 10, 0, 0));
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-FY-01',
            'invoice_type' => 'Forward',
            'generated_date' => '2026-05-10',
            'total_amount' => 5000,
            'status' => 'Pending',
        ]);

        $respMay = $this->actingAs($super)->get(route('company.edit', $company->id));
        $respMay->assertOk()
            ->assertViewIs('company.edit')
            ->assertViewHas('hasInvoicesInFy', true);

        // Test month < 4 (February)
        Carbon::setTestNow(Carbon::create(2027, 2, 15, 10, 0, 0));
        $respFeb = $this->actingAs($super)->get(route('company.edit', $company->id));
        $respFeb->assertOk()
            ->assertViewIs('company.edit')
            ->assertViewHas('hasInvoicesInFy', true);

        // Test company with no country/state (covers empty collection fallbacks lines 322, 325)
        $companyNoState = $this->createCompanyFor($super, [
            'country_id' => null,
            'state_id' => null,
            'city_id' => null,
        ]);
        $respNoState = $this->actingAs($super)->get(route('company.edit', $companyNoState->id));
        $respNoState->assertOk();

        // Test Country IN fallback to name India (covers line 316)
        Country::where('code', 'IN')->update(['code' => 'IN_OLD']);
        $respIndia = $this->actingAs($super)->get(route('company.edit', $companyNoState->id));
        $respIndia->assertOk();
        Country::where('code', 'IN_OLD')->update(['code' => 'IN']);

        // Test selectedCountryId falsy (covers line 322 : collect())
        $respEmptyCountry = $this->actingAs($super)
            ->withSession(['_old_input' => ['country_id' => '']])
            ->get(route('company.edit', $companyNoState->id));
        $respEmptyCountry->assertOk()
            ->assertViewHas('states', fn ($s) => $s->isEmpty());

        Carbon::setTestNow();
    }

    public function test_company_update_gst_scheme_change_blocked_when_invoices_exist(): void
    {
        $super = $this->makeUser('super_admin');
        $country = Country::firstOrCreate(['code' => 'IN'], ['name' => 'India', 'phonecode' => 91]);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Surat', 'state_id' => $state->id, 'country_id' => $country->id]);

        $company = $this->createCompanyFor($super, [
            'gst_scheme' => 'Regular',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer GST',
        ]);

        // When month >= 4
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 10, 0, 0));
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-GST-01',
            'invoice_type' => 'Forward',
            'generated_date' => '2026-05-20',
            'total_amount' => 3000,
            'status' => 'Pending',
        ]);

        $respBlockedMay = $this->actingAs($super)->put(route('company.update', $company->id), [
            'name' => $company->name,
            'short_name' => $company->short_name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pincode' => '395001',
            'gst_scheme' => 'Composition',
        ]);
        $respBlockedMay->assertSessionHasErrors(['gst_scheme']);

        // When month < 4
        Carbon::setTestNow(Carbon::create(2027, 1, 10, 10, 0, 0));
        $respBlockedJan = $this->actingAs($super)->put(route('company.update', $company->id), [
            'name' => $company->name,
            'short_name' => $company->short_name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pincode' => '395001',
            'gst_scheme' => 'Composition',
        ]);
        $respBlockedJan->assertSessionHasErrors(['gst_scheme']);

        Carbon::setTestNow();
    }

    public function test_company_create_with_old_input_state(): void
    {
        $super = $this->makeUser('super_admin');
        $country = Country::firstOrCreate(['code' => 'IN'], ['name' => 'India', 'phonecode' => 91]);
        $state = State::create(['name' => 'Karnataka', 'country_id' => $country->id]);
        City::create(['name' => 'Bengaluru', 'state_id' => $state->id, 'country_id' => $country->id]);

        $resp = $this->actingAs($super)
            ->withSession(['_old_input' => ['state_id' => $state->id]])
            ->get(route('company.create'));

        $resp->assertOk()
            ->assertViewIs('company.create')
            ->assertViewHas('cities', fn ($c) => $c->count() >= 1);
    }
}
