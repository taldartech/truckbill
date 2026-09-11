<?php

namespace App\Services;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\Company;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /**
     * Get Day Book transactions for a company between dates.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getDayBook(Company $company, ?string $fromDate = null, ?string $toDate = null, ?string $voucherType = null): Collection
    {
        $fromDate = $fromDate ?: now()->toDateString();
        $toDate = $toDate ?: now()->toDateString();

        $query = AccountingVoucher::with(['items.ledger', 'user'])
            ->where('company_id', $company->id)
            ->where('is_posted', true)
            ->whereDate('voucher_date', '>=', $fromDate)
            ->whereDate('voucher_date', '<=', $toDate)
            ->orderBy('voucher_date', 'asc')
            ->orderBy('id', 'asc');

        if ($voucherType) {
            $query->where('voucher_type', $voucherType);
        }

        return $query->get()->map(function (AccountingVoucher $voucher): array {
            $firstDebit = $voucher->items->firstWhere('type', 'debit');
            $firstCredit = $voucher->items->firstWhere('type', 'credit');

            $particulars = match ($voucher->voucher_type) {
                'sales' => $firstDebit?->ledger?->name ?? 'Sales',
                'purchase' => $firstCredit?->ledger?->name ?? 'Purchase',
                'payment' => $firstDebit?->ledger?->name ?? 'Payment',
                'receipt' => $firstCredit?->ledger?->name ?? 'Receipt',
                default => $voucher->items->pluck('ledger.name')->filter()->unique()->implode(', '),
            };

            return [
                'id' => $voucher->id,
                'date' => Carbon::parse($voucher->voucher_date)->format('d-m-Y'),
                'raw_date' => $voucher->voucher_date->format('Y-m-d'),
                'voucher_type' => ucfirst($voucher->voucher_type),
                'voucher_number' => $voucher->voucher_number,
                'particulars' => $particulars,
                'amount' => (float) $voucher->total_amount,
                'narration' => $voucher->narration,
                'debit_items' => $voucher->items->where('type', 'debit'),
                'credit_items' => $voucher->items->where('type', 'credit'),
            ];
        });
    }

    /**
     * Get detailed Ledger Statement (Account Book) with running balance.
     *
     * @return array<string, mixed>
     */
    public function getLedgerStatement(Ledger $ledger, ?string $fromDate = null, ?string $toDate = null): array
    {
        $fromDate = $fromDate ?: now()->startOfMonth()->toDateString();
        $toDate = $toDate ?: now()->toDateString();

        // 1. Calculate opening balance up to fromDate
        $priorItems = AccountingVoucherItem::where('ledger_id', $ledger->id)
            ->whereHas('accountingVoucher', function (Builder $query) use ($fromDate): void {
                $query->where('is_posted', true)
                    ->whereDate('voucher_date', '<', $fromDate);
            })
            ->get();

        $priorDebits = $priorItems->where('type', 'debit')->sum('amount');
        $priorCredits = $priorItems->where('type', 'credit')->sum('amount');

        $openingDebit = $ledger->opening_balance_type === 'debit' ? (float) $ledger->opening_balance : 0.0;
        $openingCredit = $ledger->opening_balance_type === 'credit' ? (float) $ledger->opening_balance : 0.0;

        $runningBalance = ($openingDebit + (float) $priorDebits) - ($openingCredit + (float) $priorCredits);

        $openingBalanceData = [
            'amount' => abs($runningBalance),
            'type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
            'formatted' => '₹'.number_format(abs($runningBalance), 2).' '.($runningBalance >= 0 ? 'Dr' : 'Cr'),
        ];

        // 2. Fetch period items
        $periodItems = AccountingVoucherItem::with(['accountingVoucher.items.ledger', 'costCentre'])
            ->where('ledger_id', $ledger->id)
            ->whereHas('accountingVoucher', function (Builder $query) use ($fromDate, $toDate): void {
                $query->where('is_posted', true)
                    ->whereDate('voucher_date', '>=', $fromDate)
                    ->whereDate('voucher_date', '<=', $toDate);
            })
            ->join('accounting_vouchers', 'accounting_voucher_items.accounting_voucher_id', '=', 'accounting_vouchers.id')
            ->orderBy('accounting_vouchers.voucher_date', 'asc')
            ->orderBy('accounting_vouchers.id', 'asc')
            ->select('accounting_voucher_items.*')
            ->get();

        $rows = collect();
        $totalPeriodDebit = 0.0;
        $totalPeriodCredit = 0.0;

        foreach ($periodItems as $item) {
            $voucher = $item->accountingVoucher;
            $isDebit = $item->type === 'debit';
            $amount = (float) $item->amount;

            if ($isDebit) {
                $totalPeriodDebit += $amount;
                $runningBalance += $amount;
            } else {
                $totalPeriodCredit += $amount;
                $runningBalance -= $amount;
            }

            // Find opposing ledger for particulars
            $opposingItem = $voucher->items->first(fn ($other) => $other->type !== $item->type);
            $opposingLedgerName = $opposingItem?->ledger?->name ?? 'As per details';

            $rows->push([
                'id' => $item->id,
                'voucher_id' => $voucher->id,
                'date' => Carbon::parse($voucher->voucher_date)->format('d-m-Y'),
                'voucher_type' => ucfirst($voucher->voucher_type),
                'voucher_number' => $voucher->voucher_number,
                'particulars' => $opposingLedgerName,
                'debit' => $isDebit ? $amount : null,
                'credit' => ! $isDebit ? $amount : null,
                'running_balance' => abs($runningBalance),
                'running_balance_type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
                'narration' => $voucher->narration ?: $item->particulars_note,
                'cost_centre' => $item->costCentre?->name,
            ]);
        }

        $closingBalanceData = [
            'amount' => abs($runningBalance),
            'type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
            'formatted' => '₹'.number_format(abs($runningBalance), 2).' '.($runningBalance >= 0 ? 'Dr' : 'Cr'),
        ];

        return [
            'ledger' => $ledger,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_balance' => $openingBalanceData,
            'closing_balance' => $closingBalanceData,
            'total_debit' => $totalPeriodDebit,
            'total_credit' => $totalPeriodCredit,
            'rows' => $rows,
        ];
    }

    /**
     * Generate Trial Balance for a company up to a date.
     *
     * @return array<string, mixed>
     */
    public function getTrialBalance(Company $company, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();

        $groups = AccountGroup::with(['ledgers' => function ($q) {
            $q->where('is_active', true);
        }])
            ->where('company_id', $company->id)
            ->whereNull('parent_id')
            ->get();

        $groupRows = [];
        $grandTotalDebit = 0.0;
        $grandTotalCredit = 0.0;

        foreach ($groups as $group) {
            $allSubGroupIds = AccountGroup::where('company_id', $company->id)
                ->where(function ($q) use ($group): void {
                    $q->where('id', $group->id)
                        ->orWhere('parent_id', $group->id);
                })
                ->pluck('id');

            $ledgers = Ledger::where('company_id', $company->id)
                ->whereIn('account_group_id', $allSubGroupIds)
                ->where('is_active', true)
                ->get();

            $groupDebit = 0.0;
            $groupCredit = 0.0;
            $ledgerDetails = [];

            foreach ($ledgers as $ledger) {
                $bal = $ledger->getCalculatedBalance($asOfDate);
                if ($bal['amount'] <= 0.001) {
                    continue;
                }

                if ($bal['type'] === 'debit') {
                    $groupDebit += $bal['amount'];
                    $grandTotalDebit += $bal['amount'];
                    $ledgerDetails[] = [
                        'id' => $ledger->id,
                        'name' => $ledger->name,
                        'debit' => $bal['amount'],
                        'credit' => 0.0,
                    ];
                } else {
                    $groupCredit += $bal['amount'];
                    $grandTotalCredit += $bal['amount'];
                    $ledgerDetails[] = [
                        'id' => $ledger->id,
                        'name' => $ledger->name,
                        'debit' => 0.0,
                        'credit' => $bal['amount'],
                    ];
                }
            }

            if (! empty($ledgerDetails)) {
                $groupRows[] = [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'primary_type' => $group->primary_type,
                    'total_debit' => $groupDebit,
                    'total_credit' => $groupCredit,
                    'ledgers' => $ledgerDetails,
                ];
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'groups' => $groupRows,
            'total_debit' => $grandTotalDebit,
            'total_credit' => $grandTotalCredit,
            'is_balanced' => abs($grandTotalDebit - $grandTotalCredit) < 0.01,
            'difference' => abs($grandTotalDebit - $grandTotalCredit),
        ];
    }

    /**
     * Generate Trading and Profit & Loss Account for a company between dates.
     *
     * @return array<string, mixed>
     */
    public function getProfitAndLoss(Company $company, ?string $fromDate = null, ?string $toDate = null): array
    {
        $fromDate = $fromDate ?: now()->startOfYear()->toDateString();
        $toDate = $toDate ?: now()->toDateString();

        $incomeGroups = AccountGroup::where('company_id', $company->id)
            ->where('primary_type', 'income')
            ->pluck('id');

        $expenseGroups = AccountGroup::where('company_id', $company->id)
            ->where('primary_type', 'expense')
            ->pluck('id');

        $incomeLedgers = Ledger::where('company_id', $company->id)
            ->whereIn('account_group_id', $incomeGroups)
            ->get();

        $expenseLedgers = Ledger::where('company_id', $company->id)
            ->whereIn('account_group_id', $expenseGroups)
            ->get();

        $directIncomes = [];
        $indirectIncomes = [];
        $totalIncome = 0.0;

        foreach ($incomeLedgers as $ledger) {
            $bal = $ledger->getCalculatedBalance($toDate);
            if ($bal['amount'] <= 0.001) {
                continue;
            }

            $totalIncome += $bal['amount'];
            if (str_contains(strtolower($ledger->accountGroup?->name ?? ''), 'direct')) {
                $directIncomes[] = ['name' => $ledger->name, 'amount' => $bal['amount']];
            } else {
                $indirectIncomes[] = ['name' => $ledger->name, 'amount' => $bal['amount']];
            }
        }

        $directExpenses = [];
        $indirectExpenses = [];
        $totalExpense = 0.0;

        foreach ($expenseLedgers as $ledger) {
            $bal = $ledger->getCalculatedBalance($toDate);
            if ($bal['amount'] <= 0.001) {
                continue;
            }

            $totalExpense += $bal['amount'];
            if (str_contains(strtolower($ledger->accountGroup?->name ?? ''), 'direct')) {
                $directExpenses[] = ['name' => $ledger->name, 'amount' => $bal['amount']];
            } else {
                $indirectExpenses[] = ['name' => $ledger->name, 'amount' => $bal['amount']];
            }
        }

        $totalDirectIncome = collect($directIncomes)->sum('amount');
        $totalDirectExpense = collect($directExpenses)->sum('amount');
        $grossProfit = $totalDirectIncome - $totalDirectExpense;

        $totalIndirectIncome = collect($indirectIncomes)->sum('amount');
        $totalIndirectExpense = collect($indirectExpenses)->sum('amount');
        $netProfit = $grossProfit + $totalIndirectIncome - $totalIndirectExpense;

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'direct_incomes' => $directIncomes,
            'total_direct_income' => $totalDirectIncome,
            'direct_expenses' => $directExpenses,
            'total_direct_expense' => $totalDirectExpense,
            'gross_profit' => $grossProfit,
            'indirect_incomes' => $indirectIncomes,
            'total_indirect_income' => $totalIndirectIncome,
            'indirect_expenses' => $indirectExpenses,
            'total_indirect_expense' => $totalIndirectExpense,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Generate Balance Sheet (Assets vs Liabilities) for a company.
     *
     * @return array<string, mixed>
     */
    public function getBalanceSheet(Company $company, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        $pnl = $this->getProfitAndLoss($company, null, $asOfDate);
        $netProfit = $pnl['net_profit'];

        $assetGroups = AccountGroup::where('company_id', $company->id)
            ->where('primary_type', 'asset')
            ->pluck('id');

        $liabilityGroups = AccountGroup::where('company_id', $company->id)
            ->whereIn('primary_type', ['liability', 'equity'])
            ->pluck('id');

        $assetLedgers = Ledger::where('company_id', $company->id)
            ->whereIn('account_group_id', $assetGroups)
            ->get();

        $liabilityLedgers = Ledger::where('company_id', $company->id)
            ->whereIn('account_group_id', $liabilityGroups)
            ->get();

        $assets = [];
        $totalAssets = 0.0;
        foreach ($assetLedgers as $ledger) {
            $bal = $ledger->getCalculatedBalance($asOfDate);
            if ($bal['amount'] <= 0.001) {
                continue;
            }
            $val = $bal['type'] === 'debit' ? $bal['amount'] : -$bal['amount'];
            $assets[] = ['name' => $ledger->name, 'amount' => $val];
            $totalAssets += $val;
        }

        $liabilities = [];
        $totalLiabilities = 0.0;
        foreach ($liabilityLedgers as $ledger) {
            $bal = $ledger->getCalculatedBalance($asOfDate);
            if ($bal['amount'] <= 0.001) {
                continue;
            }
            $val = $bal['type'] === 'credit' ? $bal['amount'] : -$bal['amount'];
            $liabilities[] = ['name' => $ledger->name, 'amount' => $val];
            $totalLiabilities += $val;
        }

        // Add net profit to liabilities (equity)
        if (abs($netProfit) > 0.001) {
            $liabilities[] = ['name' => 'Profit & Loss A/c (Current Period)', 'amount' => $netProfit];
            $totalLiabilities += $netProfit;
        }

        return [
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'difference' => round($totalAssets - $totalLiabilities, 2),
        ];
    }
}
