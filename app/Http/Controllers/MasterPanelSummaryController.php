<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterPanelSummaryRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\MasterPanelSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterPanelSummaryController extends Controller
{
    public function __construct(
        private readonly MasterPanelSummaryService $summaryService
    ) {}

    public function index(MasterPanelSummaryRequest $request): View
    {
        $viewer = $request->user();
        $isMasterAdmin = $viewer->userRole?->name === 'master_admin';

        $filters = $this->summaryService->resolveFilters($request->validated());
        $scopedCompanyIds = $this->summaryService->scopedCompanyIds($viewer, $filters);

        if (! $isMasterAdmin && ! $filters['super_admin_user_id']) {
            $filters['super_admin_user_id'] = $viewer->id;
            $scopedCompanyIds = $this->summaryService->scopedCompanyIds($viewer, $filters);
        }

        $kpis = $this->summaryService->buildKpis(
            $scopedCompanyIds,
            $filters['range_start'],
            $filters['range_end']
        );

        $companyBreakdown = $this->summaryService->buildCompanyBreakdown(
            $scopedCompanyIds,
            $filters['range_start'],
            $filters['range_end']
        );

        $superAdminBreakdown = $this->summaryService->buildSuperAdminBreakdown(
            $scopedCompanyIds,
            $filters['range_start'],
            $filters['range_end'],
            $filters['super_admin_user_id']
        );

        $selectedSuperAdmin = null;
        $selectedSuperAdminCompanies = collect();

        if ($filters['super_admin_user_id']) {
            $selectedSuperAdmin = User::query()
                ->whereKey($filters['super_admin_user_id'])
                ->whereHas('userRole', fn ($q) => $q->where('name', 'super_admin'))
                ->first();

            if ($selectedSuperAdmin) {
                $selectedSuperAdminCompanies = $companyBreakdown;
            }
        }

        $companyOptions = $this->companyFilterOptions($viewer);
        $superAdminOptions = $this->superAdminFilterOptions($viewer);

        return view('master.summary_dashboard', [
            'filters' => $filters,
            'kpis' => $kpis,
            'companyBreakdown' => $companyBreakdown,
            'superAdminBreakdown' => $superAdminBreakdown,
            'selectedSuperAdmin' => $selectedSuperAdmin,
            'selectedSuperAdminCompanies' => $selectedSuperAdminCompanies,
            'companyOptions' => $companyOptions,
            'superAdminOptions' => $superAdminOptions,
            'isMasterAdmin' => $isMasterAdmin,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Company>
     */
    private function companyFilterOptions(User $viewer): \Illuminate\Database\Eloquent\Collection
    {
        $query = Company::query()->orderBy('name');

        if ($viewer->userRole?->name === 'super_admin') {
            $query->where('user_id', $viewer->id);
        }

        return $query->get(['id', 'name', 'user_id', 'is_blocked']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function superAdminFilterOptions(User $viewer): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::query()
            ->whereHas('userRole', fn ($q) => $q->where('name', 'super_admin'))
            ->orderBy('name');

        if ($viewer->userRole?->name === 'super_admin') {
            $query->whereKey($viewer->id);
        }

        return $query->get(['id', 'name', 'email']);
    }
}
