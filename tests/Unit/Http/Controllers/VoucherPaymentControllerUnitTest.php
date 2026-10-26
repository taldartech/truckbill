<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\VoucherPaymentController;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\CompanySubscription;
use App\Models\Transporter;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class VoucherPaymentControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([1 => 'master_admin', 2 => 'super_admin', 3 => 'admin', 4 => 'moderator'] as $id => $name) {
            UserRole::create(['id' => $id, 'name' => $name]);
        }
    }

    private function admin(): User
    {
        $company = Company::create([
            'name' => 'Voucher Unit Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
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

    private function invoke(object $controller, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $args);
    }

    public function test_selected_transporter_filter_option_returns_null_without_company(): void
    {
        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $this->actingAs($super);

        $controller = app(VoucherPaymentController::class);
        $request = Request::create('/voucher_payments', 'GET', ['transporter' => 1]);

        $this->assertNull($this->invoke($controller, 'selectedTransporterFilterOptionForRequest', [$request]));
    }

    public function test_selected_transporter_filter_option_returns_null_for_unknown_transporter(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $controller = app(VoucherPaymentController::class);
        $request = Request::create('/voucher_payments', 'GET', ['transporter' => 99999]);

        $this->assertNull($this->invoke($controller, 'selectedTransporterFilterOptionForRequest', [$request]));
    }

    public function test_selected_transporter_filter_option_formats_agency_label(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $transporter = Transporter::factory()->forCompany($admin->company_id)->create([
            'mobile_number' => '9000000001',
            'is_owner' => 0,
        ]);

        $controller = app(VoucherPaymentController::class);
        $request = Request::create('/voucher_payments', 'GET', ['transporter' => $transporter->id]);

        $result = $this->invoke($controller, 'selectedTransporterFilterOptionForRequest', [$request]);

        $this->assertIsArray($result);
        $this->assertSame($transporter->id, $result['id']);
        $this->assertStringContainsString('(Agency)', $result['text']);
    }

    public function test_selected_city_filter_option_returns_null_for_missing_city(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $controller = app(VoucherPaymentController::class);
        $request = Request::create('/voucher_payments', 'GET', ['source_city' => 12345]);

        $this->assertNull($this->invoke($controller, 'selectedCityFilterOptionForRequest', [$request, 'source_city']));
    }

    public function test_selected_city_filter_option_returns_city_label(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Surat', 'state_id' => $state->id, 'country_id' => $country->id]);

        $controller = app(VoucherPaymentController::class);
        $request = Request::create('/voucher_payments', 'GET', ['source_city' => $city->id]);

        $result = $this->invoke($controller, 'selectedCityFilterOptionForRequest', [$request, 'source_city']);

        $this->assertSame($city->id, $result['id']);
        $this->assertSame($city->name, $result['text']);
    }

    public function test_validate_payment_status_rules_for_paid_revert(): void
    {
        $controller = app(VoucherPaymentController::class);
        $payment = new VoucherPayment(['payment_status' => 'paid', 'payment_mode' => 'cash']);

        $message = $this->invoke($controller, 'validatePaymentStatusRulesForUpdate', [$payment, 'cash', 'in-progress']);

        $this->assertSame('Paid payments cannot be reverted to a previous status.', $message);
    }

    public function test_validate_payment_status_rules_for_bank_in_progress_to_pending(): void
    {
        $controller = app(VoucherPaymentController::class);
        $payment = new VoucherPayment(['payment_status' => 'in-progress', 'payment_mode' => 'bank transfer']);

        $message = $this->invoke($controller, 'validatePaymentStatusRulesForUpdate', [$payment, 'bank transfer', 'pending']);

        $this->assertSame('Bank or fuel payments that are in progress cannot be set back to Pending.', $message);
    }

    public function test_default_payment_status_for_fuel_pump_mode_is_pending(): void
    {
        $controller = app(VoucherPaymentController::class);

        $this->assertSame('pending', $this->invoke($controller, 'defaultPaymentStatusForPaymentModeName', ['Fuel (Paid to Pump)']));
        $this->assertSame('pending', $this->invoke($controller, 'defaultPaymentStatusForPaymentModeName', ['Paid to Pump']));
        $this->assertSame('pending', $this->invoke($controller, 'defaultPaymentStatusForPaymentModeName', ['cheque']));
        $this->assertSame('in-progress', $this->invoke($controller, 'defaultPaymentStatusForPaymentModeName', ['cash']));
    }

    public function test_is_valid_status_transition_rejects_paid_and_cash_pending(): void
    {
        $controller = app(VoucherPaymentController::class);
        $payment = new VoucherPayment(['payment_mode' => 'cash', 'payment_status' => 'in-progress']);

        $this->assertFalse($this->invoke($controller, 'isValidStatusTransition', ['paid', 'in-progress', $payment]));
        $this->assertFalse($this->invoke($controller, 'isValidStatusTransition', ['in-progress', 'pending', $payment, 'cash']));
    }

    public function test_is_bank_or_fuel_payment_mode_name_detects_pump(): void
    {
        $controller = app(VoucherPaymentController::class);

        $this->assertTrue($this->invoke($controller, 'isBankOrFuelPaymentModeName', ['fuel(paid to pump)']));
    }

    public function test_update_status_returns_error_when_user_cannot_act_as_admin(): void
    {
        $moderator = User::factory()->create([
            'user_role_id' => 4,
            'company_id' => $this->admin()->company_id,
        ]);
        $moderator->setRelation('userRole', UserRole::find(4));
        $this->actingAs($moderator);

        $controller = app(VoucherPaymentController::class);
        $request = Request::create('/voucher_payments/update-status', 'PUT', [
            'payment_id' => 1,
            'status' => 'paid',
        ]);
        $request->setUserResolver(fn () => $moderator);

        $response = $controller->updateStatus($request);

        $this->assertTrue($response->isRedirect());
        $this->assertSame(
            'You do not have permission to update payment status.',
            $response->getSession()->get('error')
        );
    }

}
