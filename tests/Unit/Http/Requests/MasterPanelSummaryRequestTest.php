<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\MasterPanelSummaryRequest;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MasterPanelSummaryRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    public function test_rules_and_messages_for_master_admin(): void
    {
        $master = User::factory()->create(['user_role_id' => 1, 'company_id' => null]);
        $request = MasterPanelSummaryRequest::create('/master/summary', 'GET', [
            'from_date' => now()->toDateString(),
            'to_date' => now()->subDay()->toDateString(),
        ]);
        $request->setUserResolver(fn () => $master);

        $this->assertTrue($request->authorize());
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $request->withValidator($validator);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('to_date', $validator->errors()->toArray());
    }

    public function test_with_validator_super_admin_cannot_filter_other_super_admin(): void
    {
        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $otherSuper = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);

        $request = MasterPanelSummaryRequest::create('/master/summary', 'GET', [
            'super_admin_user_id' => $otherSuper->id,
        ]);
        $request->setUserResolver(fn () => $super);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('super_admin_user_id', $validator->errors()->toArray());
    }

    public function test_with_validator_super_admin_cannot_select_unowned_company(): void
    {
        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $otherSuper = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $foreignCompany = Company::create([
            'name' => 'Foreign Co',
            'user_id' => $otherSuper->id,
            'is_blocked' => false,
        ]);

        $request = MasterPanelSummaryRequest::create('/master/summary', 'GET', [
            'company_id' => $foreignCompany->id,
        ]);
        $request->setUserResolver(fn () => $super);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('company_id', $validator->errors()->toArray());
    }

    public function test_with_validator_returns_early_when_user_is_missing(): void
    {
        $existing = User::factory()->create();

        $request = MasterPanelSummaryRequest::create('/master/summary', 'GET', [
            'super_admin_user_id' => $existing->id,
        ]);
        $request->setUserResolver(fn () => null);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->passes());
    }

    public function test_authorize_allows_only_master_admin(): void
    {
        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $request = MasterPanelSummaryRequest::create('/master/summary', 'GET');
        $request->setUserResolver(fn () => $super);

        $this->assertFalse($request->authorize());
    }

    public function test_super_admin_can_filter_own_company_and_user_id(): void
    {
        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $company = Company::create([
            'name' => 'Owned Co',
            'user_id' => $super->id,
            'is_blocked' => false,
        ]);

        $request = MasterPanelSummaryRequest::create('/master/summary', 'GET', [
            'company_id' => $company->id,
            'super_admin_user_id' => $super->id,
        ]);
        $request->setUserResolver(fn () => $super);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->passes());
    }
}
