<?php

namespace Tests\Feature\Portal;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\GoodType;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAndTransporterPortalTest extends TestCase
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

    private function createCompany(): Company
    {
        $adminRole = UserRole::where('name', 'admin')->first();
        $admin = User::factory()->create(['user_role_id' => $adminRole->id]);

        $company = Company::create([
            'name' => 'Swift Cargo Movers',
            'short_name' => 'SCM',
            'user_id' => $admin->id,
            'is_blocked' => false,
        ]);

        $admin->company_id = $company->id;
        $admin->save();

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 5,
            'users_per_branch' => 5,
            'lr_credits_remaining' => 500,
        ]);

        return $company;
    }

    private function createDummyLr(Company $company, Consignee $customer, Truck $truck, string $lrNumber): LorryReceipt
    {
        $master = TransporterMaster::first() ?? TransporterMaster::factory()->create();
        $transporter = Transporter::where('company_id', $company->id)->first() ?? Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
        ]);

        return LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => $lrNumber,
            'gr_number' => 'GR-'.$lrNumber,
            'source_city' => 'Delhi',
            'source_state' => 'Delhi',
            'destination_city' => 'Jaipur',
            'destination_state' => 'Rajasthan',
            'lane' => 'Delhi-Jaipur',
            'good_type' => 'General Goods',
            'weight' => 10,
            'no_of_units' => 100,
            'fr_rate' => 2500,
            'amount' => 25000,
            'total_freight' => 25000,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
        ]);
    }

    public function test_customer_portal_lorry_receipts_listing_and_show(): void
    {
        $company = $this->createCompany();
        $customerRole = UserRole::where('name', 'customer')->first();

        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Tata Steel Distribution',
            'address' => 'Jamshedpur',
        ]);

        $customerUser = User::factory()->create([
            'user_role_id' => $customerRole->id,
            'company_id' => $company->id,
        ]);
        $customerUser->consignees()->attach($consignee->id);

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'JH05AB1234',
            'truck_type' => 'Trailer',
        ]);

        $lr = $this->createDummyLr($company, $consignee, $truck, 'LR-STEEL-101');

        // Customer lists LRs
        $listResponse = $this->actingAs($customerUser)->get(route('customer.lorry-receipts.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertViewIs('customer.lorry_receipts.index');
        $listResponse->assertViewHas('lorryReceipts');

        // Customer views specific LR
        $showResponse = $this->actingAs($customerUser)->get(route('customer.lorry-receipts.show', $lr));
        $showResponse->assertStatus(200);
        $showResponse->assertViewIs('customer.lorry_receipts.show');
        $showResponse->assertViewHas('lorryReceipt');
    }

    public function test_customer_cannot_view_unauthorized_lorry_receipt_of_other_consignee(): void
    {
        $company = $this->createCompany();
        $customerRole = UserRole::where('name', 'customer')->first();

        $myConsignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'My Consignee Ltd',
            'address' => 'Delhi',
        ]);
        $otherConsignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Other Consignee Ltd',
            'address' => 'Mumbai',
        ]);

        $customerUser = User::factory()->create([
            'user_role_id' => $customerRole->id,
            'company_id' => $company->id,
        ]);
        $customerUser->consignees()->attach($myConsignee->id);

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'DL01AA1111',
            'truck_type' => 'Tanker',
        ]);

        $otherLr = $this->createDummyLr($company, $otherConsignee, $truck, 'LR-OTHER-999');

        $response = $this->actingAs($customerUser)->get(route('customer.lorry-receipts.show', $otherLr));
        $response->assertStatus(403);
    }

    public function test_customer_invoices_and_payments_portal(): void
    {
        $company = $this->createCompany();
        $customerRole = UserRole::where('name', 'customer')->first();

        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Reliance Retail Outlets',
            'address' => 'Navi Mumbai',
        ]);

        $customerUser = User::factory()->create([
            'user_role_id' => $customerRole->id,
            'company_id' => $company->id,
        ]);
        $customerUser->consignees()->attach($consignee->id);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $consignee->id,
            'invoice_number' => 'INV-REL-001',
            'invoice_type' => 'regular',
            'generated_date' => now()->toDateString(),
            'total_amount' => 15000,
            'net_amount' => 15000,
            'payment_status' => 'partial',
        ]);

        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
            'mode' => 'bank',
            'notes' => 'Advance NEFT transfer',
            'created_by' => $company->user_id,
        ]);

        // List invoices
        $invoicesResponse = $this->actingAs($customerUser)->get(route('customer.invoices.index'));
        $invoicesResponse->assertStatus(200);
        $invoicesResponse->assertViewIs('customer.invoices.index');
        $invoicesResponse->assertViewHas('invoices');

        // Show single invoice
        $showInvoiceResponse = $this->actingAs($customerUser)->get(route('customer.invoices.show', $invoice));
        $showInvoiceResponse->assertStatus(200);
        $showInvoiceResponse->assertViewIs('customer.invoices.show');

        // List payments
        $paymentsResponse = $this->actingAs($customerUser)->get(route('customer.payments.index'));
        $paymentsResponse->assertStatus(200);
        $paymentsResponse->assertViewIs('customer.payments.index');
    }

    public function test_customer_without_assigned_consignee_gets_forbidden(): void
    {
        $company = $this->createCompany();
        $customerRole = UserRole::where('name', 'customer')->first();

        $unassignedUser = User::factory()->create([
            'user_role_id' => $customerRole->id,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($unassignedUser)->get(route('customer.lorry-receipts.index'));
        $response->assertStatus(403);
    }

    public function test_transporter_portal_freight_bookings_and_payments(): void
    {
        $company = $this->createCompany();
        $transporterRole = UserRole::where('name', 'transporter')->first();

        $master = TransporterMaster::factory()->create(['name' => 'National Highway Roadlines']);
        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
        ]);

        $transporterUser = User::factory()->create([
            'user_role_id' => $transporterRole->id,
            'transporter_master_id' => $master->id,
            'company_id' => $company->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignor A',
            'address' => 'Jaipur',
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14XY5678',
            'truck_type' => 'Container',
        ]);

        $lr = $this->createDummyLr($company, $customer, $truck, 'LR-TR-202');

        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 20000,
            'tr_amount' => 20000,
            'advance_amount' => 5000,
            'balance_amount' => 15000,
            'is_delivered' => false,
            'is_cancelled' => false,
        ]);

        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'payment_date' => now()->toDateString(),
            'payment_type' => 'balance',
            'amount' => 5000,
            'payment_mode' => 'bank',
            'payment_status' => 'paid',
            'created_by' => $company->user_id,
        ]);

        AdvancePayment::create([
            'transporter_id' => $transporter->id,
            'advance_amount' => 5000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'cash',
            'company_id' => $company->id,
            'created_by' => $company->user_id,
        ]);

        // Transporter views bookings list
        $bookingsResponse = $this->actingAs($transporterUser)->get(route('transporter.freight_bookings.index'));
        $bookingsResponse->assertStatus(200);
        $bookingsResponse->assertViewIs('transporter.freight_bookings');

        // Transporter views single booking
        $showBookingResponse = $this->actingAs($transporterUser)->get(route('transporter.freight_bookings.show', $booking));
        $showBookingResponse->assertStatus(200);
        $showBookingResponse->assertViewIs('transporter.freight_booking_show');

        // Transporter views payments
        $paymentsResponse = $this->actingAs($transporterUser)->get(route('transporter.payments.index'));
        $paymentsResponse->assertStatus(200);
        $paymentsResponse->assertViewIs('transporter.payments');
    }

    public function test_transporter_cannot_access_booking_of_another_transporter(): void
    {
        $company = $this->createCompany();
        $transporterRole = UserRole::where('name', 'transporter')->first();

        $masterA = TransporterMaster::factory()->create(['name' => 'Transporter A Masters']);
        $masterB = TransporterMaster::factory()->create(['name' => 'Transporter B Masters']);

        $transporterB = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $masterB->id,
        ]);

        $userA = User::factory()->create([
            'user_role_id' => $transporterRole->id,
            'transporter_master_id' => $masterA->id,
            'company_id' => $company->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer Corp',
            'address' => 'Ahmedabad',
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'GJ01AA9999',
            'truck_type' => 'Open',
        ]);

        $lr = $this->createDummyLr($company, $customer, $truck, 'LR-TR-303');

        $bookingB = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporterB->id,
            'tr_fr_rate' => 12000,
            'tr_amount' => 12000,
            'balance_amount' => 12000,
        ]);

        $response = $this->actingAs($userA)->get(route('transporter.freight_bookings.show', $bookingB));
        $response->assertStatus(403);
    }
}
