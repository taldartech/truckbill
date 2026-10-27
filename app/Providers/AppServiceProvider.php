<?php

namespace App\Providers;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\VoucherPayment;
use App\Observers\Accounting\AdvancePaymentObserver;
use App\Observers\Accounting\FreightBookingObserver;
use App\Observers\Accounting\InvoiceObserver;
use App\Observers\Accounting\InvoicePaymentObserver;
use App\Observers\Accounting\VoucherPaymentObserver;
use App\Pulse\PulseDatabaseStorage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Contracts\Storage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Storage::class, PulseDatabaseStorage::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Invoice::observe(InvoiceObserver::class);
        InvoicePayment::observe(InvoicePaymentObserver::class);
        FreightBooking::observe(FreightBookingObserver::class);
        VoucherPayment::observe(VoucherPaymentObserver::class);
        AdvancePayment::observe(AdvancePaymentObserver::class);

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            $subscriptionAccess = null;

            if ($user !== null) {
                $companyId = $user->userRole?->name === 'super_admin'
                    ? session('super_admin_company_id')
                    : $user->company_id;

                if ($companyId) {
                    $company = Company::query()->with('subscription')->find($companyId);
                    $subscriptionAccess = $company?->subscriptionAccessPayload();
                }
            }

            $view->with('subscriptionAccess', $subscriptionAccess);
        });
    }
}
