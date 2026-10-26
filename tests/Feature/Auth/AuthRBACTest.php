<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_login_form_is_accessible_to_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login');
    }

    public function test_forgot_password_form_is_accessible_to_guests(): void
    {
        $this->get(route('password.request'))->assertOk()->assertViewIs('auth.forgot-password');
    }

    public function test_reset_password_form_renders_with_token(): void
    {
        $this->get(route('password.reset', ['token' => 'sample-reset-token']))
            ->assertOk()
            ->assertViewIs('auth.reset-password');
    }

    public function test_authenticated_user_can_view_change_password_form(): void
    {
        $user = $this->createUserWithRole('admin');

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertOk()
            ->assertViewIs('auth.changepassword');
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

        // Verify session token is generated and stored
        $user->refresh();
        $this->assertNotNull($user->session_token);
        $this->assertEquals(60, strlen($user->session_token));

        // Verify session token is stored in session
        $this->assertNotNull(session('session_token'));
        $this->assertEquals($user->session_token, session('session_token'));
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();
        $user = $this->createUserWithRole('admin');

        $response = $this->from('/forgot-password')->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/forgot-password');
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->createUserWithRole('admin');
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertRedirect('/login');
        $this->assertCredentials([
            'email' => $user->email,
            'password' => 'NewPass123!',
        ]);
    }

    public function test_reset_password_rejects_short_password(): void
    {
        $user = $this->createUserWithRole('admin');
        $token = Password::createToken($user);

        $response = $this->from(route('password.reset', ['token' => $token]) . '?email=' . urlencode($user->email))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'Short1!',
                'password_confirmation' => 'Short1!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_reset_password_rejects_password_without_number(): void
    {
        $user = $this->createUserWithRole('admin');
        $token = Password::createToken($user);

        $response = $this->from(route('password.reset', ['token' => $token]) . '?email=' . urlencode($user->email))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NoNumber!',
                'password_confirmation' => 'NoNumber!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_reset_password_rejects_password_without_special_character(): void
    {
        $user = $this->createUserWithRole('admin');
        $token = Password::createToken($user);

        $response = $this->from(route('password.reset', ['token' => $token]) . '?email=' . urlencode($user->email))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NoSpecial123',
                'password_confirmation' => 'NoSpecial123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_reset_password_rejects_password_without_uppercase(): void
    {
        $user = $this->createUserWithRole('admin');
        $token = Password::createToken($user);

        $response = $this->from(route('password.reset', ['token' => $token]) . '?email=' . urlencode($user->email))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'nouppercase123!',
                'password_confirmation' => 'nouppercase123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }

    public function test_reset_password_rejects_password_without_lowercase(): void
    {
        $user = $this->createUserWithRole('admin');
        $token = Password::createToken($user);

        $response = $this->from(route('password.reset', ['token' => $token]) . '?email=' . urlencode($user->email))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NOLOWERCASE123!',
                'password_confirmation' => 'NOLOWERCASE123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
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

        // Verify session token is generated even with remember me
        $user->refresh();
        $this->assertNotNull($user->session_token);
        $this->assertEquals(60, strlen($user->session_token));
    }

    public function test_login_invalidates_other_sessions(): void
    {
        // Skip if database sessions are not being used
        if (config('session.driver') !== 'database') {
            $this->markTestSkipped('Database sessions not configured for testing');
        }

        $user = $this->createUserWithRole('admin');

        // Create a fake session for the user (simulating another device)
        DB::table('sessions')->insert([
            'id' => 'old-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'payload' => base64_encode(serialize(['data' => 'test'])),
            'last_activity' => now()->timestamp,
        ]);

        // Verify old session exists
        $this->assertDatabaseHas('sessions', [
            'id' => 'old-session-id',
            'user_id' => $user->id,
        ]);

        // Login from new session
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Verify old session is deleted
        $this->assertDatabaseMissing('sessions', [
            'id' => 'old-session-id',
            'user_id' => $user->id,
        ]);
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

    public function test_login_blocked_company_includes_block_reason_in_error(): void
    {
        $user = $this->createUserWithRole('admin', true);
        $user->company->update(['block_reason' => 'Non-payment']);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Your account is locked. Reason: Non-payment']);
        $this->assertGuest();
    }

    public function test_forgot_password_returns_error_for_unknown_email(): void
    {
        $response = $this->from('/forgot-password')->post(route('password.email'), [
            'email' => 'unknown-user@example.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_returns_error_for_invalid_token(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->from(route('password.reset', ['token' => 'invalid-token']).'?email='.urlencode($user->email))
            ->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_blocks_individually_blocked_user_with_reason(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->update([
            'is_blocked' => true,
            'block_reason' => 'Manual suspension',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Your account is locked. Reason: Manual suspension']);
        $this->assertGuest();
    }

    public function test_customer_login_sets_portal_consignee_session(): void
    {
        $role = UserRole::firstOrCreate(['name' => 'customer']);
        $company = Company::create(['name' => 'Portal Co']);
        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Portal Party',
            'address' => 'Main Road',
        ]);

        $customer = User::factory()->create([
            'user_role_id' => $role->id,
            'company_id' => null,
            'password' => bcrypt('password'),
        ]);
        $customer->consignees()->attach($consignee->id);

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertSame($consignee->id, session('portal_consignee_id'));
    }

    public function test_customer_login_without_consignee_assignment_is_rejected(): void
    {
        $role = UserRole::firstOrCreate(['name' => 'customer']);
        $orphan = User::factory()->create([
            'user_role_id' => $role->id,
            'company_id' => null,
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $orphan->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Your account has no customer access assigned. Please contact support.']);
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

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
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
            'master admin routes require correct role' => ['/email_templates', 'admin'],
            'super admin branch routes require correct role' => ['/branches', 'admin'],
            'admin or moderator routes require correct role' => ['/consignees', 'accountant'],
            'admin only voucher payments create requires admin' => ['/voucher_payments/create', 'moderator'],
            'reports still deny unrelated roles' => ['/reports/invoice', 'customer'],
        ];
    }

    public function test_role_middleware_allows_authorized_user(): void
    {
        $user = $this->createUserWithRole('super_admin');

        $response = $this->actingAs($user)->get('/users');

        $response->assertOk();
    }
}

