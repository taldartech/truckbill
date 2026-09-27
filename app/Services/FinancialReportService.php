<?php

namespace App\Services;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\Ledger;
use App\Models\Transporter;
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
    public function getLedgerStatement(Ledger $ledger, ?string $fromDate = null, ?string $toDate = null, ?string $searchQuery = null): array
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
            'reference' => $ledger->opening_balance_reference,
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

            // Extract Truck Number and LR Number from cost centre, reference or particulars
            $truckNumber = $item->costCentre?->name;
            $ref = (string) $voucher->reference_number;
            $note = (string) ($item->particulars_note ?: $voucher->narration);

            if (! $truckNumber && preg_match('/Truck[:\s#]+([A-Za-z0-9-]+)/i', $ref.' '.$note, $m)) {
                $truckNumber = trim($m[1]);
            }

            $lrNumber = null;
            if (preg_match('/LR[:\s#]+([A-Za-z0-9-]+)/i', $ref.' '.$note, $m)) {
                $lrNumber = trim($m[1]);
            }

            $rows->push([
                'id' => $item->id,
                'voucher_id' => $voucher->id,
                'date' => Carbon::parse($voucher->voucher_date)->format('d-m-Y'),
                'voucher_type' => ucfirst($voucher->voucher_type),
                'voucher_number' => $voucher->voucher_number,
                'reference_number' => $voucher->reference_number,
                'truck_number' => $truckNumber,
                'lr_number' => $lrNumber,
                'particulars' => $opposingLedgerName,
                'debit' => $isDebit ? $amount : null,
                'credit' => ! $isDebit ? $amount : null,
                'running_balance' => abs($runningBalance),
                'running_balance_type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
                'narration' => $voucher->narration ?: $item->particulars_note,
                'particulars_note' => $item->particulars_note,
                'cost_centre' => $item->costCentre?->name,
            ]);
        }

        // Check if this ledger is Cash / Bank / Asset and has abnormal Credit balance
        $isCashOrAsset = str_contains(strtolower($ledger->name), 'cash')
            || str_contains(strtolower($ledger->accountGroup?->name ?? ''), 'cash')
            || $ledger->code === 'CASH';
        $isNegativeCash = $isCashOrAsset && $runningBalance < -0.01;

        $closingBalanceData = [
            'amount' => abs($runningBalance),
            'type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
            'formatted' => '₹'.number_format(abs($runningBalance), 2).' '.($runningBalance >= 0 ? 'Dr' : 'Cr'),
            'is_negative_cash' => $isNegativeCash,
            'is_abnormal' => $isNegativeCash,
        ];

        // Apply search query filter if provided (matching Truck, LR, Voucher No, Narration, Opposing Ledger)
        $filteredRows = $rows;
        if ($searchQuery !== null && trim($searchQuery) !== '') {
            $q = strtolower(trim($searchQuery));
            $filteredRows = $rows->filter(function ($row) use ($q) {
                return str_contains(strtolower((string) $row['voucher_number']), $q)
                    || str_contains(strtolower((string) $row['reference_number']), $q)
                    || str_contains(strtolower((string) $row['truck_number']), $q)
                    || str_contains(strtolower((string) $row['lr_number']), $q)
                    || str_contains(strtolower((string) $row['particulars']), $q)
                    || str_contains(strtolower((string) $row['narration']), $q)
                    || str_contains(strtolower((string) $row['particulars_note']), $q);
            })->values();
        }

        return [
            'ledger' => $ledger,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'search_query' => $searchQuery,
            'opening_balance' => $openingBalanceData,
            'closing_balance' => $closingBalanceData,
            'total_debit' => $totalPeriodDebit,
            'total_credit' => $totalPeriodCredit,
            'rows' => $filteredRows,
            'unfiltered_count' => $rows->count(),
            'is_filtered' => $searchQuery !== null && trim($searchQuery) !== '',
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
     * Get Bill-to-Bill Outstandings / Invoice settlements for a party ledger (Customer or Transporter).
     *
     * @return array<string, mixed>|null
     */
    public function getBillToBillOutstandings(Ledger $ledger, ?string $statusFilter = null): ?array
    {
        $bills = collect();

        // 1. Customer (Sundry Debtors) -> Invoices
        if ($ledger->entity_type === Consignee::class && $ledger->entity_id) {
            $invoices = Invoice::with('payments')
                ->where('customer_id', $ledger->entity_id)
                ->where('company_id', $ledger->company_id)
                ->orderBy('generated_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            foreach ($invoices as $inv) {
                $billAmount = (float) ($inv->net_amount ?? $inv->total_amount);
                $receivedAmount = (float) $inv->payments->sum(fn ($p) => (float) $p->amount + (float) ($p->tds_amount ?? 0) + (float) ($p->deduction_amount ?? 0));
                $pendingAmount = max(0.0, round($billAmount - $receivedAmount, 2));

                $status = 'pending';
                if ($pendingAmount <= 0.01) {
                    $status = 'settled';
                } elseif ($receivedAmount > 0.01) {
                    $status = 'partial';
                }

                $bills->push([
                    'type' => 'invoice',
                    'id' => $inv->id,
                    'bill_no' => $inv->invoice_number ?: ($inv->manual_invoice_number ?: 'INV-'.$inv->id),
                    'bill_date' => $inv->generated_date ? Carbon::parse($inv->generated_date)->format('d-m-Y') : '-',
                    'raw_date' => $inv->generated_date,
                    'total_amount' => $billAmount,
                    'cleared_amount' => $receivedAmount,
                    'pending_amount' => $pendingAmount,
                    'status' => $status,
                    'payments' => $inv->payments->map(function ($p) {
                        $parts = ['₹'.number_format((float) $p->amount, 2).' ('.ucfirst($p->mode).')'];
                        if ((float) ($p->tds_amount ?? 0) > 0) {
                            $parts[] = 'TDS: ₹'.number_format((float) $p->tds_amount, 2);
                        }
                        if ((float) ($p->deduction_amount ?? 0) > 0) {
                            $parts[] = 'Ded: ₹'.number_format((float) $p->deduction_amount, 2);
                        }

                        return [
                            'date' => Carbon::parse($p->payment_date)->format('d-m-Y'),
                            'mode' => ucfirst($p->mode),
                            'amount' => (float) $p->amount,
                            'tds_amount' => (float) ($p->tds_amount ?? 0),
                            'deduction_amount' => (float) ($p->deduction_amount ?? 0),
                            'deduction_reason' => $p->deduction_reason,
                            'total_settled' => (float) $p->amount + (float) ($p->tds_amount ?? 0) + (float) ($p->deduction_amount ?? 0),
                            'display_text' => implode(' + ', $parts),
                            'remark' => $p->remark,
                        ];
                    }),
                ]);
            }
        }

        // 2. Transporter / Truck Owner (Sundry Creditors) -> Trip Freight Bookings
        if ($ledger->entity_type === Transporter::class && $ledger->entity_id) {
            $bookings = FreightBooking::with(['lr', 'truck', 'payments'])
                ->where(function ($q) use ($ledger) {
                    $q->where('transporter_id', $ledger->entity_id)
                        ->orWhereHas('truck', fn ($tq) => $tq->where('transporter_id', $ledger->entity_id));
                })
                ->where('company_id', $ledger->company_id)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($bookings as $fb) {
                $billAmount = (float) ($fb->tr_amount ?: $fb->total_amount);
                $paidAmount = (float) $fb->payments->sum('amount');
                $pendingAmount = max(0.0, round((float) ($fb->balance_amount ?? ($billAmount - $paidAmount)), 2));

                $status = 'pending';
                if ($pendingAmount <= 0.01) {
                    $status = 'settled';
                } elseif ($paidAmount > 0.01) {
                    $status = 'partial';
                }

                $lrNo = $fb->lr?->lr_number ?? ($fb->lr_id ? 'LR-'.$fb->lr_id : 'N/A');
                $truckNo = $fb->truck?->truck_number ?? 'N/A';

                $bills->push([
                    'type' => 'booking',
                    'id' => $fb->id,
                    'bill_no' => $fb->voucher_number ?: 'LR-'.$lrNo,
                    'truck_no' => $truckNo,
                    'lr_no' => $lrNo,
                    'bill_date' => $fb->created_at ? $fb->created_at->format('d-m-Y') : '-',
                    'raw_date' => $fb->created_at?->toDateString(),
                    'total_amount' => $billAmount,
                    'cleared_amount' => $paidAmount,
                    'pending_amount' => $pendingAmount,
                    'status' => $status,
                    'payments' => $fb->payments->map(fn ($p) => [
                        'date' => Carbon::parse($p->date ?: $p->created_at)->format('d-m-Y'),
                        'mode' => ucfirst($p->payment_mode),
                        'amount' => (float) $p->amount,
                        'remark' => $p->remark,
                    ]),
                ]);
            }
        }

        if ($bills->isEmpty() && ! in_array($ledger->entity_type, [Consignee::class, Transporter::class], true)) {
            return null;
        }

        $totalBilled = (float) $bills->sum('total_amount');
        $totalCleared = (float) $bills->sum('cleared_amount');
        $totalPending = (float) $bills->sum('pending_amount');

        $filteredBills = $bills;
        if ($statusFilter && in_array($statusFilter, ['pending', 'partial', 'settled'], true)) {
            $filteredBills = $bills->where('status', $statusFilter)->values();
        }

        return [
            'entity_type' => $ledger->entity_type === Consignee::class ? 'Customer' : 'Transporter',
            'status_filter' => $statusFilter,
            'total_billed' => $totalBilled,
            'total_cleared' => $totalCleared,
            'total_pending' => $totalPending,
            'pending_count' => $bills->whereIn('status', ['pending', 'partial'])->count(),
            'settled_count' => $bills->where('status', 'settled')->count(),
            'total_count' => $bills->count(),
            'unfiltered_count' => $bills->count(),
            'bills' => $filteredBills,
        ];
    }

    /**
     * Check if an account group is a Direct Income or Direct Expense group.
     */
    public function isDirectGroup(?AccountGroup $group, string $type): bool
    {
        if (! $group) {
            return false;
        }

        $name = strtolower(trim((string) $group->name));
        if (str_contains($name, 'direct')) {
            return true;
        }

        if ($type === 'income') {
            $directIncomeGroups = ['freight revenue', 'detention & demurrage charges'];
            if (in_array($name, $directIncomeGroups, true)) {
                return true;
            }
        } elseif ($type === 'expense') {
            $directExpenseGroups = [
                'transporter freight hiring charges',
                'diesel & fuel expenses',
                'toll & border taxes',
                'driver bhatta & expenses',
            ];
            if (in_array($name, $directExpenseGroups, true)) {
                return true;
            }
        }

        if ($group->parent_id) {
            $parent = AccountGroup::find($group->parent_id);
            if ($parent && str_contains(strtolower($parent->name), 'direct')) {
                return true;
            }
        }

        return false;
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
            if ($this->isDirectGroup($ledger->accountGroup, 'income')) {
                $directIncomes[] = ['name' => $ledger->name, 'amount' => $bal['amount'], 'id' => $ledger->id];
            } else {
                $indirectIncomes[] = ['name' => $ledger->name, 'amount' => $bal['amount'], 'id' => $ledger->id];
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
            if ($this->isDirectGroup($ledger->accountGroup, 'expense')) {
                $directExpenses[] = ['name' => $ledger->name, 'amount' => $bal['amount'], 'id' => $ledger->id];
            } else {
                $indirectExpenses[] = ['name' => $ledger->name, 'amount' => $bal['amount'], 'id' => $ledger->id];
            }
        }

        $totalDirectIncome = (float) collect($directIncomes)->sum('amount');
        $totalDirectExpense = (float) collect($directExpenses)->sum('amount');
        $grossProfit = round($totalDirectIncome - $totalDirectExpense, 2);

        $totalIndirectIncome = (float) collect($indirectIncomes)->sum('amount');
        $totalIndirectExpense = (float) collect($indirectExpenses)->sum('amount');
        $netProfit = round($grossProfit + $totalIndirectIncome - $totalIndirectExpense, 2);

        $tradingTotal = max($totalDirectIncome, $totalDirectExpense + max(0, $grossProfit));
        $pnlDebitTotal = $totalIndirectExpense + ($grossProfit < 0 ? abs($grossProfit) : 0) + ($netProfit >= 0 ? $netProfit : 0);
        $pnlCreditTotal = $totalIndirectIncome + ($grossProfit >= 0 ? $grossProfit : 0) + ($netProfit < 0 ? abs($netProfit) : 0);
        $pnlTotal = max($pnlDebitTotal, $pnlCreditTotal);

        // Build Structured Primary Groups for P&L presentation
        $buildGroupHierarchy = function (string $type) use ($company, $toDate) {
            $primaryGroups = AccountGroup::where('company_id', $company->id)
                ->where('primary_type', $type)
                ->whereNull('parent_id')
                ->orderBy('id')
                ->get();

            $result = [];
            foreach ($primaryGroups as $pGroup) {
                $subGroups = AccountGroup::where('company_id', $company->id)
                    ->where('parent_id', $pGroup->id)
                    ->orderBy('name')
                    ->get();

                $pGroupSubtotal = 0.0;
                $subGroupItems = [];

                foreach ($subGroups as $subG) {
                    $grandChildren = AccountGroup::where('company_id', $company->id)
                        ->where('parent_id', $subG->id)
                        ->pluck('id');

                    $allSubGIds = $grandChildren->push($subG->id);

                    $subGLedgers = Ledger::where('company_id', $company->id)
                        ->whereIn('account_group_id', $allSubGIds)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get();

                    $subGSubtotal = 0.0;
                    $ledgerLines = [];
                    foreach ($subGLedgers as $l) {
                        $bal = $l->getCalculatedBalance($toDate);
                        if ($bal['amount'] <= 0.001) {
                            continue;
                        }
                        $val = $bal['amount'];
                        $ledgerLines[] = [
                            'id' => $l->id,
                            'name' => $l->name,
                            'amount' => $val,
                            'formatted' => '₹'.number_format($val, 2),
                        ];
                        $subGSubtotal += $val;
                    }

                    if (! empty($ledgerLines)) {
                        $subGroupItems[] = [
                            'id' => $subG->id,
                            'name' => $subG->name,
                            'subtotal' => $subGSubtotal,
                            'ledgers' => $ledgerLines,
                        ];
                        $pGroupSubtotal += $subGSubtotal;
                    }
                }

                $directLedgers = Ledger::where('company_id', $company->id)
                    ->where('account_group_id', $pGroup->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();

                $directLines = [];
                foreach ($directLedgers as $l) {
                    $bal = $l->getCalculatedBalance($toDate);
                    if ($bal['amount'] <= 0.001) {
                        continue;
                    }
                    $val = $bal['amount'];
                    $directLines[] = [
                        'id' => $l->id,
                        'name' => $l->name,
                        'amount' => $val,
                        'formatted' => '₹'.number_format($val, 2),
                    ];
                    $pGroupSubtotal += $val;
                }

                $result[] = [
                    'id' => $pGroup->id,
                    'name' => $pGroup->name,
                    'subtotal' => $pGroupSubtotal,
                    'sub_groups' => $subGroupItems,
                    'direct_ledgers' => $directLines,
                ];
            }

            return $result;
        };

        $rawExpenseGroups = $buildGroupHierarchy('expense');
        $rawIncomeGroups = $buildGroupHierarchy('income');

        // Ensure canonical Tally groups exist (even with 0 balance) for authentic presentation
        $hasPurchase = collect($rawExpenseGroups)->contains(function ($g) {
            return str_contains(strtolower($g['name']), 'purchase');
        });
        if (! $hasPurchase) {
            array_unshift($rawExpenseGroups, [
                'id' => null,
                'name' => 'Purchase Accounts',
                'subtotal' => 0.0,
                'sub_groups' => [],
                'direct_ledgers' => [],
            ]);
        }

        $hasDirectExpenses = collect($rawExpenseGroups)->contains(function ($g) {
            return str_contains(strtolower($g['name']), 'direct expense');
        });
        if (! $hasDirectExpenses) {
            $rawExpenseGroups[] = [
                'id' => null,
                'name' => 'Direct Expenses',
                'subtotal' => 0.0,
                'sub_groups' => [],
                'direct_ledgers' => [],
            ];
        }

        $hasIndirectExpenses = collect($rawExpenseGroups)->contains(function ($g) {
            return str_contains(strtolower($g['name']), 'indirect expense');
        });
        if (! $hasIndirectExpenses) {
            $rawExpenseGroups[] = [
                'id' => null,
                'name' => 'Indirect Expenses',
                'subtotal' => 0.0,
                'sub_groups' => [],
                'direct_ledgers' => [],
            ];
        }

        $expenseOrder = ['opening stock' => 1, 'purchase' => 2, 'direct expense' => 3, 'indirect expense' => 4];
        usort($rawExpenseGroups, function ($a, $b) use ($expenseOrder) {
            $aKey = 99;
            foreach ($expenseOrder as $k => $pos) {
                if (str_contains(strtolower($a['name']), $k)) { $aKey = $pos; break; }
            }
            $bKey = 99;
            foreach ($expenseOrder as $k => $pos) {
                if (str_contains(strtolower($b['name']), $k)) { $bKey = $pos; break; }
            }
            return $aKey <=> $bKey;
        });

        $hasSales = collect($rawIncomeGroups)->contains(function ($g) {
            return str_contains(strtolower($g['name']), 'sales');
        });
        if (! $hasSales) {
            array_unshift($rawIncomeGroups, [
                'id' => null,
                'name' => 'Sales Accounts',
                'subtotal' => 0.0,
                'sub_groups' => [],
                'direct_ledgers' => [],
            ]);
        }

        $hasDirectIncomes = collect($rawIncomeGroups)->contains(function ($g) {
            return str_contains(strtolower($g['name']), 'direct income');
        });
        if (! $hasDirectIncomes) {
            $rawIncomeGroups[] = [
                'id' => null,
                'name' => 'Direct Incomes',
                'subtotal' => 0.0,
                'sub_groups' => [],
                'direct_ledgers' => [],
            ];
        }

        $hasIndirectIncomes = collect($rawIncomeGroups)->contains(function ($g) {
            return str_contains(strtolower($g['name']), 'indirect income');
        });
        if (! $hasIndirectIncomes) {
            $rawIncomeGroups[] = [
                'id' => null,
                'name' => 'Indirect Incomes',
                'subtotal' => 0.0,
                'sub_groups' => [],
                'direct_ledgers' => [],
            ];
        }

        $incomeOrder = ['sales' => 1, 'direct income' => 2, 'closing stock' => 3, 'indirect income' => 4];
        usort($rawIncomeGroups, function ($a, $b) use ($incomeOrder) {
            $aKey = 99;
            foreach ($incomeOrder as $k => $pos) {
                if (str_contains(strtolower($a['name']), $k)) { $aKey = $pos; break; }
            }
            $bKey = 99;
            foreach ($incomeOrder as $k => $pos) {
                if (str_contains(strtolower($b['name']), $k)) { $bKey = $pos; break; }
            }
            return $aKey <=> $bKey;
        });

        $totalExpense = (float) collect($rawExpenseGroups)->sum('subtotal');
        $totalIncome = (float) collect($rawIncomeGroups)->sum('subtotal');
        $grandTotal = max($totalExpense + max(0, $netProfit), $totalIncome + max(0, -$netProfit));

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'direct_incomes' => $directIncomes,
            'total_direct_income' => $totalDirectIncome,
            'direct_expenses' => $directExpenses,
            'total_direct_expense' => $totalDirectExpense,
            'gross_profit' => $grossProfit,
            'trading_total' => $tradingTotal,
            'indirect_incomes' => $indirectIncomes,
            'total_indirect_income' => $totalIndirectIncome,
            'indirect_expenses' => $indirectExpenses,
            'total_indirect_expense' => $totalIndirectExpense,
            'net_profit' => $netProfit,
            'pnl_total' => $pnlTotal,
            'expense_groups' => $rawExpenseGroups,
            'income_groups' => $rawIncomeGroups,
            'total_expense' => $totalExpense,
            'total_income' => $totalIncome,
            'grand_total' => $grandTotal,
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

        // 1. Grouped Liabilities
        $liabilityPrimaryGroups = AccountGroup::where('company_id', $company->id)
            ->whereIn('primary_type', ['liability', 'equity'])
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        $liabilityGroups = [];
        $flatLiabilities = [];
        $totalLiabilities = 0.0;

        foreach ($liabilityPrimaryGroups as $pGroup) {
            $subGroups = AccountGroup::where('company_id', $company->id)
                ->where('parent_id', $pGroup->id)
                ->orderBy('name')
                ->get();

            $pGroupSubtotal = 0.0;
            $subGroupItems = [];

            foreach ($subGroups as $subG) {
                $grandChildren = AccountGroup::where('company_id', $company->id)
                    ->where('parent_id', $subG->id)
                    ->pluck('id');

                $allSubGIds = $grandChildren->push($subG->id);

                $subGLedgers = Ledger::where('company_id', $company->id)
                    ->whereIn('account_group_id', $allSubGIds)
                    ->where('is_active', true)
                    ->get();

                $subGSubtotal = 0.0;
                $ledgerLines = [];
                foreach ($subGLedgers as $l) {
                    $bal = $l->getCalculatedBalance($asOfDate);
                    if ($bal['amount'] <= 0.001) {
                        continue;
                    }
                    $val = $bal['type'] === 'credit' ? $bal['amount'] : -$bal['amount'];
                    $ledgerLines[] = [
                        'id' => $l->id,
                        'name' => $l->name,
                        'amount' => $val,
                        'formatted' => '₹'.number_format($val, 2),
                    ];
                    $subGSubtotal += $val;
                    $flatLiabilities[] = ['name' => $l->name, 'amount' => $val, 'id' => $l->id];
                }

                if (! empty($ledgerLines)) {
                    $subGroupItems[] = [
                        'id' => $subG->id,
                        'name' => $subG->name,
                        'subtotal' => $subGSubtotal,
                        'ledgers' => $ledgerLines,
                    ];
                    $pGroupSubtotal += $subGSubtotal;
                }
            }

            $directLedgers = Ledger::where('company_id', $company->id)
                ->where('account_group_id', $pGroup->id)
                ->where('is_active', true)
                ->get();

            $directLines = [];
            foreach ($directLedgers as $l) {
                $bal = $l->getCalculatedBalance($asOfDate);
                if ($bal['amount'] <= 0.001) {
                    continue;
                }
                $val = $bal['type'] === 'credit' ? $bal['amount'] : -$bal['amount'];
                $directLines[] = [
                    'id' => $l->id,
                    'name' => $l->name,
                    'amount' => $val,
                    'formatted' => '₹'.number_format($val, 2),
                ];
                $pGroupSubtotal += $val;
                $flatLiabilities[] = ['name' => $l->name, 'amount' => $val, 'id' => $l->id];
            }

            if (! empty($subGroupItems) || ! empty($directLines)) {
                $liabilityGroups[] = [
                    'id' => $pGroup->id,
                    'name' => $pGroup->name,
                    'subtotal' => $pGroupSubtotal,
                    'sub_groups' => $subGroupItems,
                    'direct_ledgers' => $directLines,
                ];
                $totalLiabilities += $pGroupSubtotal;
            }
        }

        // Add Net Profit / Loss to liabilities (Equity section)
        if (abs($netProfit) > 0.001) {
            $liabilityGroups[] = [
                'id' => 'pnl',
                'name' => 'Profit & Loss A/c',
                'subtotal' => $netProfit,
                'sub_groups' => [],
                'direct_ledgers' => [
                    [
                        'id' => null,
                        'name' => $netProfit >= 0 ? 'Current Period Net Profit' : 'Current Period Net Loss',
                        'amount' => $netProfit,
                        'formatted' => '₹'.number_format($netProfit, 2),
                    ],
                ],
            ];
            $flatLiabilities[] = ['name' => 'Profit & Loss A/c (Current Period)', 'amount' => $netProfit, 'id' => null];
            $totalLiabilities += $netProfit;
        }

        // 2. Grouped Assets
        $assetPrimaryGroups = AccountGroup::where('company_id', $company->id)
            ->where('primary_type', 'asset')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        $assetGroups = [];
        $flatAssets = [];
        $totalAssets = 0.0;

        foreach ($assetPrimaryGroups as $pGroup) {
            $subGroups = AccountGroup::where('company_id', $company->id)
                ->where('parent_id', $pGroup->id)
                ->orderBy('name')
                ->get();

            $pGroupSubtotal = 0.0;
            $subGroupItems = [];

            foreach ($subGroups as $subG) {
                $grandChildren = AccountGroup::where('company_id', $company->id)
                    ->where('parent_id', $subG->id)
                    ->pluck('id');

                $allSubGIds = $grandChildren->push($subG->id);

                $subGLedgers = Ledger::where('company_id', $company->id)
                    ->whereIn('account_group_id', $allSubGIds)
                    ->where('is_active', true)
                    ->get();

                $subGSubtotal = 0.0;
                $ledgerLines = [];
                foreach ($subGLedgers as $l) {
                    $bal = $l->getCalculatedBalance($asOfDate);
                    if ($bal['amount'] <= 0.001) {
                        continue;
                    }
                    $val = $bal['type'] === 'debit' ? $bal['amount'] : -$bal['amount'];
                    $isNegativeCash = (str_contains(strtolower($l->name), 'cash') || $l->code === 'CASH') && $val < 0;
                    $ledgerLines[] = [
                        'id' => $l->id,
                        'name' => $l->name,
                        'amount' => $val,
                        'is_negative_cash' => $isNegativeCash,
                        'formatted' => '₹'.number_format($val, 2),
                    ];
                    $subGSubtotal += $val;
                    $flatAssets[] = ['name' => $l->name, 'amount' => $val, 'id' => $l->id, 'is_negative_cash' => $isNegativeCash];
                }

                if (! empty($ledgerLines)) {
                    $subGroupItems[] = [
                        'id' => $subG->id,
                        'name' => $subG->name,
                        'subtotal' => $subGSubtotal,
                        'ledgers' => $ledgerLines,
                    ];
                    $pGroupSubtotal += $subGSubtotal;
                }
            }

            $directLedgers = Ledger::where('company_id', $company->id)
                ->where('account_group_id', $pGroup->id)
                ->where('is_active', true)
                ->get();

            $directLines = [];
            foreach ($directLedgers as $l) {
                $bal = $l->getCalculatedBalance($asOfDate);
                if ($bal['amount'] <= 0.001) {
                    continue;
                }
                $val = $bal['type'] === 'debit' ? $bal['amount'] : -$bal['amount'];
                $isNegativeCash = (str_contains(strtolower($l->name), 'cash') || $l->code === 'CASH') && $val < 0;
                $directLines[] = [
                    'id' => $l->id,
                    'name' => $l->name,
                    'amount' => $val,
                    'is_negative_cash' => $isNegativeCash,
                    'formatted' => '₹'.number_format($val, 2),
                ];
                $pGroupSubtotal += $val;
                $flatAssets[] = ['name' => $l->name, 'amount' => $val, 'id' => $l->id, 'is_negative_cash' => $isNegativeCash];
            }

            if (! empty($subGroupItems) || ! empty($directLines)) {
                $assetGroups[] = [
                    'id' => $pGroup->id,
                    'name' => $pGroup->name,
                    'subtotal' => $pGroupSubtotal,
                    'sub_groups' => $subGroupItems,
                    'direct_ledgers' => $directLines,
                ];
                $totalAssets += $pGroupSubtotal;
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'liability_groups' => $liabilityGroups,
            'asset_groups' => $assetGroups,
            'assets' => $flatAssets,
            'total_assets' => $totalAssets,
            'liabilities' => $flatLiabilities,
            'total_liabilities' => $totalLiabilities,
            'difference' => round($totalAssets - $totalLiabilities, 2),
        ];
    }

    /**
     * Get GSTR-1 Return Preparation Report (Table 4A Forward Charge vs Table 4B Reverse Charge).
     *
     * @return array<string, mixed>
     */
    public function getGstr1Report(Company $company, ?string $fromDate = null, ?string $toDate = null): array
    {
        $fromDate = $fromDate ?: now()->startOfMonth()->toDateString();
        $toDate = $toDate ?: now()->toDateString();

        $invoices = Invoice::with(['consignee.state', 'company.state'])
            ->where('company_id', $company->id)
            ->whereDate('generated_date', '>=', $fromDate)
            ->whereDate('generated_date', '<=', $toDate)
            ->orderBy('generated_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $table4A = collect(); // B2B Forward Charge
        $table4B = collect(); // B2B Reverse Charge (GTA RCM)

        foreach ($invoices as $inv) {
            $taxable = (float) ($inv->base_amount ?? ($inv->net_amount ?? $inv->total_amount));
            $cgst = (float) ($inv->cgst ?? 0.0);
            $sgst = (float) ($inv->sgst ?? 0.0);
            $igst = (float) ($inv->igst ?? 0.0);
            $totalTax = round($cgst + $sgst + $igst, 2);
            $invoiceValue = (float) ($inv->total_amount ?? $inv->net_amount);

            $item = [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number ?: ($inv->manual_invoice_number ?: 'INV-'.$inv->id),
                'invoice_date' => $inv->generated_date ? Carbon::parse($inv->generated_date)->format('d-m-Y') : '-',
                'raw_date' => $inv->generated_date,
                'customer_name' => $inv->consignee?->name ?? 'Unregistered / Direct Customer',
                'customer_gstin' => $inv->consignee?->gst_number ?: 'URP',
                'place_of_supply' => $inv->consignee?->state?->name ?? ($company->state?->name ?? 'Intrastate'),
                'invoice_type' => ucfirst((string) $inv->invoice_type),
                'is_rcm' => $inv->is_rcm,
                'reverse_charge' => $inv->is_rcm ? 'Yes' : 'No',
                'taxable_value' => $taxable,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'total_tax' => $totalTax,
                'invoice_value' => $invoiceValue,
            ];

            if ($inv->is_rcm) {
                $table4B->push($item);
            } else {
                $table4A->push($item);
            }
        }

        $calcTotals = fn (Collection $coll) => [
            'count' => $coll->count(),
            'taxable_value' => round($coll->sum('taxable_value'), 2),
            'cgst' => round($coll->sum('cgst'), 2),
            'sgst' => round($coll->sum('sgst'), 2),
            'igst' => round($coll->sum('igst'), 2),
            'total_tax' => round($coll->sum('total_tax'), 2),
            'invoice_value' => round($coll->sum('invoice_value'), 2),
        ];

        return [
            'company' => $company,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'table_4a' => $table4A,
            'table_4a_totals' => $calcTotals($table4A),
            'table_4b' => $table4B,
            'table_4b_totals' => $calcTotals($table4B),
            'overall_totals' => [
                'count' => $invoices->count(),
                'taxable_value' => round($table4A->sum('taxable_value') + $table4B->sum('taxable_value'), 2),
                'cgst' => round($table4A->sum('cgst') + $table4B->sum('cgst'), 2),
                'sgst' => round($table4A->sum('sgst') + $table4B->sum('sgst'), 2),
                'igst' => round($table4A->sum('igst') + $table4B->sum('igst'), 2),
                'total_tax' => round($table4A->sum('total_tax') + $table4B->sum('total_tax'), 2),
                'invoice_value' => round($table4A->sum('invoice_value') + $table4B->sum('invoice_value'), 2),
            ],
        ];
    }

    /**
     * Get Trade Receivables (Sundry Debtors) Aging Report with MSME 45-day overdue alerts.
     *
     * @return array<string, mixed>
     */
    public function getDebtorsAgingReport(Company $company, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        $refDate = Carbon::parse($asOfDate)->startOfDay();

        $invoices = Invoice::with(['consignee', 'payments'])
            ->where('company_id', $company->id)
            ->whereDate('generated_date', '<=', $asOfDate)
            ->orderBy('generated_date', 'asc')
            ->get();

        $customerGroups = [];

        foreach ($invoices as $inv) {
            $billAmount = (float) ($inv->net_amount ?? $inv->total_amount);
            $paidAmount = (float) $inv->payments
                ->where('payment_date', '<=', $asOfDate)
                ->sum(fn ($p) => (float) $p->amount + (float) ($p->tds_amount ?? 0) + (float) ($p->deduction_amount ?? 0));

            $pending = max(0.0, round($billAmount - $paidAmount, 2));
            if ($pending <= 0.01) {
                continue;
            }

            $invDate = Carbon::parse($inv->generated_date)->startOfDay();
            $ageDays = max(0, $invDate->diffInDays($refDate, false));
            $isMsmeOverdue = $ageDays > 45;

            $customerId = $inv->customer_id ?: 0;
            $customerName = $inv->consignee?->name ?? 'Walk-in / Direct Debtors';
            $gstNumber = $inv->consignee?->gst_number ?? '-';
            $contact = $inv->consignee?->contact_number ?? '-';

            if (! isset($customerGroups[$customerId])) {
                $customerGroups[$customerId] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customerName,
                    'gst_number' => $gstNumber,
                    'contact_number' => $contact,
                    'total_pending' => 0.0,
                    'bucket_0_30' => 0.0,
                    'bucket_31_60' => 0.0,
                    'bucket_61_90' => 0.0,
                    'bucket_over_90' => 0.0,
                    'msme_overdue_amount' => 0.0,
                    'has_msme_alert' => false,
                    'invoices' => [],
                ];
            }

            $customerGroups[$customerId]['total_pending'] += $pending;

            if ($ageDays <= 30) {
                $customerGroups[$customerId]['bucket_0_30'] += $pending;
            } elseif ($ageDays <= 60) {
                $customerGroups[$customerId]['bucket_31_60'] += $pending;
            } elseif ($ageDays <= 90) {
                $customerGroups[$customerId]['bucket_61_90'] += $pending;
            } else {
                $customerGroups[$customerId]['bucket_over_90'] += $pending;
            }

            if ($isMsmeOverdue) {
                $customerGroups[$customerId]['msme_overdue_amount'] += $pending;
                $customerGroups[$customerId]['has_msme_alert'] = true;
            }

            $customerGroups[$customerId]['invoices'][] = [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number ?: ($inv->manual_invoice_number ?: 'INV-'.$inv->id),
                'date' => $inv->generated_date ? Carbon::parse($inv->generated_date)->format('d-m-Y') : '-',
                'age_days' => $ageDays,
                'is_msme_overdue' => $isMsmeOverdue,
                'total_amount' => $billAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pending,
            ];
        }

        $customers = collect(array_values($customerGroups))->sortByDesc('total_pending')->values();

        return [
            'company' => $company,
            'as_of_date' => $asOfDate,
            'customers' => $customers,
            'totals' => [
                'total_receivables' => round($customers->sum('total_pending'), 2),
                'total_0_30' => round($customers->sum('bucket_0_30'), 2),
                'total_31_60' => round($customers->sum('bucket_31_60'), 2),
                'total_61_90' => round($customers->sum('bucket_61_90'), 2),
                'total_over_90' => round($customers->sum('bucket_over_90'), 2),
                'total_msme_overdue' => round($customers->sum('msme_overdue_amount'), 2),
                'msme_alert_customers_count' => $customers->where('has_msme_alert', true)->count(),
                'total_debtors_count' => $customers->count(),
            ],
        ];
    }
}
