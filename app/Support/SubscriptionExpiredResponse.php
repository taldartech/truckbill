<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionExpiredResponse
{
    public static function forGraceRestrictedAction(Request $request, ?Company $company = null): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public static function forLocked(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public static function redirectForUser(Request $request): RedirectResponse
    {
        return self::forLocked($request);
    }

    public static function abortUnlessActive(Request $request): void
    {
        throw new HttpResponseException(self::forLocked($request));
    }

    public static function abortUnlessCanMutate(Request $request, ?Company $company = null): void
    {
        $payload = $company?->subscriptionAccessPayload() ?? [];
        $mode = $payload['access_mode'] ?? 'locked';

        if ($mode === 'grace') {
            throw new HttpResponseException(self::forGraceRestrictedAction($request, $company));
        }

        throw new HttpResponseException(self::forLocked($request));
    }
}
