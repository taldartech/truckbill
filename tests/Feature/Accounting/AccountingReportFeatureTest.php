<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Ledger;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        $roles = [
            'master_admin',
            'super_admin',
            'admin',
            'moderator',
            'accountant',
            'transporter',
            'customer',
        ];

        foreach ($roles as $role) {
            UserRole::firstOrCreate(['name' => $role]);
        }
    }

    private function setupCompanyAndUser(string $roleName = 'admin'): array
    {
        $role = UserRole::where('name', $roleName)->first();
        $user = User::factory()->create(['user_role_id' => $role->id]);

        $company = Company::create([
            'name' => 'Apex Logistics Corp',
            'short_name' => 'APEX',
            'user_id' => $user->id,
            'is_blocked' => false,
        ]);

        $user->company_id = $company->id;
        $user->save();

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 5,
            'users_per_branch' => 5,
            'lr_credits_remaining' => 1000,
        ]);

        // Provision chart of accounts
        $provisioner = app(ChartOfAccountsProvisioner::class);
        $provisioner->provisionForCompany($company);

        return [$user, $company];
    }

    public function test_admin_can_view_day_book(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.day-book', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
            'voucher_type' => 'sales',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.day_book');
        $response->assertViewHas(['company', 'transactions', 'fromDate', 'toDate', 'voucherType']);
    }

    public function test_admin_can_view_ledger_statement_with_and_without_selected_ledger(): void
    {
        [$admin, $company] = $this->setupCompanyAndUser('admin');

        // Test without ledger
        $responseWithout = $this->actingAs($admin)->get(route('accounting.ledger-statement'));
        $responseWithout->assertStatus(200);
        $responseWithout->assertViewIs('accounting.ledger_statement');
        $responseWithout->assertViewHas('ledgers');

        // Pick one provisioned ledger
        $ledger = Ledger::where('company_id', $company->id)->first();
        $this->assertNotNull($ledger);

        $responseWith = $this->actingAs($admin)->get(route('accounting.ledger-statement', [
            'ledgerId' => $ledger->id,
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));
        $responseWith->assertStatus(200);
        $responseWith->assertViewHas('statement');
    }

    public function test_admin_can_view_trial_balance(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.trial-balance', [
            'as_of_date' => now()->toDateString(),
            'mode' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.trial_balance');
        $response->assertViewHas(['company', 'trialBalance', 'asOfDate', 'mode']);
    }

    public function test_admin_can_view_profit_and_loss_report(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.profit-and-loss', [
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.profit_and_loss');
        $response->assertViewHas(['company', 'pnl', 'fromDate', 'toDate']);
    }

    public function test_admin_can_view_balance_sheet_report(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.balance-sheet', [
            'as_of_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.balance_sheet');
        $response->assertViewHas(['company', 'balanceSheet', 'asOfDate']);
    }

    public function test_admin_can_export_tally_masters_xml(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.export-tally-masters'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $this->assertStringContainsString('<?xml', $response->getContent());
    }

    public function test_admin_can_export_tally_vouchers_xml(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.export-tally-vouchers', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $this->assertStringContainsString('<?xml', $response->getContent());
    }

    public function test_admin_can_view_gstr1_report(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.gstr1', [
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.gstr1');
        $response->assertViewHas(['company', 'report', 'fromDate', 'toDate']);
    }

    public function test_admin_can_view_debtors_aging_report(): void
    {
        [$admin] = $this->setupCompanyAndUser('admin');

        $response = $this->actingAs($admin)->get(route('accounting.debtors-aging', [
            'as_of_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('accounting.debtors_aging');
        $response->assertViewHas(['company', 'report', 'asOfDate']);
    }

    public function test_accountant_role_has_full_access_to_accounting_reports(): void
    {
        [$accountant] = $this->setupCompanyAndUser('accountant');

        $response = $this->actingAs($accountant)->get(route('accounting.day-book'));
        $response->assertStatus(200);

        $pnlResponse = $this->actingAs($accountant)->get(route('accounting.profit-and-loss'));
        $pnlResponse->assertStatus(200);
    }

    public function test_moderator_or_customer_role_cannot_access_accounting_reports(): void
    {
        [$moderator] = $this->setupCompanyAndUser('moderator');

        $response = $this->actingAs($moderator)->get(route('accounting.day-book'));
        $response->assertStatus(403);
    }

    public function test_super_admin_with_session_company_can_view_reports(): void
    {
        [$user, $company] = $this->setupCompanyAndUser('super_admin');

        $response = $this->actingAs($user)
            ->withSession(['super_admin_company_id' => $company->id])
            ->get(route('accounting.day-book'));

        $response->assertStatus(200);
        $response->assertViewHas('company', fn ($c) => $c->id === $company->id);
    }
}
