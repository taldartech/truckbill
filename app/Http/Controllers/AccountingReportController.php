<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Ledger;
use App\Services\FinancialReportService;
use App\Services\TallyXmlExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseStatus;

class AccountingReportController extends Controller
{
    public function __construct(
        private readonly FinancialReportService $reportService,
        private readonly TallyXmlExportService $tallyExportService
    ) {}

    private function getActiveCompany(): Company
    {
        $companyId = Auth::user()->userRole?->name === 'super_admin' && session('super_admin_company_id')
            ? session('super_admin_company_id')
            : Auth::user()->company_id;

        return Company::findOrFail($companyId);
    }

    /**
     * Day Book view.
     */
    public function dayBook(Request $request)
    {
        $company = $this->getActiveCompany();
        $fromDate = $request->input('from_date', now()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $voucherType = $request->input('voucher_type');

        $transactions = $this->reportService->getDayBook($company, $fromDate, $toDate, $voucherType);

        return view('accounting.day_book', compact('company', 'transactions', 'fromDate', 'toDate', 'voucherType'));
    }

    /**
     * Ledger Statement (Account Book) view.
     */
    public function ledgerStatement(Request $request, ?int $ledgerId = null)
    {
        $company = $this->getActiveCompany();
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $searchQuery = $request->input('search');
        $billFilter = $request->input('bill_filter');

        $selectedLedgerId = $ledgerId ?: $request->input('ledger_id');
        $ledgers = Ledger::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $statement = null;
        $outstandings = null;
        if ($selectedLedgerId) {
            $ledger = Ledger::where('company_id', $company->id)->findOrFail($selectedLedgerId);
            $statement = $this->reportService->getLedgerStatement($ledger, $fromDate, $toDate, $searchQuery);
            $outstandings = $this->reportService->getBillToBillOutstandings($ledger, $billFilter);
        }

        return view('accounting.ledger_statement', compact(
            'company',
            'ledgers',
            'selectedLedgerId',
            'statement',
            'outstandings',
            'fromDate',
            'toDate',
            'searchQuery',
            'billFilter'
        ));
    }

    /**
     * Trial Balance view.
     */
    public function trialBalance(Request $request)
    {
        $company = $this->getActiveCompany();
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $mode = $request->input('mode', 'all');

        $trialBalance = $this->reportService->getTrialBalance($company, $asOfDate, $mode);

        return view('accounting.trial_balance', compact('company', 'trialBalance', 'asOfDate', 'mode'));
    }

    /**
     * Profit and Loss Account view.
     */
    public function profitAndLoss(Request $request)
    {
        $company = $this->getActiveCompany();
        $fromDate = $request->input('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $pnl = $this->reportService->getProfitAndLoss($company, $fromDate, $toDate);

        return view('accounting.profit_and_loss', compact('company', 'pnl', 'fromDate', 'toDate'));
    }

    /**
     * Balance Sheet view.
     */
    public function balanceSheet(Request $request)
    {
        $company = $this->getActiveCompany();
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        $balanceSheet = $this->reportService->getBalanceSheet($company, $asOfDate);

        return view('accounting.balance_sheet', compact('company', 'balanceSheet', 'asOfDate'));
    }

    /**
     * Export Tally XML Masters.
     */
    public function exportTallyMasters(): Response
    {
        $company = $this->getActiveCompany();
        $xml = $this->tallyExportService->exportMastersXml($company);

        $fileName = 'tally_masters_'.str($company->name)->slug().'_'.now()->format('Ymd_His').'.xml';

        return response($xml, ResponseStatus::HTTP_OK, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Export Tally XML Vouchers.
     */
    public function exportTallyVouchers(Request $request): Response
    {
        $company = $this->getActiveCompany();
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $xml = $this->tallyExportService->exportVouchersXml($company, $fromDate, $toDate);

        $fileName = 'tally_vouchers_'.str($company->name)->slug()."_{$fromDate}_to_{$toDate}.xml";

        return response($xml, ResponseStatus::HTTP_OK, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * GSTR-1 Return Preparation (Table 4A Forward vs Table 4B Reverse Charge).
     */
    public function gstr1(Request $request)
    {
        $company = $this->getActiveCompany();
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $report = $this->reportService->getGstr1Report($company, $fromDate, $toDate);

        return view('accounting.gstr1', compact('company', 'report', 'fromDate', 'toDate'));
    }

    /**
     * Trade Receivables (Sundry Debtors) Aging Report with MSME alerts.
     */
    public function debtorsAging(Request $request)
    {
        $company = $this->getActiveCompany();
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        $report = $this->reportService->getDebtorsAgingReport($company, $asOfDate);

        return view('accounting.debtors_aging', compact('company', 'report', 'asOfDate'));
    }
}

