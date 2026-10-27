<?php

namespace App\Console\Commands;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\VoucherPayment;
use App\Services\AccountingPostingService;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Console\Command;
use Throwable;

class BackfillAccountingLedgersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:backfill {--company= : Specific company ID to backfill}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill double-entry accounting ledgers and vouchers for historical operations';

    public function __construct(
        private readonly ChartOfAccountsProvisioner $provisioner,
        private readonly AccountingPostingService $postingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $companiesQuery = Company::query();
        if ($companyId) {
            $companiesQuery->where('id', $companyId);
        }

        $companies = $companiesQuery->get();
        if ($companies->isEmpty()) {
            $this->warn('No companies found to backfill.');

            return self::SUCCESS;
        }

        $this->info("Starting accounting backfill across {$companies->count()} company/companies...");

        foreach ($companies as $company) {
            $this->line('---------------------------------------------------------');
            $this->info("Processing Company: {$company->name} (ID: {$company->id})");

            $this->provisioner->provisionForCompany($company);
            $this->info('  ✓ Chart of accounts & default ledgers provisioned.');

            // 1. Backfill Invoices & Payments
            $invoices = Invoice::with(['company', 'consignee', 'payments'])
                ->where('company_id', $company->id)
                ->get();

            $invCount = 0;
            $payCount = 0;
            foreach ($invoices as $invoice) {
                try {
                    $this->postingService->postInvoice($invoice);
                    $invCount++;

                    foreach ($invoice->payments as $payment) {
                        $this->postingService->postInvoicePayment($payment);
                        $payCount++;
                    }
                } catch (Throwable $e) {
                    $this->error("  Error posting Invoice #{$invoice->id}: {$e->getMessage()}");
                }
            }
            $this->info("  ✓ Backfilled {$invCount} sales invoices and {$payCount} invoice receipts.");

            // 2. Backfill Freight Bookings
            $bookings = FreightBooking::with(['company', 'transporter', 'truck', 'lr', 'customAmounts'])
                ->where('company_id', $company->id)
                ->get();

            $fbCount = 0;
            foreach ($bookings as $booking) {
                try {
                    if ($booking->transporter) {
                        $voucher = $this->postingService->postFreightBooking($booking);
                        if ($voucher) {
                            $fbCount++;
                        }
                    }
                } catch (Throwable $e) {
                    $this->error("  Error posting Freight Booking #{$booking->id}: {$e->getMessage()}");
                }
            }
            $this->info("  ✓ Backfilled {$fbCount} freight booking purchase vouchers.");

            // 3. Backfill Voucher Payments
            $voucherPayments = VoucherPayment::with(['company', 'freight_booking.transporter', 'freight_booking.truck', 'pump'])
                ->where('company_id', $company->id)
                ->get();

            $vpCount = 0;
            foreach ($voucherPayments as $vp) {
                try {
                    if ($vp->freight_booking?->transporter) {
                        $voucher = $this->postingService->postVoucherPayment($vp);
                        if ($voucher) {
                            $vpCount++;
                        }
                    }
                } catch (Throwable $e) {
                    $this->error("  Error posting Voucher Payment #{$vp->id}: {$e->getMessage()}");
                }
            }
            $this->info("  ✓ Backfilled {$vpCount} payment vouchers.");

            // 4. Backfill Transporter Advance Payments
            $advances = AdvancePayment::with(['company', 'transporter'])
                ->where('company_id', $company->id)
                ->where('advance_amount', '>', 0)
                ->get();

            $advCount = 0;
            foreach ($advances as $advance) {
                try {
                    $voucher = $this->postingService->postAdvancePayment($advance);
                    if ($voucher) {
                        $advCount++;
                    }
                } catch (Throwable $e) {
                    $this->error("  Error posting Advance Payment #{$advance->id}: {$e->getMessage()}");
                }
            }
            $this->info("  ✓ Backfilled {$advCount} transporter advance payment vouchers.");
        }

        $this->info('---------------------------------------------------------');
        $this->info('Accounting backfill completed successfully!');

        return self::SUCCESS;
    }
}
