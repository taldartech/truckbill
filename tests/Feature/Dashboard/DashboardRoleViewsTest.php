<?php

namespace Tests\Feature\Dashboard;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\Driver;
use App\Models\EwayBill;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\UpcomingSubscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardRoleViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                if (! $date) {
                    return null;
                }
                return Carbon::parse($date)->format('M Y');
            }, 2);
        }

        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
        UserRole::firstOrCreate(['id' => 4], ['name' => 'moderator']);
        UserRole::firstOrCreate(['id' => 5], ['name' => 'accountant']);
        UserRole::firstOrCreate(['id' => 6], ['name' => 'transporter']);
        UserRole::firstOrCreate(['id' => 7], ['name' => 'customer']);
    }

    protected function createCompany(int $userId, string $name = 'Test Corp'): Company
    {
        return Company::create([
            'name' => $name,
            'user_id' => $userId,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
            'is_blocked' => false,
        ]);
    }

    public function test_master_admin_dashboard(): void
    {
        $master = User::factory()->create([
            'user_role_id' => 1,
            'company_id' => null,
        ]);

        $company = $this->createCompany($master->id, 'Master Test Co');
        
        CompanySubscription::create([
            'company_id' => $company->id,
            'plan_name' => 'Monthly',
            'starts_on' => now()->subDays(60)->toDateString(),
            'ends_on' => now()->addDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'subscription',
            'status' => 'paid',
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);

        DB::table('upcoming_subscriptions')->insert([
            'company_id' => $company->id,
            'payment_id' => $payment->id,
            'starts_on' => now()->addDays(6)->toDateString(),
            'ends_on' => now()->addDays(370)->toDateString(),
            'included_branches' => 1,
            'users_per_branch' => 5,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($master)->get(route('dashboard'));
        $response->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHasAll([
                'systemStats',
                'expiringCompanies',
                'recentCompanies',
                'topCompaniesByRevenue',
                'monthlyGrowth',
            ]);
    }

    public function test_super_admin_dashboard_without_and_with_company_session(): void
    {
        $super = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = $this->createCompany($super->id, 'Super Owner Co');

        CompanySubscription::create([
            'company_id' => $company->id,
            'plan_name' => 'Annual',
            'starts_on' => now()->subDays(10)->toDateString(),
            'ends_on' => now()->addDays(15)->toDateString(),
            'status' => 'active',
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer Corp',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-SUPER-01',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [],
            'total_amount' => 5000,
            'net_amount' => 5000,
            'payment_status' => 'partial',
        ]);

        InvoicePayment::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'amount' => 2000,
            'payment_date' => now()->toDateString(),
            'mode' => 'bank_transfer',
        ]);

        // 1. Without session company: shows super admin summary
        $responseOverview = $this->actingAs($super)->get(route('dashboard'));
        $responseOverview->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHasAll([
                'adminStats',
                'expiringCompanies',
                'upcomingPlanExpiry',
                'revenueByCompany',
                'ownedCompanies',
            ]);

        // 2. With super admin company session: delegates to adminDashboard()
        $responseInCompany = $this->actingAs($super)
            ->withSession(['super_admin_company_id' => $company->id])
            ->get(route('dashboard'));
        $responseInCompany->assertOk()
            ->assertViewIs('dashboard');

        // 3. Super admin without any owned companies (tests companyIds === [] revenue calculation)
        session()->forget('super_admin_company_id');
        $superEmpty = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);
        $responseEmpty = $this->actingAs($superEmpty)
            ->withSession([])
            ->get(route('dashboard'));
        $responseEmpty->assertOk()
            ->assertViewIs('dashboard');
    }

    public function test_admin_dashboard_with_different_periods(): void
    {
        $super = User::factory()->create(['user_role_id' => 2]);
        $company = $this->createCompany($super->id, 'Admin Ops Co');

        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignee Admin',
        ]);

        // Unpaid invoice
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PERIOD-01',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [],
            'total_amount' => 3000,
            'net_amount' => 3000,
            'payment_status' => 'unpaid',
        ]);

        // Paid invoice (paidAmount >= amountToCollect covers line 263)
        $paidInvoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PERIOD-PAID',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [],
            'total_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'paid',
        ]);
        InvoicePayment::create([
            'company_id' => $company->id,
            'invoice_id' => $paidInvoice->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'mode' => 'cash',
        ]);

        // LR with FreightBooking within period (covers profitability trend lines 331, 347, 349-360)
        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH12AB9999',
            'truck_type' => 'Open',
        ]);
        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Driver Singh',
            'phone' => '9876543210',
        ]);
        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-PROFIT-01',
            'gr_date' => now()->toDateString(),
            'actual_amount' => 5000,
            'amount' => 5000,
            'delivery_status' => 'Pending',
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'source_city' => 'Jaipur',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Delhi',
            'destination_state' => 'Delhi',
            'good_type' => 'Steel',
            'lane' => 'Jaipur-Delhi',
            'weight' => 10,
            'no_of_units' => 1,
            'fr_rate' => 500,
            'gr_number' => 'GR-PROFIT-01',
        ]);
        FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 400,
            'tr_amount' => 4000,
            'advance_amount' => 1000,
            'balance_amount' => 3000,
            'is_delivered' => false,
            'is_cancelled' => false,
        ]);

        $lrFallback = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-PROFIT-02',
            'gr_date' => now()->toDateString(),
            'actual_amount' => null,
            'amount' => 2000,
            'delivery_status' => 'Pending',
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'source_city' => 'Jaipur',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Delhi',
            'destination_state' => 'Delhi',
            'good_type' => 'Steel',
            'lane' => 'Jaipur-Delhi',
            'weight' => 5,
            'no_of_units' => 1,
            'fr_rate' => 400,
            'gr_number' => 'GR-PROFIT-02',
        ]);
        FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lrFallback->id,
            'tr_fr_rate' => 300,
            'tr_amount' => 1500,
            'advance_amount' => 500,
            'balance_amount' => 1000,
            'is_delivered' => false,
            'is_cancelled' => false,
        ]);

        FreightBooking::addGlobalScope('empty_fb_scope', function ($builder) {
            $builder->where('tr_fr_rate', '!=', 98765);
        });

        $lrEmptyFb = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-EMPTY-FB',
            'gr_date' => now()->toDateString(),
            'actual_amount' => 1000,
            'amount' => 1000,
            'delivery_status' => 'Pending',
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'source_city' => 'Jaipur',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Delhi',
            'destination_state' => 'Delhi',
            'good_type' => 'Steel',
            'lane' => 'Jaipur-Delhi',
            'weight' => 5,
            'no_of_units' => 1,
            'fr_rate' => 400,
            'gr_number' => 'GR-EMPTY-FB',
        ]);
        DB::table('freight_bookings')->insert([
            'company_id' => $company->id,
            'lr_id' => $lrEmptyFb->id,
            'tr_fr_rate' => 98765,
            'tr_amount' => 1500,
            'advance_amount' => 500,
            'balance_amount' => 1000,
            'is_delivered' => 0,
            'is_cancelled' => 0,
            'voucher_number' => 9999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['7d', '30d', 'month', 'invalid_period'] as $period) {
            $resp = $this->actingAs($admin)->get(route('dashboard', ['period' => $period]));
            $resp->assertOk()->assertViewIs('dashboard');
        }
    }

    public function test_moderator_dashboard(): void
    {
        $super = User::factory()->create(['user_role_id' => 2]);
        $company = $this->createCompany($super->id, 'Moderator Logistics');

        $moderator = User::factory()->create([
            'user_role_id' => 4,
            'company_id' => $company->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Mod Consignee',
        ]);

        // Invoice for moderator company to calculate outstanding amount (covers lines 465-469)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-MOD-01',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 2500,
            'net_amount' => 2500,
            'payment_status' => 'unpaid',
        ]);

        $masterTrans = TransporterMaster::create([
            'name' => 'Speed Transporters',
            'pan_number' => 'ABCD12345Z',
            'address' => 'Sample Addr',
        ]);

        $trans = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $masterTrans->id,
            'is_owner' => 0,
        ]);

        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Ramesh Driver',
            'mobile' => '9876543210',
            'license_number' => 'DL12345678',
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'transporter_id' => $trans->id,
            'truck_number' => 'RJ14XX9999',
            'driver_id' => $driver->id,
            'truck_type' => 'Open',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'transporter_id' => $trans->id,
            'truck_id' => $truck->id,
            'lr_number' => 'LR-MOD-01',
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Jaipur',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Delhi',
            'destination_state' => 'Delhi',
            'lane' => 'North Lane',
            'good_type' => 'Steel',
            'weight' => 25,
            'no_of_units' => 10,
            'fr_rate' => 1000,
            'amount' => 25000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);

        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $trans->id,
            'tr_fr_rate' => 800,
            'tr_amount' => 20000,
            'advance_amount' => 5000,
            'balance_amount' => 15000,
            'is_delivered' => false,
            'is_cancelled' => false,
        ]);

        EwayBill::create([
            'freight_booking_id' => $fb->id,
            'eway_bill_number' => 'EWB-MOD-999',
            'eway_bill_expiry' => now()->toDateString(),
        ]);

        $response = $this->actingAs($moderator)->get(route('dashboard'));
        $response->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHasAll([
                'operationalStats',
                'moderatorStats',
                'bookingStatuses',
                'recentBookings',
                'topTrucks',
                'topTransporters',
                'expiredEwayBills',
            ]);
    }

    public function test_accountant_dashboard(): void
    {
        $super = User::factory()->create(['user_role_id' => 2]);
        $company = $this->createCompany($super->id, 'Accounts Firm');

        $accountant = User::factory()->create([
            'user_role_id' => 5,
            'company_id' => $company->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Billable Customer',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-ACC-01',
            'invoice_type' => 'Forward',
            'generated_date' => now()->subDays(45)->toDateString(),
            'lr_ids' => [],
            'total_amount' => 8000,
            'net_amount' => 8000,
            'payment_status' => 'partial',
        ]);

        InvoicePayment::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'amount' => 3000,
            'payment_date' => now()->toDateString(),
            'mode' => 'cheque',
        ]);

        $response = $this->actingAs($accountant)->get(route('dashboard'));
        $response->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHasAll([
                'financialStats',
                'pendingInvoices',
                'recentInvoicePayments',
            ]);
    }

    public function test_transporter_dashboard_without_and_with_master(): void
    {
        $super = User::factory()->create(['user_role_id' => 2]);
        $company = $this->createCompany($super->id, 'Transporter Fleet Co');

        // 1. Without transporter_master_id
        $transUserNoMaster = User::factory()->create([
            'user_role_id' => 6,
            'company_id' => $company->id,
            'transporter_master_id' => null,
        ]);

        $respEmpty = $this->actingAs($transUserNoMaster)->get(route('dashboard'));
        $respEmpty->assertOk()
            ->assertViewIs('dashboard');

        // 2. With transporter_master_id
        $masterTrans = TransporterMaster::create([
            'name' => 'National Fleet',
            'pan_number' => 'FLEET1234F',
            'address' => 'Mumbai Office',
        ]);

        $transUserWithMaster = User::factory()->create([
            'user_role_id' => 6,
            'company_id' => $company->id,
            'transporter_master_id' => $masterTrans->id,
        ]);

        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $masterTrans->id,
            'is_owner' => 0,
        ]);

        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Kishore Driver',
            'mobile' => '9123456789',
            'license_number' => 'DL98765432',
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'MH04AB5555',
            'driver_id' => $driver->id,
            'truck_type' => 'Container',
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'National Consignee',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'transporter_id' => $transporter->id,
            'truck_id' => $truck->id,
            'lr_number' => 'LR-TRANS-01',
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Pune',
            'source_state' => 'Maharashtra',
            'destination_city' => 'Mumbai',
            'destination_state' => 'Maharashtra',
            'lane' => 'Highway 1',
            'good_type' => 'Auto Parts',
            'weight' => 15,
            'no_of_units' => 50,
            'fr_rate' => 1200,
            'amount' => 18000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);

        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 15000,
            'advance_amount' => 3000,
            'balance_amount' => 12000,
            'is_delivered' => false,
            'is_cancelled' => false,
        ]);

        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'payment_type' => 'balance',
            'amount' => 2000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'cash',
            'payment_status' => 'paid',
        ]);

        AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 1000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'cash',
        ]);

        EwayBill::create([
            'freight_booking_id' => $fb->id,
            'eway_bill_number' => 'EWB-TRANS-123',
            'eway_bill_expiry' => now()->subDay()->toDateString(),
        ]);

        $respFull = $this->actingAs($transUserWithMaster)->get(route('dashboard'));
        $respFull->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHasAll([
                'transporterStats',
                'recentFreightBookings',
                'recentPayments',
                'expiredEwayBills',
            ]);
    }

    public function test_customer_dashboard_without_and_with_consignees(): void
    {
        $super = User::factory()->create(['user_role_id' => 2]);
        $company = $this->createCompany($super->id, 'Customer Portal Co');

        // 1. Customer without assigned consignees
        $custUserNoConsignee = User::factory()->create([
            'user_role_id' => 7,
            'company_id' => null,
        ]);

        $respEmpty = $this->actingAs($custUserNoConsignee)->get(route('dashboard'));
        $respEmpty->assertOk()->assertViewIs('dashboard');

        // 2. Customer with assigned consignee
        $custUserWithConsignee = User::factory()->create([
            'user_role_id' => 7,
            'company_id' => null,
        ]);

        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Active Customer Consignee',
        ]);
        $custUserWithConsignee->consignees()->attach($consignee->id);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $consignee->id,
            'consignor_id' => $consignee->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-CUST-99',
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Ahmedabad',
            'source_state' => 'Gujarat',
            'destination_city' => 'Surat',
            'destination_state' => 'Gujarat',
            'lane' => 'Expressway',
            'good_type' => 'Textiles',
            'weight' => 5,
            'no_of_units' => 20,
            'fr_rate' => 500,
            'amount' => 2500,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);

        $inv = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $consignee->id,
            'invoice_number' => 'INV-CUST-88',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [$lr->id],
            'total_amount' => 3000,
            'net_amount' => 3000,
            'payment_status' => 'partial',
        ]);

        InvoicePayment::create([
            'company_id' => $company->id,
            'invoice_id' => $inv->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'mode' => 'cash',
        ]);

        $respFull = $this->actingAs($custUserWithConsignee)->get(route('dashboard'));
        $respFull->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHasAll([
                'customerStats',
                'recentLorryReceipts',
                'recentInvoicePayments',
                'consigneeOptions',
            ]);
    }

    public function test_default_dashboard_fallback_for_custom_role(): void
    {
        $super = User::factory()->create(['user_role_id' => 2]);
        $company = $this->createCompany($super->id, 'Fallback Co');

        $role = UserRole::create(['name' => 'custom_role_' . uniqid()]);

        $customUser = User::factory()->create([
            'user_role_id' => $role->id,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($customUser)->get(route('dashboard'));
        $response->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas('monthlyStats');
    }
}
