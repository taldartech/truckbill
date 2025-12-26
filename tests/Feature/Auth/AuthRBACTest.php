<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthRBACTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithRole(string $roleName, bool $blocked = false): User
    {
        $role = UserRole::firstOrCreate(['name' => $roleName]);
        $company = Company::create([
            'name' => 'Company ' . $roleName,
            'is_blocked' => $blocked,
        ]);

        return User::factory()->create([
            'user_role_id' => $role->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();
        $user = $this->createUserWithRole('admin');

        $response = $this->from('/forgot-password')->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/forgot-password');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->createUserWithRole('admin');
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/login');
        $this->assertCredentials([
            'email' => $user->email,
            'password' => 'new-password',
        ]);
    }

    public function test_user_can_login_with_remember_me(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertCookie(Auth::getRecallerName());
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_login_fails_for_missing_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'missing@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_blocks_user_when_company_is_blocked(): void
    {
        $user = $this->createUserWithRole('admin', true);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->post('/password/change', [
            'old_password' => 'password',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_change_password_requires_correct_old_password(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->from('/password/change')->post('/password/change', [
            'old_password' => 'not-correct',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/password/change');
        $response->assertSessionHasErrors('old_password');
    }

    public function test_guest_is_redirected_from_protected_route(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_blocked_company_is_forbidden_by_checkblock_middleware(): void
    {
        $user = $this->createUserWithRole('admin', true);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertForbidden();
    }

    #[DataProvider('unauthorizedRouteProvider')]
    public function test_role_middleware_denies_disallowed_roles(string $uri, string $roleName): void
    {
        $user = $this->createUserWithRole($roleName);

        $response = $this->actingAs($user)->get($uri);

        $response->assertForbidden();
    }

    public static function unauthorizedRouteProvider(): array
    {
        return [
            'super admin routes require correct role' => ['/users', 'admin'],
            'master admin routes require correct role' => ['/promo_codes', 'admin'],
            'super admin branch routes require correct role' => ['/branches', 'admin'],
            'admin or moderator routes require correct role' => ['/consignees', 'accountant'],
            'admin only voucher payments create requires admin' => ['/voucher_payments/create', 'moderator'],
            'admin or accountant reports require those roles' => ['/reports/invoice', 'moderator'],
        ];
    }

    public function test_role_middleware_allows_authorized_user(): void
    {
        $user = $this->createUserWithRole('super_admin');

        $response = $this->actingAs($user)->get('/users');

        $response->assertOk();
    }
}

