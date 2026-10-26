<?php

namespace Tests\Feature\Coverage;

use App\Http\Middleware\EnsureActiveSubscription;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\CustomInvoiceDetail;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AccountingPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;
use Yajra\DataTables\Facades\DataTables;

class RemainingCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1 => 'master_admin', 2 => 'super_admin', 3 => 'admin', 4 => 'moderator', 5 => 'accountant'] as $id => $name) {
            UserRole::firstOrCreate(['id' => $id], ['name' => $name]);
        }
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Coverage Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    protected function seedBasicLr(User $admin): LorryReceipt
    {
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Coverage Customer',
        ]);

        $transporter = \App\Models\Transporter::factory()
            ->forCompany($admin->company_id)
            ->create();

        $truck = \App\Models\Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01CV9999',
            'truck_type' => 'Flatbed',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-COV-1',
            'memo_id' => 88001,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 100,
            'advance_amount' => 0,
            'balance_amount' => 100,
            'tds_amount' => 0,
            'company_id' => $admin->company_id,
            'voucher_number' => 88001,
        ]);
        $lr->freight_booking_id = $booking->id;
        $lr->save();

        return $lr;
    }

    public function test_report_index_pages_without_date_range_use_empty_collections(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->get(route('reports.tds'))
            ->assertOk()
            ->assertViewHas('lrs', fn ($lrs) => $lrs instanceof \Illuminate\Support\Collection && $lrs->isEmpty());

        $this->get(route('reports.dispatch'))
            ->assertOk()
            ->assertViewHas('lrs', fn ($lrs) => $lrs instanceof \Illuminate\Support\Collection && $lrs->isEmpty());

        $this->get(route('reports.shortage-damage'))
            ->assertOk()
            ->assertViewHas('lrs', fn ($lrs) => $lrs instanceof \Illuminate\Support\Collection && $lrs->isEmpty());

        $this->get(route('reports.eway-bill'))
            ->assertOk()
            ->assertViewHas('frs', fn ($frs) => $frs instanceof \Illuminate\Support\Collection && $frs->isEmpty());
    }

    public function test_lr_copy_export_honors_template_query_parameter(): void
    {
        $admin = $this->makeAdmin();
        $lr = $this->seedBasicLr($admin);

        $this->actingAs($admin)
            ->get(route('reports.lr-copy-export', ['lr_id' => $lr->id, 'template' => 'modern']))
            ->assertOk();
    }

    public function test_memo_copy_pdf_export_errors_when_memo_has_no_lrs(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from(route('reports.memo-copy'))
            ->get(route('reports.memo-copy-export', ['memo_id' => 999888777, 'type' => 'pdf']))
            ->assertRedirect()
            ->assertSessionHas('error', 'No data found for the selected memo.');
    }

    public function test_pending_memo_and_invoice_reports_survive_query_exceptions(): void
    {
        $admin = $this->makeAdmin();
        $lr = $this->seedBasicLr($admin);
        $lr->update(['memo_id' => null]);

        LorryReceipt::retrieved(static function (): void {
            throw new \RuntimeException('Simulated pending report failure');
        });

        $this->actingAs($admin)
            ->from(route('reports.pending-memo'))
            ->get(route('reports.pending-memo'))
            ->assertRedirect()
            ->assertSessionHas('error');

        LorryReceipt::flushEventListeners();
        $lr->update(['memo_id' => 88001]);

        LorryReceipt::retrieved(static function (): void {
            throw new \RuntimeException('Simulated pending invoice failure');
        });

        $this->actingAs($admin)
            ->from(route('reports.pending-invoice'))
            ->get(route('reports.pending-invoice'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_voucher_receipt_export_rejects_missing_gr(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();
        $lr = $this->seedBasicLr($other);

        $this->actingAs($admin)
            ->from(route('reports.transporter-report'))
            ->get(route('reports.voucher-receipt-export', ['lr_id' => $lr->id]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_mark_voucher_printed_updates_linked_lr_and_freight_booking_group(): void
    {
        $admin = $this->makeAdmin();
        $lr = $this->seedBasicLr($admin);
        $linkedGroup = 66001;
        $lr->update(['linked_lr_id' => $linkedGroup]);

        $booking = FreightBooking::create([
            'company_id' => $admin->company_id,
            'lr_id' => $lr->id,
            'transporter_id' => $lr->transporter_id,
            'linked_lr_id' => $linkedGroup,
            'tr_fr_rate' => 100,
            'tr_amount' => 100,
            'advance_amount' => 0,
            'balance_amount' => 100,
            'tds_amount' => 0,
            'voucher_number' => 66001,
        ]);
        $lr->update(['freight_booking_id' => $booking->id]);

        $lr2 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lr->customer_id,
            'consignor_id' => $lr->customer_id,
            'consignee_id' => $lr->customer_id,
            'lr_number' => 'LR-COV-LINKED',
            'linked_lr_id' => $linkedGroup,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $lr->truck_id,
            'transporter_id' => $lr->transporter_id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ]);
        $booking2 = FreightBooking::create([
            'company_id' => $admin->company_id,
            'lr_id' => $lr2->id,
            'transporter_id' => $lr->transporter_id,
            'linked_lr_id' => $linkedGroup,
            'linked_freight_booking_id' => $booking->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 100,
            'advance_amount' => 0,
            'balance_amount' => 100,
            'tds_amount' => 0,
            'voucher_number' => 66002,
        ]);
        $lr2->update(['freight_booking_id' => $booking2->id]);

        $this->actingAs($admin)
            ->postJson(route('reports.voucher-receipt-mark-printed'), [
                'freight_booking_id' => $booking->id,
            ])
            ->assertOk()
            ->assertJsonPath('linked_lr_id', $linkedGroup);

        $this->assertTrue((bool) $booking->fresh()->voucher_printed);
        $this->assertTrue((bool) $booking2->fresh()->voucher_printed);
    }

    public function test_party_bill_view_and_exports_cover_template_and_custom_invoice_branches(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $city = \App\Models\City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Party Bill Coverage',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $admin->company->update(['state_id' => $state->id, 'city_id' => $city->id]);
        $lr = $this->seedBasicLr($admin);
        $lr->update(['customer_id' => $customer->id, 'consignor_id' => $customer->id, 'consignee_id' => $customer->id]);

        CompanySetting::query()->updateOrCreate(
            ['company_id' => $admin->company_id],
            array_merge(CompanySetting::defaultsFor($admin->company), ['invoice_template' => 'modern'])
        );

        $this->actingAs($admin)
            ->get(route('reports.party-bill', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertViewHas('selectedInvoiceTemplateLabel', 'B (Modern)');

        CompanySetting::query()->where('company_id', $admin->company_id)->update(['invoice_template' => 'legacy-unknown']);

        $customInvoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV/CUSTOM\\1',
            'manual_invoice_number' => 'MAN/CUSTOM',
            'invoice_type' => 'Reverse',
            'custom_invoice' => 'rate_difference',
            'generated_date' => now()->toDateString(),
            'total_amount' => 0,
            'base_amount' => 0,
            'net_amount' => 0,
        ]);
        CustomInvoiceDetail::create([
            'invoice_id' => $customInvoice->id,
            'lr_id' => $lr->id,
            'amount' => 50,
            'remark' => 'Coverage custom',
            'portal_rate' => 40,
            'diff' => 10,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.party-bill-export', [
                'invoice_number' => $customInvoice->id,
                'type' => 'pdf',
            ]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('reports.party-bill-export', [
                'invoice_number' => $customInvoice->id,
                'type' => 'excel',
            ]))
            ->assertOk();
    }

    public function test_transporter_report_applies_valid_transporter_truck_and_reference_filters(): void
    {
        $admin = $this->makeAdmin();
        $lr = $this->seedBasicLr($admin);
        $lr->update(['reference_by' => $lr->transporter_id]);

        $dateParams = [
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $this->actingAs($admin)
            ->get(route('reports.transporter-report', $dateParams + [
                'transporter_id' => $lr->transporter_id,
                'truck_id' => $lr->truck_id,
                'reference_by' => $lr->transporter_id,
            ]))
            ->assertOk()
            ->assertViewHas('transporterId', $lr->transporter_id)
            ->assertViewHas('truckId', $lr->truck_id)
            ->assertSee('LR-COV-1');
    }

    public function test_profitability_report_handles_zero_revenue_margin_summary(): void
    {
        $admin = $this->makeAdmin();
        $lr = $this->seedBasicLr($admin);
        $lr->update(['amount' => 0, 'actual_amount' => null]);

        $this->actingAs($admin)
            ->get(route('reports.profitability', [
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('totalMargin', 0.0);
    }

    public function test_invoice_index_covers_datatable_edge_columns_and_error_path(): void
    {
        $admin = $this->makeAdmin();
        $customer = Consignee::create(['company_id' => $admin->company_id, 'name' => 'DT Edge']);
        $lr = $this->seedBasicLr($admin);

        $invoiceId = DB::table('invoices')->insertGetId([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-SCALAR-MEMO',
            'manual_invoice_number' => 'MAN-1',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'total_amount' => 500,
            'net_amount' => 500,
            'base_amount' => 500,
            'payment_status' => 'weird',
            'memo_ids' => json_encode($lr->memo_id),
            'lr_ids' => json_encode([$lr->id]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices?draw=1&start=0&length=10')
            ->assertOk();

        DataTables::shouldReceive('of')
            ->once()
            ->andThrow(new \RuntimeException('DataTables simulated failure'));

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices?draw=1&start=0&length=10')
            ->assertStatus(500)
            ->assertJsonStructure(['error']);

        $this->assertNotNull($invoiceId);
    }

    public function test_invoice_filters_actions_cross_company_and_subscription_paths(): void
    {
        $owner = $this->makeAdmin();
        $intruder = $this->makeAdmin();
        $customer = Consignee::create(['company_id' => $owner->company_id, 'name' => 'Scope Customer']);
        [$lr1] = [ $this->seedBasicLr($owner) ];

        $invoice = Invoice::create([
            'company_id' => $owner->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-SCOPE-1',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->subDays(3)->toDateString(),
            'total_amount' => 1000,
            'net_amount' => 1000,
            'base_amount' => 1000,
            'cgst' => 25,
            'sgst' => 25,
            'payment_status' => 'pending',
            'memo_ids' => [$lr1->memo_id],
            'lr_ids' => [$lr1->id],
        ]);

        $this->actingAs($owner)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices?'.http_build_query([
                'generated_date_from' => now()->subWeek()->toDateString(),
                'generated_date_to' => now()->toDateString(),
            ]))
            ->assertOk();

        $this->actingAs($owner)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices?'.http_build_query([
                'generated_date_from' => now()->subWeek()->toDateString(),
            ]))
            ->assertOk();

        $this->actingAs($owner)
            ->getJson(route('invoices.show', $invoice).'?ajax=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('igst_rate', 0);

        $this->actingAs($intruder)->put(route('invoices.update', $invoice), [
            'generated_date' => now()->toDateString(),
            'memo_ids' => [$lr1->memo_id],
        ])->assertForbidden();

        $this->actingAs($intruder)->delete(route('invoices.destroy', $invoice), [
            'delete_remark' => 'nope',
        ])->assertForbidden();

        $this->actingAs($intruder)->postJson(route('invoices.payments.store', $invoice), [
            'mode' => 'cash',
            'amount' => 10,
            'payment_date' => now()->toDateString(),
        ])->assertForbidden();

        $owner->company->subscription->update([
            'status' => 'active',
            'ends_on' => now()->subDay()->toDateString(),
        ]);
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $this->actingAs($owner)->put(route('invoices.update', $invoice), [
            'generated_date' => now()->toDateString(),
            'memo_ids' => [$lr1->memo_id],
        ])
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('error');
    }

    public function test_invoice_store_update_and_lrs_json_edge_cases(): void
    {
        $admin = $this->makeAdmin();
        $customer = Consignee::create(['company_id' => $admin->company_id, 'name' => 'GST Customer']);
        $lr = $this->seedBasicLr($admin);
        $lr->update(['customer_id' => $customer->id]);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $admin->company->update(['state_id' => $state->id, 'gst_slab' => 5]);
        $customer->update(['state_id' => $state->id]);

        $this->actingAs($admin)->post('/invoices', [
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'memo_ids' => [$lr->memo_id],
        ])->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('company_id', $admin->company_id)->latest('id')->first();
        $this->assertNotNull($invoice);

        $this->actingAs($admin)
            ->getJson('/invoices/lrs?memo_ids='.$lr->memo_id)
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/invoices/lrs?memo_ids=')
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($admin)
            ->getJson('/api/invoice-numbers/0')
            ->assertOk()
            ->assertExactJson([]);

        $wrongCustomer = Consignee::create(['company_id' => $admin->company_id, 'name' => 'Other']);
        $foreignMemo = 99002;
        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $wrongCustomer->id,
            'consignor_id' => $wrongCustomer->id,
            'consignee_id' => $wrongCustomer->id,
            'lr_number' => 'LR-FOREIGN-MEMO',
            'memo_id' => $foreignMemo,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->from(route('invoices.edit', $invoice))->put(route('invoices.update', $invoice), [
            'generated_date' => now()->toDateString(),
            'memo_ids' => [$foreignMemo],
        ])->assertRedirect(route('invoices.edit', $invoice))->assertSessionHasErrors('memo_ids');

        $lr->update(['amount' => 200]);
        $this->actingAs($admin)->put(route('invoices.update', $invoice), [
            'generated_date' => now()->toDateString(),
            'memo_ids' => [$lr->memo_id],
        ])->assertRedirect(route('invoices.index'));

        $invoice->refresh();
        $this->assertGreaterThan(0, (float) $invoice->cgst);
    }

    public function test_forgot_password_surfaces_validation_error_when_reset_link_not_sent(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn('passwords.user');

        $this->post(route('password.email'), ['email' => 'missing@example.com'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }
}
