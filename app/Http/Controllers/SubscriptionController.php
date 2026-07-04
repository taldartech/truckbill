<?php

namespace App\Http\Controllers;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use App\Models\UpcomingSubscription;
use App\Services\AdminApiClient;
use App\Services\SubscriptionCheckoutService;
use App\Services\SubscriptionPricingService;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionPricingService $pricingService,
        private readonly SubscriptionCheckoutService $checkoutService,
        private readonly AdminApiClient $adminApi,
        private readonly SubscriptionUsageService $usageService
    ) {}

    public function index(): View
    {
        $this->assertSuperAdmin();

        $companies = Company::query()
            ->where('user_id', Auth::id())
            ->with('subscription')
            ->orderBy('name')
            ->get();

        return view('subscription.index', compact('companies'));
    }

    public function show(Request $request): View
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->query('company_id'));

        $subscription = $company->subscription;
        $isExpired = ! $subscription?->isActive();
        $catalog = $this->loadCatalogOrNull();
        $usage = $this->usageService->usageForCompany($company);
        $payments = SubscriptionPayment::query()
            ->where('company_id', $company->id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $upcoming = UpcomingSubscription::query()
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        return view('subscription.show', compact(
            'company',
            'subscription',
            'isExpired',
            'catalog',
            'usage',
            'payments',
            'upcoming',
        ));
    }

    public function checkoutForm(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->query('company_id'));

        $subscription = $company->subscription;
        $isExpired = ! $subscription?->isActive();

        $purchaseType = $request->query('purchase_type', 'renewal');
        if (! in_array($purchaseType, ['renewal', 'addon'], true)) {
            $purchaseType = 'renewal';
        }

        if ($purchaseType === 'addon' && $isExpired) {
            return redirect()
                ->route('subscription.show', ['company_id' => $company->id])
                ->with('error', 'Add-ons are only available for active subscriptions.');
        }

        try {
            $catalog = $this->adminApi->getCatalog();
        } catch (SubscriptionCatalogUnavailableException) {
            return redirect()
                ->route('subscription.show', ['company_id' => $company->id])
                ->with('error', 'Unable to load pricing. Please try again later.');
        }

        $usage = null;
        $minimumAddons = null;
        if ($purchaseType === 'renewal') {
            $usage = $this->usageService->usageForCompany($company);
            $minimumAddons = $this->pricingService->minimumRenewalAddons($company, $catalog['catalog']);
        }

        return view('subscription.checkout', compact(
            'company',
            'subscription',
            'isExpired',
            'catalog',
            'purchaseType',
            'usage',
            'minimumAddons',
        ));
    }

    public function history(Request $request): View
    {
        $this->assertSuperAdmin();
        $companyId = (int) $request->query('company_id');
        $payments = SubscriptionPayment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereHas('company', fn ($q) => $q->where('user_id', Auth::id()))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('subscription.history', compact('payments', 'companyId'));
    }

    public function quote(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->input('company_id'));

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'purchase_type' => ['required', 'in:renewal,addon'],
            'addon_branches' => ['nullable', 'integer', 'min:0'],
            'addon_users' => ['nullable', 'integer', 'min:0'],
            'addon_lr_credits' => ['nullable', 'integer', 'min:0'],
            'promo_code' => ['nullable', 'string'],
            'promo_validated' => ['nullable', 'boolean'],
        ]);

        $validated['addon_trucks'] = 0;

        try {
            return response()->json($this->pricingService->quote($company, $validated));
        } catch (SubscriptionCatalogUnavailableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'blocking_errors' => [$exception->getMessage()],
                'can_checkout' => false,
            ], 503);
        }
    }

    public function validatePromo(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $result = $this->adminApi->validatePromo($validated['code']);
        } catch (SubscriptionCatalogUnavailableException $exception) {
            return response()->json([
                'valid' => false,
                'message' => $exception->getMessage(),
            ], 503);
        }

        return response()->json($result, ($result['valid'] ?? false) ? 200 : 422);
    }

    public function checkout(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();
        $company = $this->resolveCompany((int) $request->input('company_id'));

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'purchase_type' => ['required', 'in:renewal,addon'],
            'addon_branches' => ['nullable', 'integer', 'min:0'],
            'addon_users' => ['nullable', 'integer', 'min:0'],
            'addon_lr_credits' => ['nullable', 'integer', 'min:0'],
            'promo_code' => ['nullable', 'string'],
            'promo_validated' => ['nullable', 'boolean'],
        ]);

        $validated['addon_trucks'] = 0;

        try {
            $order = $this->checkoutService->createCheckoutOrder($company, $validated, Auth::id());
        } catch (SubscriptionCatalogUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($order);
    }

    public function callback(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();

        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = $this->checkoutService->handleCallback($validated, Auth::id());

        return response()->json(['success' => true, 'payment' => $payment]);
    }

    private function assertSuperAdmin(): void
    {
        if (Auth::user()->userRole->name !== 'super_admin') {
            abort(403);
        }
    }

    private function resolveCompany(int $companyId): Company
    {
        $company = Company::with('subscription')->findOrFail($companyId);
        if ($company->user_id !== Auth::id()) {
            abort(403);
        }

        return $company;
    }

    private function loadCatalogOrNull(): ?array
    {
        try {
            return $this->adminApi->getCatalog();
        } catch (SubscriptionCatalogUnavailableException) {
            return null;
        }
    }
}
