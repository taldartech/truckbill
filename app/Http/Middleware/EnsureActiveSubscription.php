<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\SubscriptionExpiredResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $this->resolveCompanyId($request);

        if (! $companyId) {
            return $next($request);
        }

        $company = Company::query()
            ->with('subscription')
            ->find($companyId);

        if ($company && ! $company->isPlanActive()) {
            return SubscriptionExpiredResponse::forRequest($request);
        }

        return $next($request);
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
