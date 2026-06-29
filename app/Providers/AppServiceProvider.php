<?php

namespace App\Providers;

use App\Models\Company;
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
