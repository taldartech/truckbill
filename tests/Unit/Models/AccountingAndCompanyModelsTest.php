<?php

namespace Tests\Unit\Models;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\AdvancePayment;
use App\Models\AuditLog;
use App\Models\AuditLogChange;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanySubscription;
use App\Models\CostCentre;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\Ledger;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountingAndCompanyModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
    }

    /**
     * 1. AccountGroup model tests
     */
    public function test_account_group_relations_casts_and_hierarchy(): void
    {
        $group = new AccountGroup([
            'name' => 'Bank Accounts',
            'code' => 'BANK',
            'primary_type' => 'Asset',
            'is_builtin' => 1,
        ]);

        $this->assertTrue($group->is_builtin);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $group->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $group->parent());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $group->children());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $group->ledgers());

        // Hierarchy path testing
        $root = new AccountGroup(['name' => 'Assets']);
        $parent = new AccountGroup(['name' => 'Current Assets']);
        $parent->setRelation('parent', $root);

        $leaf = new AccountGroup(['name' => 'Bank Accounts']);
        $leaf->setRelation('parent', $parent);

        $this->assertEquals('Assets › Current Assets › Bank Accounts', $leaf->getHierarchyPath());
        $this->assertEquals('Assets', $root->getHierarchyPath());
    }

    /**
     * 2. AccountingVoucher model tests
     */
    public function test_accounting_voucher_relations_casts_and_balance_check(): void
    {
        $voucher = new AccountingVoucher([
            'voucher_type' => 'journal',
            'voucher_number' => 'JV-001',
            'voucher_date' => '2026-10-25',
            'reference_date' => '2026-10-24',
            'challan_tender_date' => '2026-10-23',
            'total_amount' => '1500.50',
            'is_posted' => 1,
        ]);

        $this->assertInstanceOf(Carbon::class, $voucher->voucher_date);
        $this->assertInstanceOf(Carbon::class, $voucher->reference_date);
        $this->assertInstanceOf(Carbon::class, $voucher->challan_tender_date);
        $this->assertEquals('1500.50', $voucher->total_amount);
        $this->assertTrue($voucher->is_posted);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $voucher->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $voucher->branch());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $voucher->user());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $voucher->source());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $voucher->items());

        // Test isBalanced() query execution
        $company = Company::create(['name' => 'Voucher Co']);
        $group = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'General',
            'code' => 'GEN',
            'primary_type' => 'asset',
        ]);
        $ledger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => 'Cash Ledger',
            'is_active' => true,
        ]);

        $persistedVoucher = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JV-BAL-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 1000.00,
        ]);

        // Unbalanced voucher
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $persistedVoucher->id,
            'ledger_id' => $ledger->id,
            'type' => 'debit',
            'amount' => 1000.00,
        ]);
        $this->assertFalse($persistedVoucher->isBalanced());

        // Balanced voucher
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $persistedVoucher->id,
            'ledger_id' => $ledger->id,
            'type' => 'credit',
            'amount' => 1000.00,
        ]);
        $this->assertTrue($persistedVoucher->isBalanced());
    }

    /**
     * 3. AccountingVoucherItem model tests
     */
    public function test_accounting_voucher_item_relations_and_casts(): void
    {
        $item = new AccountingVoucherItem([
            'type' => 'debit',
            'amount' => '450.75',
            'reconciliation_date' => '2026-10-25',
        ]);

        $this->assertEquals('450.75', $item->amount);
        $this->assertInstanceOf(Carbon::class, $item->reconciliation_date);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $item->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $item->accountingVoucher());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $item->ledger());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $item->costCentre());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $item->invoice());
    }

    /**
     * 4. AdvancePayment model tests
     */
    public function test_advance_payment_relations(): void
    {
        $payment = new AdvancePayment([
            'advance_amount' => 2500.00,
            'payment_date' => '2026-10-25',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment->transporter());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment->bankLedger());
    }

    /**
     * 5. AuditLog and AuditLogChange model tests
     */
    public function test_audit_log_and_change_relations_and_display_helpers(): void
    {
        $change = new AuditLogChange([
            'attribute_name' => 'status',
            'field_label' => 'Status',
            'old_value' => 'pending',
            'new_value' => 'approved',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $change->auditLog());

        $auditLog = new AuditLog([
            'subject_type' => LorryReceipt::class,
            'summary' => 'Updated Freight booking and Lorry receipt record',
            'occurred_at' => '2026-10-25 12:00:00',
        ]);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $auditLog->user());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $auditLog->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $auditLog->fieldChanges());
        $this->assertInstanceOf(Carbon::class, $auditLog->occurred_at);

        $this->assertEquals('Good Receipts (Bilty)', $auditLog->subjectTypeDisplayName());

        $auditLog->subject_type = FreightBooking::class;
        $this->assertEquals('Good Hires (Lorry Hire)', $auditLog->subjectTypeDisplayName());

        $auditLog->subject_type = Invoice::class;
        $auditLog->subject_type_name = 'Custom Invoices';
        $this->assertEquals('Custom Invoices', $auditLog->subjectTypeDisplayName());

        $this->assertEquals('Updated Good Hires (Lorry Hire) and Good Receipts (Bilty) record', $auditLog->displaySummary());
    }

    /**
     * 6. Branch and City model tests
     */
    public function test_branch_and_city_model_attributes_and_relations(): void
    {
        $branch = new Branch([
            'branch_name' => 'Mumbai North Hub',
            'head_office' => 1,
        ]);

        $this->assertEquals('branches', $branch->getTable());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $branch->company());

        $city = new City([
            'name' => 'Jaipur',
            'state_id' => 1,
        ]);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $city->state());
    }

    /**
     * 7. CompanySetting model tests
     */
    public function test_company_setting_defaults_relations_and_preview_fallbacks(): void
    {
        $company = new Company([
            'id' => 99,
            'name' => 'Default Settings Logistics',
            'address' => 'Plot 42, Sector 18, Gurugram',
        ]);
        $company->id = 99;

        // defaultsFor
        $defaults = CompanySetting::defaultsFor($company);
        $this->assertEquals(99, $defaults['company_id']);
        $this->assertEquals('Default Settings Logistics', $defaults['template_name']);
        $this->assertEquals('Plot 42, Sector 18, Gurugram', $defaults['template_address']);
        $this->assertEquals('classic', $defaults['invoice_template']);

        // company relation
        $setting = new CompanySetting($defaults);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $setting->company());

        // applyPreviewDefaults when setting is null
        $testCo1 = new Company(['name' => 'Co Alpha', 'address' => 'Alpha Address']);
        $testCo1->setRelation('setting', null);
        CompanySetting::applyPreviewDefaults($testCo1);
        $this->assertNotNull($testCo1->setting);
        $this->assertEquals('Co Alpha', $testCo1->setting->template_name);
        $this->assertEquals('Alpha Address', $testCo1->setting->template_address);

        // applyPreviewDefaults when setting exists but template_name/address are blank
        $testCo2 = new Company(['name' => 'Co Beta', 'address' => null]);
        $blankSetting = new CompanySetting(['template_name' => '', 'template_address' => '']);
        $testCo2->setRelation('setting', $blankSetting);
        CompanySetting::applyPreviewDefaults($testCo2);
        $this->assertEquals('Co Beta', $testCo2->setting->template_name);
        $this->assertEquals('Sample registered office address', $testCo2->setting->template_address);
    }

    /**
     * 8. CompanySubscription model tests
     */
    public function test_company_subscription_methods_calculations_and_create(): void
    {
        $company = Company::create(['name' => 'Subscribed Co']);

        // Test custom static create (updates if existing)
        $sub1 = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDays(10)->toDateString(),
            'ends_on' => now()->addDays(20)->toDateString(),
            'included_branches' => 2,
            'users_per_branch' => 5,
            'addon_branches' => 1,
            'addon_users' => 3,
            'included_lr_credits' => 100,
            'lr_credits_remaining' => 50,
        ]);
        $this->assertEquals('active', $sub1->status);

        // create again updates existing
        $subUpdated = CompanySubscription::create([
            'company_id' => $company->id,
            'addon_branches' => 2,
        ]);
        $this->assertEquals($sub1->id, $subUpdated->id);
        $this->assertEquals(2, $subUpdated->addon_branches);

        // Relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $sub1->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $sub1->upcoming());

        // Calculations
        $this->assertEquals(4, $subUpdated->maxBranches()); // 2 + 2
        $this->assertEquals(5, $subUpdated->maxUsersInBranch());
        $this->assertEquals(23, $subUpdated->maxCompanyUsers()); // (4 * 5) + 3

        // isActive() logic
        $this->assertTrue($subUpdated->isActive());

        $expiredSub = new CompanySubscription([
            'status' => 'active',
            'ends_on' => now()->subDays(5),
        ]);
        $this->assertFalse($expiredSub->isActive());

        $cancelledSub = new CompanySubscription([
            'status' => 'cancelled',
            'ends_on' => now()->addDays(10),
        ]);
        $this->assertFalse($cancelledSub->isActive());

        // displayStatus() and getDisplayStatusAttribute()
        $this->assertEquals('active', $subUpdated->displayStatus());
        $this->assertEquals('active', $subUpdated->display_status);

        $expiredSub->setRelation('company', null);
        $this->assertEquals('expired', $expiredSub->displayStatus());

        $unknownSub = new CompanySubscription(['status' => 'custom_hold']);
        $this->assertEquals('custom_hold', $unknownSub->displayStatus());

        $noneSub = new CompanySubscription(['status' => null]);
        $this->assertEquals('none', $noneSub->displayStatus());

        // displayStatus with company attached: grace period vs expired
        $graceCompany = Company::create(['name' => 'Grace Co']);
        $graceSub = $graceCompany->subscription;
        $graceSub->status = 'active';
        $graceSub->ends_on = now()->subDay();
        $graceSub->save();
        $graceSub->refresh();
        $this->assertEquals('grace', $graceSub->displayStatus());

        $expiredCompany = Company::create(['name' => 'Expired Co']);
        $expSub = $expiredCompany->subscription;
        $expSub->status = 'active';
        $expSub->ends_on = now()->subDays(30);
        $expSub->save();
        $expSub->refresh();
        $this->assertEquals('expired', $expSub->displayStatus());

        // Create with company_id where existing record is null
        $coStandalone = Company::create(['name' => 'Standalone Co']);
        CompanySubscription::where('company_id', $coStandalone->id)->delete();
        $subNoExisting = CompanySubscription::create([
            'company_id' => $coStandalone->id,
            'status' => 'trial',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(15)->toDateString(),
        ]);
        $this->assertEquals($coStandalone->id, $subNoExisting->company_id);

        // remainingDaysInclusive()
        $daysSub = new CompanySubscription(['ends_on' => now()->addDays(5)->toDateString()]);
        $this->assertGreaterThanOrEqual(5, $daysSub->remainingDaysInclusive());

        // Casts
        $this->assertInstanceOf(Carbon::class, $sub1->starts_on);
        $this->assertInstanceOf(Carbon::class, $sub1->ends_on);
    }

    /**
     * 9. Company model tests: Relations, subscriptions, files, dummy PAN, and short codes
     */
    public function test_company_methods_relations_and_helpers(): void
    {
        Storage::fake('public');

        $company = Company::create([
            'name' => 'Royal Express Logistics Private Limited',
            'address' => 'Transport Nagar, Jaipur',
            'dummy_pan_number' => null,
        ]);

        // 1. Relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->branches());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->invoices());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $company->country());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $company->state());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $company->city());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $company->user());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $company->setting());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $company->subscription());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->pendingUpcomingRenewals());

        // 2. Pending renewals
        $this->assertFalse($company->hasPendingUpcomingRenewal());
        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'plan',
            'status' => 'paid',
            'subtotal' => 500,
            'total_amount' => 500,
        ]);
        UpcomingSubscription::forceCreate([
            'company_id' => $company->id,
            'payment_id' => $payment->id,
            'starts_on' => now()->addDays(15)->toDateString(),
            'ends_on' => now()->addDays(45)->toDateString(),
            'included_branches' => 1,
            'users_per_branch' => 1,
            'status' => 'pending',
        ]);
        $this->assertTrue($company->hasPendingUpcomingRenewal());

        // 3. Subscription helpers
        // Saved company gets trial subscription from booted hook
        $this->assertTrue($company->isPlanActive());
        $this->assertEquals(100, $company->lrCreditsRemaining());
        $this->assertInstanceOf(Carbon::class, $company->planEndsOn());

        // Unsaved company instance without subscription
        $unsavedCo = new Company();
        $this->assertFalse($unsavedCo->isPlanActive());
        $this->assertEquals(0, $unsavedCo->lrCreditsRemaining());
        $this->assertNull($unsavedCo->planEndsOn());

        $sub = new CompanySubscription([
            'status' => 'active',
            'ends_on' => now()->addDays(15),
            'lr_credits_remaining' => 45,
        ]);
        $company->setRelation('subscription', $sub);
        $this->assertTrue($company->isPlanActive());
        $this->assertEquals(45, $company->lrCreditsRemaining());
        $this->assertInstanceOf(Carbon::class, $company->planEndsOn());

        // Subscription permission wrappers
        $this->assertIsBool($company->subscriptionCanWrite());
        $this->assertIsBool($company->subscriptionCanExport());
        $this->assertIsBool($company->subscriptionCanPrint());

        // 4. File URLs and PDF paths (empty vs non-existent vs existing)
        $this->assertNull($company->logoPublicUrl());
        $this->assertNull($company->logoFilesystemPathForPdf());
        $this->assertNull($company->signaturePublicUrl());
        $this->assertNull($company->signatureFilesystemPathForPdf());

        $company->company_logo = 'logos/missing_logo.png';
        $company->digital_signature = 'signatures/missing_sig.png';
        $this->assertNull($company->logoPublicUrl());
        $this->assertNull($company->logoFilesystemPathForPdf());
        $this->assertNull($company->signaturePublicUrl());
        $this->assertNull($company->signatureFilesystemPathForPdf());

        // Put actual files on fake storage
        Storage::disk('public')->put('logos/real_logo.png', 'fake_logo_data');
        Storage::disk('public')->put('signatures/real_sig.png', 'fake_sig_data');
        $company->company_logo = 'logos/real_logo.png';
        $company->digital_signature = 'signatures/real_sig.png';

        $this->assertStringContainsString('logos/real_logo.png', $company->logoPublicUrl());
        $this->assertStringContainsString('logos/real_logo.png', $company->logoFilesystemPathForPdf());
        $this->assertStringContainsString('signatures/real_sig.png', $company->signaturePublicUrl());
        $this->assertStringContainsString('signatures/real_sig.png', $company->signatureFilesystemPathForPdf());

        // 5. Short name generation
        $this->assertEquals('', Company::generateShortNameFromCompanyName(''));
        $this->assertEquals('ROYAL', Company::generateShortNameFromCompanyName('Royal'));
        $this->assertEquals('RELPL', Company::generateShortNameFromCompanyName('Royal Express Logistics Private Limited'));
        $this->assertEquals('OTTFF', Company::generateShortNameFromCompanyName('One Two Three Four Five Six Seven'));
        $this->assertEquals('N', Company::generateShortNameFromCompanyName('123 456 nums'));
        $this->assertEquals('', Company::generateShortNameFromCompanyName('123 456 789'));

        // ensureUniqueShortName
        $this->assertEquals('', Company::ensureUniqueShortName(''));
        $this->assertEquals('UNIQUE', Company::ensureUniqueShortName('UNIQUE'));

        Company::create(['name' => 'Unique Co 1', 'short_name' => 'TAKEN']);
        $unique = Company::ensureUniqueShortName('TAKEN');
        $this->assertEquals('TAKEN01', $unique);

        // Test with exceptCompanyId
        $exceptTest = Company::ensureUniqueShortName('TAKEN', 999999);
        $this->assertEquals('TAKEN01', $exceptTest);

        // Test collision reaching 100+ to exercise string suffix branch
        $insert = [];
        for ($i = 1; $i <= 100; $i++) {
            $suffix = strlen((string) $i) < 3 ? str_pad((string) $i, 2, '0', STR_PAD_LEFT) : (string) $i;
            $insert[] = ['name' => "Collision Co $i", 'short_name' => "TRP$suffix"];
        }
        Company::insert($insert);
        Company::create(['name' => 'Collision Base', 'short_name' => 'TRP']);
        $resHundred = Company::ensureUniqueShortName('TRP');
        $this->assertEquals('TRP101', $resHundred);

        // 6. Predefined short_name and dummy_pan_number, empty name
        $coPredefined = new Company([
            'name' => '',
            'short_name' => 'PREDEF',
        ]);
        $coPredefined->dummy_pan_number = '0PREDEF123';
        $coPredefined->save();
        $this->assertEquals('PREDEF', $coPredefined->short_name);
        $this->assertEquals('0PREDEF123', $coPredefined->dummy_pan_number);

        // 7. Dummy PAN generation
        $dummyWithExcept = Company::generateUniqueDummyPanNumber(123);
        $this->assertNotEmpty($dummyWithExcept);

        $this->expectException(\InvalidArgumentException::class);
        Company::dummyTransporterTruckOwnerPanNumber(999999);
    }

    public function test_company_dummy_pan_generation_for_valid_company(): void
    {
        $company = Company::create(['name' => 'Pan Dummy Co']);
        $this->assertNotEmpty($company->dummy_pan_number);

        // Fetch existing dummy pan
        $pan1 = Company::dummyTransporterTruckOwnerPanNumber($company->id);
        $this->assertEquals($company->dummy_pan_number, $pan1);
        $this->assertEquals(10, strlen($pan1));
        $this->assertStringStartsWith('0', $pan1);

        // Clear dummy pan and test on-demand generation in dummyTransporterTruckOwnerPanNumber
        $company->dummy_pan_number = null;
        $company->saveQuietly();
        $this->assertNull($company->fresh()->dummy_pan_number);

        $panGenerated = Company::dummyTransporterTruckOwnerPanNumber($company->id);
        $this->assertNotEmpty($panGenerated);
        $this->assertEquals(10, strlen($panGenerated));
        $this->assertStringStartsWith('0', $panGenerated);
    }

    /**
     * 11. CostCentre model tests
     */
    public function test_cost_centre_relations_and_attributes(): void
    {
        $company = Company::create(['name' => 'Cost Centre Co']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'HQ Branch',
        ]);

        $costCentre = CostCentre::create([
            'company_id' => $company->id,
            'category' => 'branch',
            'name' => 'Logistics Admin',
            'code' => 'CC-HQ-01',
            'entity_type' => Branch::class,
            'entity_id' => $branch->id,
        ]);

        $this->assertEquals('Cost Centre Co', $costCentre->company->name);
        $this->assertEquals('HQ Branch', $costCentre->entity->branch_name);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $costCentre->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $costCentre->entity());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $costCentre->voucherItems());

        // Soft deletes
        $costCentre->delete();
        $this->assertSoftDeleted('cost_centres', ['id' => $costCentre->id]);
    }
}
