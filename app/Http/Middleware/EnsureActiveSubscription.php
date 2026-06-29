<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionAccessMode;
use App\Models\Company;
use App\Services\SubscriptionAccessService;
use App\Support\SubscriptionExpiredResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        private readonly SubscriptionAccessService $accessService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $this->resolveCompanyId($request);

        if (! $companyId) {
            return $next($request);
        }

        $company = Company::query()
            ->with('subscription')
            ->find($companyId);

        if ($company === null) {
            return $next($request);
        }

        $mode = $this->accessService->resolveForCompany($company);

        if ($mode === SubscriptionAccessMode::Full) {
            return $next($request);
        }

        if ($mode === SubscriptionAccessMode::GraceReadOnly) {
            if ($this->allowsGraceRead($request)) {
                return $next($request);
            }

            return SubscriptionExpiredResponse::forGraceRestrictedAction($request, $company);
        }

        return SubscriptionExpiredResponse::forLocked($request);
    }

    private function allowsGraceRead(Request $request): bool
    {
        return $request->isMethodSafe() && ! $this->isExportOrPrintRoute($request);
    }

    private function isExportOrPrintRoute(Request $request): bool
    {
        $name = (string) $request->route()?->getName();

        return str_contains($name, 'export')
            || str_contains($name, 'print')
            || str_contains($name, '-export');
    }

    private function resolveCompanyId(Request $request): ?int
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        if ($user->userRole?->name === 'super_admin') {
            $sessionCompanyId = session('super_admin_company_id');

            return $sessionCompanyId ? (int) $sessionCompanyId : null;
        }

        return $user->company_id ? (int) $user->company_id : null;
    }
}
