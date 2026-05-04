<?php

namespace App\Http\Middleware;

use App\Models\Consignee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerConsigneeContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user || $user->userRole?->name !== 'customer') {
            abort(403, 'Unauthorized action.');
        }

        $allowedConsigneeIds = $user->allowedConsigneeIds();
        if ($allowedConsigneeIds === []) {
            abort(403, 'No customer access assigned.');
        }

        $availableConsignees = Consignee::query()
            ->with('company')
            ->whereIn('id', $allowedConsigneeIds)
            ->get();

        $globalConsigneeIds = $availableConsignees
            ->filter(fn (Consignee $consignee) => ! ($consignee->company?->is_blocked ?? false))
            ->pluck('id')
            ->all();

        if ($globalConsigneeIds === []) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'All linked customer accounts are currently blocked.');
        }

        $request->attributes->set('portal_allowed_consignee_ids', $globalConsigneeIds);
        $request->attributes->set('portal_consignees', $availableConsignees->whereIn('id', $globalConsigneeIds)->values());

        return $next($request);
    }
}
