<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HttpMiddlewareCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = sys_get_temp_dir().'/truckbill-views-'.getmypid();
        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }
        config(['view.compiled' => $compiledPath]);

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    private function adminWithCompany(?Company $company = null): User
    {
        $company ??= Company::create(['name' => 'Middleware Co', 'is_blocked' => false]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    public function test_check_block_middleware_logs_out_blocked_user_with_reason(): void
    {
        $user = $this->adminWithCompany();
        $user->update([
            'is_blocked' => true,
            'block_reason' => 'Fraud review',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Your account is locked. Reason: Fraud review']);
        $this->assertGuest();
    }

    public function test_check_block_middleware_logs_out_when_company_is_blocked(): void
    {
        $company = Company::create([
            'name' => 'Blocked Co',
            'is_blocked' => true,
        ]);
        $user = $this->adminWithCompany($company);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Your account is locked.']);
        $this->assertGuest();
    }

    public function test_check_block_middleware_includes_company_block_reason_when_present(): void
    {
        $company = Company::create([
            'name' => 'Blocked With Reason Co',
            'is_blocked' => true,
        ]);
        $user = $this->adminWithCompany($company);

        Company::retrieved(function (Company $loaded) use ($company): void {
            if ((int) $loaded->id === (int) $company->id) {
                $loaded->setAttribute('block_reason', 'Billing hold');
            }
        });

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Your account is locked. Reason: Billing hold']);
    }

    public function test_check_block_middleware_persists_session_token_when_missing_from_session(): void
    {
        $user = $this->adminWithCompany();
        CompanySubscription::create([
            'company_id' => $user->company_id,
            'status' => 'active',
            'starts_on' => Carbon::today()->subMonth(),
            'ends_on' => Carbon::today()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);
        $user->update(['session_token' => 'persist-me-token']);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertSame('persist-me-token', session('session_token'));
    }

    public function test_check_block_middleware_logs_out_on_session_token_mismatch(): void
    {
        $user = $this->adminWithCompany();
        $user->update(['session_token' => 'server-token']);

        $response = $this->withSession(['session_token' => 'stale-token'])
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'You have been logged out because you logged in from another device.');
        $this->assertGuest();
    }

    public function test_check_block_middleware_allows_request_when_session_token_matches(): void
    {
        $user = $this->adminWithCompany();
        $user->update(['session_token' => 'sync-token']);

        $response = $this->withSession(['session_token' => 'sync-token'])
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
    }

    public function test_prevent_search_indexing_sets_robots_header_when_enabled(): void
    {
        Config::set('app.block_search_indexing', true);
        $user = $this->adminWithCompany();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_ensure_active_subscription_uses_super_admin_company_context(): void
    {
        Cache::put('subscription.settings', [
            'grace_period_days' => 0,
            'gst_rate_percent' => 18.0,
        ], now()->addDay());

        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $company = Company::create([
            'name' => 'Super Context Co',
            'user_id' => $super->id,
            'is_blocked' => false,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDay(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $response = $this->actingAs($super)
            ->withSession(['super_admin_company_id' => $company->id])
            ->get('/trucks');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_ensure_active_subscription_resolve_company_id_returns_null_for_guest(): void
    {
        $middleware = app(EnsureActiveSubscription::class);
        $method = new \ReflectionMethod(EnsureActiveSubscription::class, 'resolveCompanyId');
        $method->setAccessible(true);
        $request = Request::create('/trucks', 'GET');
        $request->setUserResolver(fn () => null);

        $this->assertNull($method->invoke($middleware, $request));
    }

    public function test_ensure_active_subscription_passthrough_when_company_id_unresolved(): void
    {
        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $middleware = app(EnsureActiveSubscription::class);
        $request = Request::create('/lorry_receipts', 'GET');
        $request->setUserResolver(fn () => $super);

        $response = $middleware->handle($request, fn () => response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_ensure_active_subscription_passthrough_when_company_record_missing(): void
    {
        $admin = User::factory()->make([
            'user_role_id' => 3,
            'company_id' => 999999,
        ]);
        $admin->setRelation('userRole', UserRole::find(3));

        $middleware = app(EnsureActiveSubscription::class);
        $request = Request::create('/invoices', 'GET');
        $request->setUserResolver(fn () => $admin);

        $response = $middleware->handle($request, fn () => response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_role_middleware_aborts_for_unauthenticated_user(): void
    {
        $middleware = app(RoleMiddleware::class);
        $request = Request::create('/users', 'GET');

        try {
            $middleware->handle($request, fn () => response('ok'), 'admin');
            $this->fail('Expected RoleMiddleware to abort for guest.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame('Unauthorized action.', $exception->getMessage());
        }
    }
}
