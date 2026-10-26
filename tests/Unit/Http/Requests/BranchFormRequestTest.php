<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BranchFormRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
    }

    public function test_store_branch_request_authorize_returns_false_for_guest(): void
    {
        Auth::logout();
        $request = StoreBranchRequest::create('/branches', 'POST', [
            'company_id' => 1,
            'branch_name' => 'HQ',
            'head_office' => true,
        ]);

        $this->assertFalse($request->authorize());
    }

    public function test_store_branch_request_authorize_allows_missing_company_pending_validation(): void
    {
        $owner = User::factory()->create(['user_role_id' => 2]);
        $this->actingAs($owner);
        $request = StoreBranchRequest::create('/branches', 'POST', [
            'branch_name' => 'HQ',
            'head_office' => true,
        ]);

        $this->assertTrue($request->authorize());
    }

    public function test_store_branch_request_authorize_allows_unknown_company_pending_validation(): void
    {
        $owner = User::factory()->create(['user_role_id' => 2]);
        $this->actingAs($owner);
        $request = StoreBranchRequest::create('/branches', 'POST', [
            'company_id' => 999999,
            'branch_name' => 'HQ',
            'head_office' => true,
        ]);

        $this->assertTrue($request->authorize());
    }

    public function test_update_branch_request_authorize_returns_false_for_guest(): void
    {
        Auth::logout();
        $company = Company::create(['name' => 'Guest Branch Co']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Main',
            'head_office' => true,
        ]);

        $request = UpdateBranchRequest::create('/branches/'.$branch->id, 'PUT', [
            'company_id' => $company->id,
            'branch_name' => 'Main',
            'head_office' => true,
        ]);
        $request->setRouteResolver(function () use ($branch, $request) {
            return (new Route('PUT', '/branches/{branch}', []))->bind($request)->setParameter('branch', $branch);
        });

        $this->assertFalse($request->authorize());
    }

    public function test_update_branch_request_authorize_returns_false_when_company_missing(): void
    {
        $owner = User::factory()->create(['user_role_id' => 2]);
        $company = Company::create([
            'name' => 'Temporary Co',
            'user_id' => $owner->id,
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Orphan',
            'head_office' => true,
        ]);
        $company->delete();

        $request = UpdateBranchRequest::create('/branches/'.$branch->id, 'PUT', [
            'company_id' => $branch->company_id,
            'branch_name' => 'Orphan',
            'head_office' => true,
        ]);
        $this->actingAs($owner);
        $request->setRouteResolver(function () use ($branch, $request) {
            return (new Route('PUT', '/branches/{branch}', []))->bind($request)->setParameter('branch', $branch);
        });

        $this->assertFalse($request->authorize());
    }

    public function test_update_branch_request_authorize_false_when_user_does_not_own_company(): void
    {
        $owner = User::factory()->create(['user_role_id' => 2]);
        $intruder = User::factory()->create(['user_role_id' => 2]);
        $company = Company::create([
            'name' => 'Owned Co',
            'user_id' => $owner->id,
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Main',
            'head_office' => true,
        ]);

        $request = UpdateBranchRequest::create('/branches/'.$branch->id, 'PUT', [
            'company_id' => $company->id,
            'branch_name' => 'Main',
            'head_office' => true,
        ]);
        $this->actingAs($intruder);
        $request->setRouteResolver(function () use ($branch, $request) {
            return (new Route('PUT', '/branches/{branch}', []))->bind($request)->setParameter('branch', $branch);
        });

        $this->assertFalse($request->authorize());
    }

    public function test_update_branch_request_authorize_true_and_rules_for_owner(): void
    {
        $owner = User::factory()->create(['user_role_id' => 2]);
        $company = Company::create([
            'name' => 'Branch Rules Co',
            'user_id' => $owner->id,
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Main',
            'head_office' => true,
        ]);

        $request = UpdateBranchRequest::create('/branches/'.$branch->id, 'PUT', [
            'company_id' => $company->id,
            'branch_name' => 'Regional HQ',
            'branch_address' => 'Station Road',
            'branch_contact_number' => '9876543210',
            'head_office' => true,
        ]);
        $this->actingAs($owner);
        $request->setRouteResolver(function () use ($branch, $request) {
            $route = (new Route('PUT', '/branches/{branch}', []))->bind($request);
            $route->setParameter('branch', $branch);

            return $route;
        });

        $this->assertTrue($request->authorize());

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertTrue($validator->passes());
        $this->assertSame('The branch name is required.', $request->messages()['branch_name.required']);
    }

    public function test_update_branch_request_authorize_false_when_branch_route_missing(): void
    {
        $owner = User::factory()->create(['user_role_id' => 2]);
        $this->actingAs($owner);

        $request = UpdateBranchRequest::create('/branches/1', 'PUT', [
            'company_id' => 1,
            'branch_name' => 'Main',
            'head_office' => true,
        ]);
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('PUT', '/branches/{branch}', []);
            $route->bind($request);
            $route->setParameter('branch', null);

            return $route;
        });

        $this->assertFalse($request->authorize());
    }

}
