<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\LorryReceiptController;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Consignee;
use App\Models\LorryReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class LorryReceiptControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([1 => 'master_admin', 2 => 'super_admin', 3 => 'admin'] as $id => $name) {
            UserRole::create(['id' => $id, 'name' => $name]);
        }
    }

    private function admin(): User
    {
        $company = Company::create([
            'name' => 'Unit Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
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

    public function test_redirect_to_lorry_receipts_index_without_flash(): void
    {
        $this->actingAs($this->admin());

        $controller = app(LorryReceiptController::class);
        $method = new ReflectionMethod(LorryReceiptController::class, 'redirectToLorryReceiptsIndex');
        $method->setAccessible(true);

        $request = Request::create('/lorry_receipts', 'GET', ['page' => 2]);
        $response = $method->invoke($controller, $request, null, null);

        $this->assertFalse($response->getSession()->has('success'));
        $this->assertFalse($response->getSession()->has('error'));
        $this->assertStringContainsString(route('lorry_receipts.index'), $response->getTargetUrl());
    }

    public function test_can_change_transporter_returns_true_when_gr_date_missing(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $lr = new \App\Models\LorryReceipt([
            'gr_date' => null,
        ]);

        $controller = app(LorryReceiptController::class);
        $method = new ReflectionMethod(LorryReceiptController::class, 'canChangeTransporterForLorryReceipt');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $lr));
    }

    public function test_validate_lorry_receipt_uniqueness_update_rejects_duplicate_memo_id(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Cust',
            'address' => 'Addr',
        ]);

        $first = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-UNIT-MEMO-1',
            'gr_number' => 'GR-UNIT-MEMO-1',
            'memo_id' => 'MEMO-UNIT-DUP',
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
        ]);

        $second = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-UNIT-MEMO-2',
            'gr_number' => 'GR-UNIT-MEMO-2',
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
        ]);

        $controller = app(LorryReceiptController::class);
        $method = new ReflectionMethod(LorryReceiptController::class, 'validateLorryReceiptUniquenessUpdate');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);

        $method->invoke($controller, [
            'lr_number' => 'LR-UNIT-MEMO-2',
            'gr_number' => 'GR-UNIT-MEMO-2',
            'memo_id' => 'MEMO-UNIT-DUP',
        ], $second->id, $admin->company_id);
    }

    public function test_force_agency_for_orphan_truck_returns_early_when_truck_id_empty(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $controller = app(LorryReceiptController::class);
        $method = new ReflectionMethod(LorryReceiptController::class, 'forceAgencyForOrphanTruck');
        $method->setAccessible(true);

        $validated = ['truck_id' => null, 'transporter_type' => 'owner'];
        $method->invokeArgs($controller, [&$validated, $admin->company_id]);

        $this->assertSame('owner', $validated['transporter_type']);
    }
}
