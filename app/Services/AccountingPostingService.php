<?php

namespace App\Services;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\CostCentre;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Ledger;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\VoucherPayment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingPostingService
{
    public function __construct(
        private readonly ChartOfAccountsProvisioner $provisioner
    ) {}

    /**
     * Ensure standard accounts are provisioned and find or create a ledger for an entity.
     */
    public function getOrCreatePartyLedger(Company $company, mixed $entity): Ledger
    {
        $this->provisioner->provisionForCompany($company);

        if ($entity instanceof Consignee) {
            $group = AccountGroup::where('company_id', $company->id)
                ->where('name', 'Sundry Debtors')
                ->first();

            return Ledger::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'entity_type' => Consignee::class,
                    'entity_id' => $entity->id,
                ],
                [
                    'account_group_id' => $group->id,
                    'name' => $entity->name.' (Customer)',
                    'gstin' => $entity->gst_number,
                    'pan_number' => $entity->pan_number,
                    'opening_balance' => 0.00,
                    'opening_balance_type' => 'debit',
                ]
            );
        }

        if ($entity instanceof Transporter) {
            $group = AccountGroup::where('company_id', $company->id)
                ->where('name', 'Sundry Creditors')
                ->first();

            return Ledger::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'entity_type' => Transporter::class,
                    'entity_id' => $entity->id,
                ],
                [
                    'account_group_id' => $group->id,
                    'name' => $entity->name.($entity->is_owner ? ' (Owner)' : ' (Transporter)'),
                    'gstin' => $entity->gst_number,
                    'pan_number' => $entity->pan_number,
                    'tds_section' => '194C',
                    'tds_rate' => $entity->tds_percentage,
                    'opening_balance' => 0.00,
                    'opening_balance_type' => 'credit',
                ]
            );
        }

        if ($entity instanceof Pump) {
            $group = AccountGroup::where('company_id', $company->id)
                ->where('name', 'Sundry Creditors')
                ->first();

            return Ledger::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'entity_type' => Pump::class,
                    'entity_id' => $entity->id,
                ],
                [
                    'account_group_id' => $group->id,
                    'name' => $entity->name.' (Fuel Pump)',
                    'opening_balance' => 0.00,
                    'opening_balance_type' => 'credit',
                ]
            );
        }

        throw new InvalidArgumentException('Unsupported entity for ledger creation: '.get_debug_type($entity));
    }

    /**
     * Get or create a Cost Centre for a Truck.
     */
    public function getOrCreateTruckCostCentre(Company $company, ?Truck $truck): ?CostCentre
    {
        if (! $truck) {
            return null;
        }

        return CostCentre::firstOrCreate(
            [
                'company_id' => $company->id,
                'category' => 'truck',
                'name' => $truck->truck_number,
            ],
            [
                'entity_type' => Truck::class,
                'entity_id' => $truck->id,
            ]
        );
    }

    /**
     * Post a Sales Voucher for an Invoice.
     */
    public function postInvoice(Invoice $invoice): ?AccountingVoucher
    {
        $company = $invoice->company;
        if (! $company) {
            return null;
        }

        $this->provisioner->provisionForCompany($company);

        $customer = $invoice->consignee;
        $customerLedger = $this->getOrCreatePartyLedger($company, $customer);

        $revenueLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'FR_INCOME')
            ->firstOrFail();

        $cgstLedger = Ledger::where('company_id', $company->id)->where('code', 'CGST_OUT')->first();
        $sgstLedger = Ledger::where('company_id', $company->id)->where('code', 'SGST_OUT')->first();
        $igstLedger = Ledger::where('company_id', $company->id)->where('code', 'IGST_OUT')->first();
        $roundOffLedger = Ledger::where('company_id', $company->id)->where('code', 'ROUND_OFF')->first();

        return DB::transaction(function () use (
            $invoice,
            $company,
            $customerLedger,
            $revenueLedger,
            $cgstLedger,
            $sgstLedger,
            $igstLedger,
            $roundOffLedger
        ): AccountingVoucher {
            // Remove any previous posted voucher for this invoice to prevent duplication
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', Invoice::class)
                ->where('source_id', $invoice->id)
                ->delete();

            $totalAmount = (float) ($invoice->net_amount ?? $invoice->total_amount);
            $baseAmount = (float) ($invoice->base_amount ?? $invoice->total_amount);
            $cgst = (float) ($invoice->cgst ?? 0.0);
            $sgst = (float) ($invoice->sgst ?? 0.0);
            $igst = (float) ($invoice->igst ?? 0.0);

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'sales',
                'voucher_number' => 'SALES-'.($invoice->invoice_number ?: $invoice->manual_invoice_number ?: $invoice->id),
                'voucher_date' => $invoice->generated_date ?: now()->toDateString(),
                'reference_number' => $invoice->manual_invoice_number ?: $invoice->invoice_number,
                'reference_date' => $invoice->generated_date ?: now()->toDateString(),
                'narration' => 'Customer freight sales invoice #'.($invoice->invoice_number ?: $invoice->manual_invoice_number),
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'total_amount' => $totalAmount,
                'is_posted' => true,
                'user_id' => $invoice->user_id,
            ]);

            // 1. Debit: Customer
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $customerLedger->id,
                'type' => 'debit',
                'amount' => $totalAmount,
                'particulars_note' => 'Sales against Invoice #'.($invoice->invoice_number ?: $invoice->id),
            ]);

            // 2. Credit: Revenue
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $revenueLedger->id,
                'type' => 'credit',
                'amount' => $baseAmount,
                'particulars_note' => 'Freight Revenue',
            ]);

            // 3. Taxes
            if ($cgst > 0 && $cgstLedger) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $cgstLedger->id,
                    'type' => 'credit',
                    'amount' => $cgst,
                ]);
            }

            if ($sgst > 0 && $sgstLedger) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $sgstLedger->id,
                    'type' => 'credit',
                    'amount' => $sgst,
                ]);
            }

            if ($igst > 0 && $igstLedger) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $igstLedger->id,
                    'type' => 'credit',
                    'amount' => $igst,
                ]);
            }

            // Round-off if necessary
            $credits = $baseAmount + $cgst + $sgst + $igst;
            $diff = round($totalAmount - $credits, 2);
            if (abs($diff) > 0.001 && $roundOffLedger) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $roundOffLedger->id,
                    'type' => $diff > 0 ? 'credit' : 'debit',
                    'amount' => abs($diff),
                ]);
            }

            return $voucher;
        });
    }

    /**
     * Post a Receipt Voucher when customer payment is recorded.
     */
    public function postInvoicePayment(InvoicePayment $payment): ?AccountingVoucher
    {
        $invoice = $payment->invoice;
        $company = $invoice?->company;
        if (! $company) {
            return null;
        }

        $this->provisioner->provisionForCompany($company);

        $customer = $invoice->consignee;
        $customerLedger = $this->getOrCreatePartyLedger($company, $customer);

        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();

        return DB::transaction(function () use ($payment, $company, $invoice, $customerLedger, $bankLedger): AccountingVoucher {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', InvoicePayment::class)
                ->where('source_id', $payment->id)
                ->delete();

            $amount = (float) $payment->amount;

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'receipt',
                'voucher_number' => 'RCPT-'.$payment->id,
                'voucher_date' => $payment->payment_date ?: now()->toDateString(),
                'reference_number' => $payment->reference_number ?: 'INV-'.$invoice->id,
                'reference_date' => $payment->payment_date ?: now()->toDateString(),
                'narration' => 'Payment received for Invoice #'.($invoice->invoice_number ?: $invoice->manual_invoice_number),
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
                'total_amount' => $amount,
                'is_posted' => true,
            ]);

            // Dr: Bank / Cash
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $bankLedger->id,
                'type' => 'debit',
                'amount' => $amount,
                'particulars_note' => 'Payment received',
            ]);

            // Cr: Customer
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $customerLedger->id,
                'type' => 'credit',
                'amount' => $amount,
                'particulars_note' => 'Against Invoice #'.($invoice->invoice_number ?: $invoice->id),
            ]);

            return $voucher;
        });
    }

    /**
     * Post a Purchase / Freight Hiring Voucher for a confirmed Freight Booking.
     */
    public function postFreightBooking(FreightBooking $booking): ?AccountingVoucher
    {
        $company = $booking->company;
        if (! $company) {
            return null;
        }

        // If booking was cancelled, remove any previous voucher
        if ($booking->is_cancelled) {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', FreightBooking::class)
                ->where('source_id', $booking->id)
                ->delete();

            return null;
        }

        $transporter = $booking->transporter;
        if (! $transporter) {
            return null;
        }

        $this->provisioner->provisionForCompany($company);

        $transporterLedger = $this->getOrCreatePartyLedger($company, $transporter);

        $hiringExpenseLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'FR_EXPENSE')
            ->firstOrFail();

        $tdsLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'TDS_194C')
            ->first();

        $recoveryLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'SHORTAGE_REC')
            ->first();

        $costCentre = $this->getOrCreateTruckCostCentre($company, $booking->truck);

        return DB::transaction(function () use (
            $booking,
            $company,
            $transporterLedger,
            $hiringExpenseLedger,
            $tdsLedger,
            $recoveryLedger,
            $costCentre
        ): AccountingVoucher {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', FreightBooking::class)
                ->where('source_id', $booking->id)
                ->delete();

            $grossFreight = (float) $booking->tr_amount;
            $detention = (float) ($booking->detention ?? 0.0);
            $deviation = (float) ($booking->deviation ?? 0.0);
            $miscExpense = (float) ($booking->misc_expense ?? 0.0);
            $damageAmount = (float) ($booking->damage_amount ?? 0.0);
            $shortageAmount = (float) ($booking->shortage_amount ?? 0.0);
            $tdsAmount = (float) ($booking->tds_amount ?? 0.0);

            $customAmounts = $booking->relationLoaded('customAmounts')
                ? $booking->customAmounts
                : $booking->customAmounts()->get();
            $positiveCustom = (float) $customAmounts->where('amount', '>', 0)->sum('amount');
            $negativeCustom = abs((float) $customAmounts->where('amount', '<', 0)->sum('amount'));

            $totalHiringExpense = round($grossFreight + $detention + $deviation + $positiveCustom, 2);
            $recoveryAmount = round($damageAmount + $shortageAmount + $miscExpense + $negativeCustom, 2);
            $tdsAmount = round($tdsAmount, 2);
            $netCredited = round($totalHiringExpense - $recoveryAmount - $tdsAmount, 2);

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'purchase',
                'voucher_number' => 'PUR-FB-'.$booking->id,
                'voucher_date' => $booking->created_at?->toDateString() ?: now()->toDateString(),
                'reference_number' => $booking->voucher_number ?: 'LR-'.$booking->lr_id,
                'narration' => 'Freight hiring for Truck #'.($booking->truck?->truck_number ?? 'N/A').' LR #'.($booking->lr?->lr_number ?? 'N/A'),
                'source_type' => FreightBooking::class,
                'source_id' => $booking->id,
                'total_amount' => $totalHiringExpense,
                'is_posted' => true,
            ]);

            // Dr: Freight Expense (tagged with Truck Cost Centre)
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $hiringExpenseLedger->id,
                'type' => 'debit',
                'amount' => $totalHiringExpense,
                'cost_centre_id' => $costCentre?->id,
                'particulars_note' => 'Freight hiring trip charges',
            ]);

            // Cr: Transporter (Net Amount)
            if (abs($netCredited) > 0.001) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $transporterLedger->id,
                    'type' => $netCredited >= 0 ? 'credit' : 'debit',
                    'amount' => abs($netCredited),
                    'particulars_note' => 'Net freight payable after deductions & TDS',
                ]);
            }

            // Cr: TDS 194C Payable
            if ($tdsAmount > 0 && $tdsLedger) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $tdsLedger->id,
                    'type' => 'credit',
                    'amount' => $tdsAmount,
                    'particulars_note' => 'TDS u/s 194C @ '.($booking->transporter?->tds_percentage ?? 1).'%',
                ]);
            }

            // Cr: Shortage & Damage Recovery
            if ($recoveryAmount > 0 && $recoveryLedger) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $recoveryLedger->id,
                    'type' => 'credit',
                    'amount' => $recoveryAmount,
                    'particulars_note' => 'Trip shortage, damage & deduction recovery',
                ]);
            }

            return $voucher;
        });
    }

    /**
     * Post a Payment Voucher for a trip VoucherPayment (Bank/Cash/Pump/Advance).
     */
    public function postVoucherPayment(VoucherPayment $voucherPayment): ?AccountingVoucher
    {
        $company = $voucherPayment->company;
        if (! $company) {
            return null;
        }

        $booking = $voucherPayment->freight_booking;
        $transporter = $booking?->transporter;
        if (! $transporter) {
            return null;
        }

        $paymentMode = strtolower(trim((string) $voucherPayment->payment_mode));

        // When payment is settled by adjusting against prior advance, do not disburse bank/cash funds.
        if (str_contains($paymentMode, 'advance')) {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', VoucherPayment::class)
                ->where('source_id', $voucherPayment->id)
                ->delete();

            return null;
        }

        $this->provisioner->provisionForCompany($company);

        $transporterLedger = $this->getOrCreatePartyLedger($company, $transporter);

        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();

        $cashLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'CASH')
            ->firstOrFail();

        $costCentre = $this->getOrCreateTruckCostCentre($company, $booking?->truck);

        return DB::transaction(function () use (
            $voucherPayment,
            $company,
            $booking,
            $transporterLedger,
            $bankLedger,
            $cashLedger,
            $costCentre,
            $paymentMode
        ): AccountingVoucher {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', VoucherPayment::class)
                ->where('source_id', $voucherPayment->id)
                ->delete();

            $amount = (float) $voucherPayment->amount;

            // Determine credit ledger based on payment mode
            $creditLedger = match ($paymentMode) {
                'cash' => $cashLedger,
                'pump' => $voucherPayment->pump ? $this->getOrCreatePartyLedger($company, $voucherPayment->pump) : $cashLedger,
                default => $bankLedger,
            };

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'payment',
                'voucher_number' => 'PAY-'.($voucherPayment->voucher_number ?: $voucherPayment->id),
                'voucher_date' => $voucherPayment->date ?: now()->toDateString(),
                'reference_number' => $voucherPayment->reference_transaction_id ?: $voucherPayment->voucher_number,
                'narration' => 'Payment to '.($booking?->transporter?->name ?? 'Transporter').' (Mode: '.$paymentMode.')',
                'source_type' => VoucherPayment::class,
                'source_id' => $voucherPayment->id,
                'total_amount' => $amount,
                'is_posted' => true,
                'user_id' => $voucherPayment->user_id,
            ]);

            // Dr: Transporter (reducing payable)
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $transporterLedger->id,
                'type' => 'debit',
                'amount' => $amount,
                'cost_centre_id' => $costCentre?->id,
                'particulars_note' => 'Voucher #'.($voucherPayment->voucher_number ?: $voucherPayment->id),
            ]);

            // Cr: Bank, Cash, or Pump
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $creditLedger->id,
                'type' => 'credit',
                'amount' => $amount,
                'particulars_note' => 'Disbursed via '.$creditLedger->name,
            ]);

            return $voucher;
        });
    }

    /**
     * Post a Payment Voucher for an Advance Payment to a Transporter.
     */
    public function postAdvancePayment(AdvancePayment $advancePayment): ?AccountingVoucher
    {
        $amount = (float) $advancePayment->advance_amount;
        $remark = strtolower(trim((string) $advancePayment->remark));

        // Skip internal negative adjustments or zero amounts
        if ($amount <= 0 || str_contains($remark, 'negative balance adjustment')) {
            AccountingVoucher::where('company_id', $advancePayment->company_id)
                ->where('source_type', AdvancePayment::class)
                ->where('source_id', $advancePayment->id)
                ->delete();

            return null;
        }

        $company = $advancePayment->company;
        if (! $company) {
            return null;
        }

        $transporter = $advancePayment->transporter;
        if (! $transporter) {
            return null;
        }

        $this->provisioner->provisionForCompany($company);

        $transporterLedger = $this->getOrCreatePartyLedger($company, $transporter);

        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();

        $cashLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'CASH')
            ->firstOrFail();

        $creditLedger = str_contains($remark, 'cash') ? $cashLedger : $bankLedger;

        return DB::transaction(function () use (
            $advancePayment,
            $company,
            $transporter,
            $transporterLedger,
            $creditLedger,
            $amount
        ): AccountingVoucher {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', AdvancePayment::class)
                ->where('source_id', $advancePayment->id)
                ->delete();

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'payment',
                'voucher_number' => 'PAY-ADV-'.$advancePayment->id,
                'voucher_date' => $advancePayment->payment_date ?: now()->toDateString(),
                'reference_number' => 'ADV-'.$advancePayment->id,
                'reference_date' => $advancePayment->payment_date ?: now()->toDateString(),
                'narration' => 'Advance payment to '.$transporter->name.($advancePayment->remark ? ' ('.$advancePayment->remark.')' : ''),
                'source_type' => AdvancePayment::class,
                'source_id' => $advancePayment->id,
                'total_amount' => $amount,
                'is_posted' => true,
            ]);

            // Dr: Transporter (Advance disbursement)
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $transporterLedger->id,
                'type' => 'debit',
                'amount' => $amount,
                'particulars_note' => 'Advance to Transporter',
            ]);

            // Cr: Bank or Cash
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $creditLedger->id,
                'type' => 'credit',
                'amount' => $amount,
                'particulars_note' => 'Disbursed via '.$creditLedger->name,
            ]);

            return $voucher;
        });
    }

    /**
     * Delete any accounting voucher associated with a given source model.
     */
    public function deleteVoucherFor(string $sourceType, int|string $sourceId, ?int $companyId = null): void
    {
        $query = AccountingVoucher::where('source_type', $sourceType)
            ->where('source_id', $sourceId);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $query->delete();
    }
}
