<?php

namespace Tests\Feature\Users;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    protected function makeMasterAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_role_id' => 1,
            'company_id' => null,
        ], $overrides));
    }

    protected function makeSuperAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_role_id' => 2,
            'company_id' => null,
        ], $overrides));
    }

    protected function createCompanyFor(User $owner, array $attributes = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Company ' . $owner->id,
            'user_id' => $owner->id,
            'is_blocked' => false,
        ], $attributes));
    }

    protected function createBranch(Company $company, array $attributes = []): Branch
    {
        return Branch::create(array_merge([
            'company_id' => $company->id,
            'branch_name' => 'HQ',
        ], $attributes));
    }

    public function test_master_admin_can_view_users_index(): void
    {
        $admin = $this->makeMasterAdmin();

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk()->assertViewIs('users.index');
    }

    public function test_master_admin_datatable_returns_super_admin_users(): void
    {
        $admin = $this->makeMasterAdmin();
        $super = $this->makeSuperAdmin();

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?draw=1&start=0&length=10');

        $response->assertOk()
            ->assertJsonFragment(['recordsTotal' => 1])
            ->assertJsonFragment(['recordsFiltered' => 1]);

        // Master admin should only see super_admin users, not themselves
        $this->assertCount(1, $response->json('data'));
        $emails = collect($response->json('data'))->pluck('email');
        $this->assertTrue($emails->contains($super->email));
        $this->assertFalse($emails->contains($admin->email));
    }

    public function test_super_admin_datatable_is_scoped_to_owned_companies(): void
    {
        $super = $this->makeSuperAdmin();
        $ownedCompany = $this->createCompanyFor($super);
        $otherCompany = Company::create(['name' => 'Other Co', 'user_id' => null]);

        $ownedUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $ownedCompany->id,
            'email' => 'owned@example.com',
        ]);
        $otherUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $otherCompany->id,
            'email' => 'other@example.com',
        ]);

        $response = $this
            ->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?draw=1&start=0&length=10');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('owned@example.com', $response->json('data')[0]['email']);
    }

    public function test_store_creates_admin_with_company_and_branch(): void
    {
        // Super admin can create company users
        $actor = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($actor);
        $branch = $this->createBranch($company);

        $response = $this->actingAs($actor)->post(route('users.store'), [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(3, $user->user_role_id);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_master_admin_can_create_super_admin(): void
    {
        $actor = $this->makeMasterAdmin();

        $response = $this->actingAs($actor)->post(route('users.store'), [
            'name' => 'New Super Admin',
            'email' => 'newsuper@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'super_admin',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'newsuper@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(2, $user->user_role_id); // super_admin role_id
        $this->assertNull($user->company_id);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_store_requires_company_and_branch_for_non_super_admin(): void
    {
        $actor = $this->makeMasterAdmin();

        $response = $this->actingAs($actor)->from(route('users.create'))->post(route('users.store'), [
            'name' => 'No Company',
            'email' => 'nocompany@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors(['company_id', 'branch_id']);
        $this->assertDatabaseMissing('users', ['email' => 'nocompany@example.com']);
    }

    public function test_edit_forbidden_when_user_not_in_owned_company(): void
    {
        $super = $this->makeSuperAdmin();
        $otherOwner = $this->makeMasterAdmin(['email' => 'other@owner.com']);
        $otherCompany = $this->createCompanyFor($otherOwner);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->actingAs($super)->get(route('users.edit', $target));

        $response->assertForbidden();
    }

    public function test_destroy_soft_deletes_user_in_owned_company(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($super)->delete('/users/' . $target->id);

        $response->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_destroy_forbidden_for_user_outside_owned_company(): void
    {
        $super = $this->makeSuperAdmin();
        $otherOwner = $this->makeMasterAdmin(['email' => 'another@owner.com']);
        $otherCompany = $this->createCompanyFor($otherOwner);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->actingAs($super)->delete('/users/' . $target->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_update_changes_role_and_password(): void
    {
        // Super admin can update company users
        $actor = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($actor);
        $branch = $this->createBranch($company);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($actor)->put(route('users.update', $target), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'newsecret',
            'password_confirmation' => 'newsecret',
            'role' => 'moderator',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Updated Name', $target->name);
        $this->assertSame('updated@example.com', $target->email);
        $this->assertSame(4, $target->user_role_id);
        $this->assertTrue(Hash::check('newsecret', $target->password));
    }

    public function test_master_admin_can_update_super_admin(): void
    {
        $actor = $this->makeMasterAdmin();
        $target = $this->makeSuperAdmin(['email' => 'super@example.com']);

        $response = $this->actingAs($actor)->put(route('users.update', $target), [
            'name' => 'Updated Super Admin',
            'email' => 'updatedsuper@example.com',
            'password' => 'newsecret',
            'password_confirmation' => 'newsecret',
            'role' => 'super_admin',
        ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Updated Super Admin', $target->name);
        $this->assertSame('updatedsuper@example.com', $target->email);
        $this->assertSame(2, $target->user_role_id); // super_admin role_id
        $this->assertTrue(Hash::check('newsecret', $target->password));
    }
}

