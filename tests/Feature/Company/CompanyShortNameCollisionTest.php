<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyShortNameCollisionTest extends TestCase
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

    protected function makeMasterAdmin(): User
    {
        return User::factory()->create([
            'user_role_id' => 1,
        ]);
    }

    protected function createCompanyFor(User $owner, array $overrides = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Company '.$owner->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $owner->id,
            'is_blocked' => false,
        ], $overrides));
    }

    public function test_store_assigns_padded_suffix_when_short_name_collides(): void
    {
        $master = $this->makeMasterAdmin();
        $this->createCompanyFor($master, [
            'name' => 'Existing Co',
            'short_name' => 'VKC',
        ]);

        $response = $this->actingAs($master)->post('/company', [
            'name' => 'Another Co',
            'short_name' => 'VKC',
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);

        $response->assertRedirect(route('company.index'));
        $this->assertDatabaseHas('companies', [
            'name' => 'Another Co',
            'short_name' => 'VKC01',
            'user_id' => $master->id,
        ]);
    }

    public function test_store_uses_next_available_code_when_multiple_collisions(): void
    {
        $master = $this->makeMasterAdmin();
        $this->createCompanyFor($master, ['name' => 'A', 'short_name' => 'VKC']);
        $this->createCompanyFor($master, ['name' => 'B', 'short_name' => 'VKC01']);

        $response = $this->actingAs($master)->post('/company', [
            'name' => 'Third Co',
            'short_name' => 'VKC',
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);

        $response->assertRedirect(route('company.index'));
        $this->assertDatabaseHas('companies', [
            'name' => 'Third Co',
            'short_name' => 'VKC02',
        ]);
    }

    public function test_ensure_unique_short_name_matches_store_suffix_rules(): void
    {
        $master = $this->makeMasterAdmin();
        $first = $this->createCompanyFor($master, ['name' => 'X', 'short_name' => 'AB']);

        $this->assertSame('AB01', Company::ensureUniqueShortName('AB'));
        $this->assertSame('AB', Company::ensureUniqueShortName('AB', $first->id));
    }

    public function test_generate_short_name_uses_one_letter_per_word_up_to_five(): void
    {
        $this->assertSame(
            'TDPD',
            Company::generateShortNameFromCompanyName('Test Duplicate Product dsf')
        );
        $this->assertSame('VKC', Company::generateShortNameFromCompanyName('Vikas Kumar Chaturvedi'));
        $this->assertSame('ACME', Company::generateShortNameFromCompanyName('Acme'));
    }

    public function test_generate_short_name_caps_at_five_letters_for_long_names(): void
    {
        $this->assertSame(
            'ABGDE',
            Company::generateShortNameFromCompanyName('Alpha Beta Gamma Delta Epsilon Zeta')
        );
    }
}
