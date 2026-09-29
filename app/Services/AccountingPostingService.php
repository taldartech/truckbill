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
            $groupName = $entity->is_owner ? 'Truck Owners' : 'Transporters';
            $group = AccountGroup::where('company_id', $company->id)
                ->where('name', $groupName)
                ->first()
                ?? AccountGroup::where('company_id', $company->id)
                    ->where('name', 'Sundry Creditors')
                    ->first();

            $expectedName = $entity->name.($entity->is_owner ? ' (Owner)' : ' (Transporter)');

            $ledger = Ledger::where('company_id', $company->id)
                ->where('entity_type', Transporter::class)
                ->where('entity_id', $entity->id)
                ->first();

            $tdsRate = ($entity->declaration_given ?? false) ? 0.00 : $entity->tds_percentage;

            if ($ledger) {
                if ($group && $ledger->account_group_id !== $group->id) {
                    $ledger->account_group_id = $group->id;
                }
                if ($ledger->name !== $expectedName && ! empty($entity->name)) {
                    $ledger->name = $expectedName;
                }
                $ledger->tds_rate = $tdsRate;
                $ledger->save();

                return $ledger;
            }

            return Ledger::create([
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => $expectedName,
                'entity_type' => Transporter::class,
                'entity_id' => $entity->id,
                'gstin' => $entity->gst_number,
                'pan_number' => $entity->pan_number,
                'tds_section' => '194C',
                'tds_rate' => $tdsRate,
                'opening_balance' => 0.00,
                'opening_balance_type' => 'credit',
            ]);
        }

        if ($entity instanceof Pump) {
            $group = AccountGroup::where('company_id', $company->id)
                ->where('name', 'Fuel Pumps')
                ->first()
                ?? AccountGroup::where('company_id', $company->id)
                    ->where('name', 'Sundry Creditors')
                    ->first();

            $pumpName = trim((string) ($entity->pump_name ?: $entity->name ?: $entity->contact_name));
            if ($pumpName === '') {
                $pumpName = 'Pump #'.$entity->id;
            }
            $expectedName = $pumpName.' (Fuel Pump)';

            $ledger = Ledger::where('company_id', $company->id)
                ->where('entity_type', Pump::class)
                ->where('entity_id', $entity->id)
                ->first();

            if ($ledger) {
                if ($group && $ledger->account_group_id !== $group->id) {
                    $ledger->account_group_id = $group->id;
                }
                if ($ledger->name !== $expectedName) {
                    $ledger->name = $expectedName;
                }
                $ledger->save();

                return $ledger;
            }

            return Ledger::create([
                'company_id' => $company->id,
                'account_group_id' => $group->id,
                'name' => $expectedName,
                'entity_type' => Pump::class,
                'entity_id' => $entity->id,
                'opening_balance' => 0.00,
                'opening_balance_type' => 'credit',
            ]);
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

        $paymentMode = strtolower(trim((string) $payment->mode));
        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();
        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? $cashLedger;

        // Use selected bank/wallet ledger if available, otherwise default by mode
        if (! empty($payment->bank_ledger_id)) {
            $remittanceLedger = Ledger::where('company_id', $company->id)->find($payment->bank_ledger_id)
                ?? ($paymentMode === 'cash' ? $cashLedger : $bankLedger);
        } else {
            $remittanceLedger = $paymentMode === 'cash' ? $cashLedger : $bankLedger;
        }

        $tdsLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'TDS_194C_REC')
            ->first();

        $deductionsLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'CUSTOMER_DEDUCTIONS')
            ->first();

        return DB::transaction(function () use (
            $payment,
            $company,
            $invoice,
            $customerLedger,
            $remittanceLedger,
            $tdsLedger,
            $deductionsLedger
        ): AccountingVoucher {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', InvoicePayment::class)
                ->where('source_id', $payment->id)
                ->delete();

            $receivedAmount = (float) $payment->amount;
            $tdsAmount = (float) ($payment->tds_amount ?? 0.0);
            $deductionAmount = (float) ($payment->deduction_amount ?? 0.0);
            $totalSettled = round($receivedAmount + $tdsAmount + $deductionAmount, 2);

            $invRef = $invoice->invoice_number ?: ($invoice->manual_invoice_number ?: 'INV-'.$invoice->id);
            $narrationParts = ['Payment received for Invoice #'.$invRef];
            if ($tdsAmount > 0) {
                $narrationParts[] = 'TDS 194C: ₹'.number_format($tdsAmount, 2);
            }
            if ($deductionAmount > 0) {
                $narrationParts[] = 'Deductions: ₹'.number_format($deductionAmount, 2).($payment->deduction_reason ? ' ('.$payment->deduction_reason.')' : '');
            }
            if ($payment->remark) {
                $narrationParts[] = $payment->remark;
            }

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'receipt',
                'voucher_number' => 'RCPT-'.$payment->id,
                'voucher_date' => $payment->payment_date ?: now()->toDateString(),
                'reference_number' => $invRef,
                'reference_date' => $payment->payment_date ?: now()->toDateString(),
                'narration' => implode(' | ', $narrationParts),
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
                'total_amount' => $totalSettled,
                'is_posted' => true,
            ]);

            // 1. Dr: Bank or Cash (Actual Remittance)
            if ($receivedAmount > 0) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $remittanceLedger->id,
                    'type' => 'debit',
                    'amount' => $receivedAmount,
                    'particulars_note' => 'Remittance against Invoice #'.$invRef.' via '.$remittanceLedger->name,
                ]);
            }

            // 2. Dr: TDS Receivable u/s 194C (TDS Deducted by Customer)
            if ($tdsAmount > 0 && $tdsLedger) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $tdsLedger->id,
                    'type' => 'debit',
                    'amount' => $tdsAmount,
                    'particulars_note' => 'TDS deducted by customer u/s 194C on Invoice #'.$invRef,
                ]);
            }

            // 3. Dr: Customer Deductions & Shortage (Claims/Penalties)
            if ($deductionAmount > 0 && $deductionsLedger) {
                $dedReason = $payment->deduction_reason ? ': '.$payment->deduction_reason : '';
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $deductionsLedger->id,
                    'type' => 'debit',
                    'amount' => $deductionAmount,
                    'particulars_note' => 'Customer transit loss/deduction on Invoice #'.$invRef.$dedReason,
                ]);
            }

            // 4. Cr: Customer (Total Cleared)
            if ($totalSettled > 0) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $customerLedger->id,
                    'type' => 'credit',
                    'amount' => $totalSettled,
                    'particulars_note' => 'Settled against Invoice #'.$invRef,
                ]);
            }

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

        $transporter = $booking->transporter ?? $booking->truck?->transporter ?? $booking->lr?->transporter;
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
        $truckNo = $booking->truck?->truck_number ?? 'N/A';
        $lrNo = $booking->lr?->lr_number ?? ($booking->lr_id ? 'LR-'.$booking->lr_id : 'N/A');

        return DB::transaction(function () use (
            $booking,
            $company,
            $transporter,
            $transporterLedger,
            $hiringExpenseLedger,
            $tdsLedger,
            $recoveryLedger,
            $costCentre,
            $truckNo,
            $lrNo
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
                'reference_number' => 'Truck: '.$truckNo.' | LR: '.$lrNo,
                'narration' => 'Freight hiring for Truck #'.$truckNo.' LR #'.$lrNo.($booking->voucher_number ? ' (Voucher: '.$booking->voucher_number.')' : ''),
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
                'particulars_note' => 'Truck #'.$truckNo.' | LR #'.$lrNo.' - Freight hiring charges',
            ]);

            // Cr: Transporter (Net Amount)
            if (abs($netCredited) > 0.001) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $transporterLedger->id,
                    'type' => $netCredited >= 0 ? 'credit' : 'debit',
                    'amount' => abs($netCredited),
                    'cost_centre_id' => $costCentre?->id,
                    'particulars_note' => 'Truck #'.$truckNo.' | LR #'.$lrNo.' - Net freight payable after deductions & TDS',
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
                    'particulars_note' => 'TDS u/s 194C @ '.($transporter->tds_percentage ?? 1).'%',
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
        $transporter = $booking?->transporter ?? $booking?->truck?->transporter ?? $booking?->lr?->transporter;
        if (! $transporter) {
            return null;
        }

        $paymentMode = strtolower(trim((string) $voucherPayment->payment_mode));
        $truckNo = $booking?->truck?->truck_number ?? 'N/A';
        $lrNo = $booking?->lr?->lr_number ?? ($booking?->lr_id ? 'LR-'.$booking->lr_id : 'N/A');

        $this->provisioner->provisionForCompany($company);

        $transporterLedger = $this->getOrCreatePartyLedger($company, $transporter);

        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();

        $cashLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'CASH')
            ->firstOrFail();

        $costCentre = $this->getOrCreateTruckCostCentre($company, $booking?->truck);

        // Check payment mode type
        $isFuelPump = str_contains($paymentMode, 'fuel') || str_contains($paymentMode, 'pump') || ! empty($voucherPayment->pump_id);
        $isAdvanceAdjust = str_contains($paymentMode, 'advance');

        if ($isAdvanceAdjust) {
            // Post journal adjustment against prior advance so trip settlement is visible in ledger
            return DB::transaction(function () use (
                $voucherPayment,
                $company,
                $transporterLedger,
                $costCentre,
                $truckNo,
                $lrNo,
                $paymentMode
            ): AccountingVoucher {
                AccountingVoucher::where('company_id', $company->id)
                    ->where('source_type', VoucherPayment::class)
                    ->where('source_id', $voucherPayment->id)
                    ->delete();

                $amount = (float) $voucherPayment->amount;

                $voucher = AccountingVoucher::create([
                    'company_id' => $company->id,
                    'voucher_type' => 'journal',
                    'voucher_number' => 'JRN-'.($voucherPayment->voucher_number ?: $voucherPayment->id),
                    'voucher_date' => $voucherPayment->date ?: now()->toDateString(),
                    'reference_number' => 'Truck: '.$truckNo.' | LR: '.$lrNo,
                    'narration' => 'Advance adjustment for Truck #'.$truckNo.' LR #'.$lrNo.' (Voucher #'.($voucherPayment->voucher_number ?: $voucherPayment->id).')',
                    'source_type' => VoucherPayment::class,
                    'source_id' => $voucherPayment->id,
                    'total_amount' => $amount,
                    'is_posted' => true,
                    'user_id' => $voucherPayment->user_id,
                ]);

                $advanceAssetLedger = Ledger::where('company_id', $company->id)
                    ->where('code', 'ADV_TRANSPORTER')
                    ->first()
                    ?? $this->provisioner->ensureLedger($company, 'Advance to Transporters & Truck Owners A/c', 'Loans & Advances', 'ADV_TRANSPORTER', 'debit');

                // Dr: Transporter Payable (reducing hiring payable for trip)
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $transporterLedger->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'cost_centre_id' => $costCentre?->id,
                    'particulars_note' => 'Truck #'.$truckNo.' | LR #'.$lrNo.' - Advance adjusted against trip hiring',
                ]);

                // Cr: Advance to Transporters & Truck Owners A/c (clearing advance asset)
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $advanceAssetLedger->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'cost_centre_id' => $costCentre?->id,
                    'particulars_note' => 'Truck #'.$truckNo.' | LR #'.$lrNo.' - Advance settled against Voucher #'.($voucherPayment->voucher_number ?: $voucherPayment->id),
                ]);

                return $voucher;
            });
        }

        // Determine credit ledger based on payment mode / selected bank_ledger_id
        if ($isFuelPump) {
            $pump = $voucherPayment->pump ?? ($voucherPayment->pump_id ? Pump::find($voucherPayment->pump_id) : null);
            if (! $pump && ! empty($voucherPayment->remark) && $company->user_id) {
                $pumps = Pump::where('user_id', $company->user_id)->get();
                foreach ($pumps as $p) {
                    if (stripos($voucherPayment->remark, (string) $p->pump_name) !== false) {
                        $pump = $p;
                        break;
                    }
                }
            }
            if ($pump) {
                $creditLedger = $this->getOrCreatePartyLedger($company, $pump);
            } else {
                $creditLedger = Ledger::where('company_id', $company->id)
                    ->where('name', 'Fuel Pump Payable A/c')
                    ->first()
                    ?? $this->provisioner->ensureLedger($company, 'Fuel Pump Payable A/c', 'Fuel Pumps', 'FUEL_PUMP_PAYABLE', 'credit');
            }
        } elseif (! empty($voucherPayment->bank_ledger_id)) {
            $creditLedger = Ledger::where('company_id', $company->id)->find($voucherPayment->bank_ledger_id)
                ?? ($paymentMode === 'cash' ? $cashLedger : $bankLedger);
        } elseif ($paymentMode === 'cash') {
            $creditLedger = $cashLedger;
        } else {
            $creditLedger = $bankLedger;
        }

        return DB::transaction(function () use (
            $voucherPayment,
            $company,
            $transporter,
            $transporterLedger,
            $creditLedger,
            $costCentre,
            $paymentMode,
            $truckNo,
            $lrNo
        ): AccountingVoucher {
            AccountingVoucher::where('company_id', $company->id)
                ->where('source_type', VoucherPayment::class)
                ->where('source_id', $voucherPayment->id)
                ->delete();

            $amount = (float) $voucherPayment->amount;

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'payment',
                'voucher_number' => 'PAY-'.($voucherPayment->voucher_number ?: $voucherPayment->id),
                'voucher_date' => $voucherPayment->date ?: now()->toDateString(),
                'reference_number' => 'Truck: '.$truckNo.' | LR: '.$lrNo,
                'narration' => 'Payment for Truck #'.$truckNo.' LR #'.$lrNo.' to '.($transporter->name ?? 'Transporter').' (Mode: '.$paymentMode.')',
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
                'particulars_note' => 'Truck #'.$truckNo.' | LR #'.$lrNo.' - Voucher #'.($voucherPayment->voucher_number ?: $voucherPayment->id).' ('.$paymentMode.')',
            ]);

            // Cr: Bank, Cash, or Fuel Pump
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $creditLedger->id,
                'type' => 'credit',
                'amount' => $amount,
                'particulars_note' => 'Truck #'.$truckNo.' | LR #'.$lrNo.' - Disbursed via '.$creditLedger->name,
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

        $company = $advancePayment->company ?? ($advancePayment->company_id ? Company::find($advancePayment->company_id) : null);
        if (! $company) {
            return null;
        }

        $transporter = $advancePayment->transporter ?? ($advancePayment->transporter_id ? Transporter::find($advancePayment->transporter_id) : null);
        if (! $transporter) {
            return null;
        }

        $this->provisioner->provisionForCompany($company);

        $advanceAssetLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'ADV_TRANSPORTER')
            ->first()
            ?? $this->provisioner->ensureLedger($company, 'Advance to Transporters & Truck Owners A/c', 'Loans & Advances', 'ADV_TRANSPORTER', 'debit');

        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();

        $cashLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'CASH')
            ->firstOrFail();

        if (! empty($advancePayment->bank_ledger_id)) {
            $creditLedger = Ledger::where('company_id', $company->id)->find($advancePayment->bank_ledger_id)
                ?? (str_contains($remark, 'cash') ? $cashLedger : $bankLedger);
        } else {
            $creditLedger = str_contains($remark, 'cash') ? $cashLedger : $bankLedger;
        }

        return DB::transaction(function () use (
            $advancePayment,
            $company,
            $transporter,
            $advanceAssetLedger,
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

            // Dr: Advance to Transporters & Truck Owners (Asset)
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $advanceAssetLedger->id,
                'type' => 'debit',
                'amount' => $amount,
                'particulars_note' => 'Advance to '.($transporter->name ?? 'Transporter'),
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
     * Post a Credit Note (GST Section 34) for an Invoice adjustment / rate revision.
     */
    public function postCreditNote(Invoice $invoice, float $amount, ?string $reason = null, float $taxAmount = 0.0): AccountingVoucher
    {
        $company = $invoice->company ?? Company::findOrFail($invoice->company_id);
        $this->provisioner->provisionForCompany($company);

        $customer = $invoice->consignee;
        $customerLedger = $this->getOrCreatePartyLedger($company, $customer);

        $revenueLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'FR_INCOME')
            ->firstOrFail();

        $totalCreditNoteAmount = round($amount + $taxAmount, 2);
        $invRef = $invoice->invoice_number ?: ($invoice->manual_invoice_number ?: 'INV-'.$invoice->id);

        return DB::transaction(function () use (
            $invoice,
            $company,
            $customerLedger,
            $revenueLedger,
            $amount,
            $taxAmount,
            $totalCreditNoteAmount,
            $reason,
            $invRef
        ): AccountingVoucher {
            $voucherNumber = 'CRN-'.now()->format('ymd').'-'.rand(1000, 9999);

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'credit_note',
                'voucher_number' => $voucherNumber,
                'voucher_date' => now()->toDateString(),
                'reference_number' => $invRef,
                'reference_date' => $invoice->generated_date ?: now()->toDateString(),
                'narration' => 'Credit Note against Invoice #'.$invRef.($reason ? ' ('.$reason.')' : ''),
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'total_amount' => $totalCreditNoteAmount,
                'is_posted' => true,
            ]);

            // 1. Dr: Freight Revenue
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $revenueLedger->id,
                'type' => 'debit',
                'amount' => $amount,
                'particulars_note' => 'Freight rate revision / discount on Invoice #'.$invRef,
            ]);

            // 2. Dr: Output GST if tax adjustment
            if ($taxAmount > 0) {
                $taxLedger = Ledger::where('company_id', $company->id)->where('code', 'IGST_OUT')->first()
                    ?? Ledger::where('company_id', $company->id)->where('code', 'CGST_OUT')->first();
                if ($taxLedger) {
                    AccountingVoucherItem::create([
                        'company_id' => $company->id,
                        'accounting_voucher_id' => $voucher->id,
                        'ledger_id' => $taxLedger->id,
                        'type' => 'debit',
                        'amount' => $taxAmount,
                        'particulars_note' => 'GST output adjustment u/s 34 on Invoice #'.$invRef,
                    ]);
                }
            }

            // 3. Cr: Customer (reducing receivable)
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $customerLedger->id,
                'type' => 'credit',
                'amount' => $totalCreditNoteAmount,
                'particulars_note' => 'Credit Note issued on Invoice #'.$invRef,
            ]);

            return $voucher;
        });
    }

    /**
     * Post a Debit Note to an attached Transporter / Truck Owner for penalties or damage deductions.
     */
    public function postDebitNote(Transporter $transporter, float $amount, string $reason, ?FreightBooking $booking = null): AccountingVoucher
    {
        $company = $transporter->company ?? Company::findOrFail($transporter->company_id);
        $this->provisioner->provisionForCompany($company);

        $transporterLedger = $this->getOrCreatePartyLedger($company, $transporter);

        $recoveryLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'SHORTAGE_REC')
            ->first()
            ?? $this->provisioner->ensureLedger($company, 'Shortage & Damage Recovery A/c', 'Shortage & Damage Recovery', 'SHORTAGE_REC', 'credit');

        $truckNo = $booking?->truck?->truck_number ?? 'General';
        $lrNo = $booking?->lr?->lr_number ?? ($booking?->lr_id ? 'LR-'.$booking->lr_id : '-');

        return DB::transaction(function () use (
            $company,
            $transporter,
            $transporterLedger,
            $recoveryLedger,
            $amount,
            $reason,
            $truckNo,
            $lrNo,
            $booking
        ): AccountingVoucher {
            $voucherNumber = 'DBN-'.now()->format('ymd').'-'.rand(1000, 9999);

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => 'debit_note',
                'voucher_number' => $voucherNumber,
                'voucher_date' => now()->toDateString(),
                'reference_number' => 'Truck: '.$truckNo.' | LR: '.$lrNo,
                'narration' => 'Debit Note to '.($transporter->name ?? 'Transporter').': '.$reason,
                'source_type' => $booking ? FreightBooking::class : Transporter::class,
                'source_id' => $booking?->id ?? $transporter->id,
                'total_amount' => $amount,
                'is_posted' => true,
            ]);

            // 1. Dr: Transporter (reducing payable)
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $transporterLedger->id,
                'type' => 'debit',
                'amount' => $amount,
                'particulars_note' => 'Debit Note: '.$reason,
            ]);

            // 2. Cr: Shortage & Damage Recovery
            AccountingVoucherItem::create([
                'company_id' => $company->id,
                'accounting_voucher_id' => $voucher->id,
                'ledger_id' => $recoveryLedger->id,
                'type' => 'credit',
                'amount' => $amount,
                'particulars_note' => 'Recovery from '.($transporter->name ?? 'Transporter'),
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
