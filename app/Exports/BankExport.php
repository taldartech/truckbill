<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use App\Models\VoucherPayment;
use App\Models\VoucherLinked;
use Illuminate\Support\Facades\Auth;

class BankExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $companyId = Auth::user()->company_id;
        
        // Query for regular payments
        $paymentQuery = VoucherPayment::with([
                'freight_booking.lr',
                'freight_booking.truck',
                'freight_booking.transporter'
            ])
            ->where('payment_mode', 'bank transfer')
            ->whereNull('linked_voucher_id')
            ->whereHas('freight_booking', function($q) use ($companyId) {
                if ($companyId) {
                    $q->where('company_id', $companyId);
                }
            });

        // Query for linked vouchers
        $linkedQuery = VoucherLinked::with([
                'freight_booking.lr',
                'freight_booking.truck',
                'freight_booking.transporter'
            ])
            ->where('payment_mode', 'bank transfer')
            ->whereHas('freight_booking', function($q) use ($companyId) {
                if ($companyId) {
                    $q->where('company_id', $companyId);
                }
            });

        // Apply date filters
        if ($this->startDate) {
            $paymentQuery->where('date', '>=', $this->startDate);
            $linkedQuery->where('date', '>=', $this->startDate);
        }
        
        if ($this->endDate) {
            $paymentQuery->where('date', '<=', $this->endDate);
            $linkedQuery->where('date', '<=', $this->endDate);
        }

        // Process regular payments
        $payments = $paymentQuery->get()
            ->map(function($payment) {
                return [
                    'date' => $payment->date,
                    'lr_number' => $payment->freight_booking->lr->lr_number ?? 'N/A',
                    'truck_number' => $payment->freight_booking->lr->truck->truck_number ?? 'N/A',
                    'party' => $payment->freight_booking->transporter->name ?? 'N/A',
                    'name' => $payment->freight_booking->transporter->short_name ?? 'N/A',
                    'beneficiary_id' => $payment->freight_booking->transporter->beneficiary_id ?? 'N/A',
                    'amount' => $payment->amount,
                ];
            });

        // Process linked payments
        $linkedPayments = $linkedQuery->get()
            ->map(function($linked) {
                return [
                    'date' => $linked->date,
                    'lr_number' => $linked->freight_booking->lr->lr_number ?? 'N/A',
                    'truck_number' => $linked->freight_booking->lr->truck->truck_number ?? 'N/A',
                    'party' => $linked->freight_booking->transporter->name ?? 'N/A',
                    'name' => $linked->freight_booking->transporter->short_name ?? 'N/A',
                    'beneficiary_id' => $linked->freight_booking->transporter->beneficiary_id ?? 'N/A',
                    'amount' => $linked->amount,
                ];
            });

        // Merge and sort by date
        return $payments->merge($linkedPayments)->sortBy('date');
    }

    public function headings(): array
    {
        return [
            'Date',
            'LR Number',
            'Truck Number',
            'Party',
            'Name',
            'Beneficiary ID',
            'Amount (₹)'
        ];
    }

    public function title(): string
    {
        return 'Bank Payments';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            // Set alignment for all cells
            'A:G' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                ],
            ],
            // Format amount column as number with 2 decimal places
            'G' => [
                'numberFormat' => [
                    'formatCode' => '#,##0.00',
                ],
            ],
        ];
    }
}