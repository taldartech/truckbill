<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\VoucherPayment;
use App\Models\VoucherLinked;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BankExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    protected $startDate;
    protected $endDate;
    protected $paymentStatus;

    public function __construct($startDate = null, $endDate = null, $paymentStatus = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->paymentStatus = $paymentStatus;
    }

    public function collection()
    {
        $companyId = Auth::user()->company_id;

        // Step 1: Fetch records from voucher_payments where linked_voucher_id is null
        $paymentsQuery = VoucherPayment::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter', 'freight_booking'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('company_id', $companyId)
            ->whereNull('linked_voucher_id')
            ->where('payment_mode', 'bank transfer');

        if ($this->paymentStatus) {
            $paymentsQuery->where('payment_status', $this->paymentStatus);
        }

        $payments = $paymentsQuery->get();

        // Step 2: Fetch all records from voucher_linked table
        $linkedPaymentsQuery = VoucherLinked::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter', 'freight_booking'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('company_id', $companyId)
            ->where('payment_mode', 'bank transfer');

        if ($this->paymentStatus) {
            $linkedPaymentsQuery->where('payment_status', $this->paymentStatus);
        }

        $linked_payments = $linkedPaymentsQuery->get();

        $allPayments = [];

        // Process unlinked voucher payments
        foreach ($payments as $payment) {
            $freightBooking = $payment->freight_booking;
            $lr = $freightBooking?->lr;
            $transporter = $lr?->transporter;
            $truck = $lr?->truck;
            $tds_deducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
            $status = ucfirst(str_replace('-', ' ', $payment->payment_status ?? 'N/A'));
            $allPayments[] = [
                'Date' => Date::dateTimeToExcel(\Carbon\Carbon::parse($payment->date)),
                'LR Number' => $lr?->lr_number ?? '-',
                'Truck Number' => $truck?->truck_number ?? '-',
                'Transporter Name' => $transporter?->name ?? '-',
                'Short Name' => $transporter?->short_name ?? '-',
                'Beneficiary ID' => $transporter?->beneficiary_id ?? '-',
                'Amount' => $payment['amount'] ?? 0,
                'Narration' => 'GR NO ' . ($lr?->gr_number ?? '-'),
                'TDS Deducted' => $tds_deducted,
                'Status' => $status,
            ];
        }

        // Process linked voucher payments
        foreach ($linked_payments as $linked_payment) {
            $freightBooking = $linked_payment->freight_booking;
            $lr = $freightBooking?->lr;
            $transporter = $lr?->transporter;
            $truck = $lr?->truck;
            $tds_deducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
            $status = ucfirst(str_replace('-', ' ', $linked_payment->payment_status ?? 'N/A'));
            $allPayments[] = [
                'Date' => Date::dateTimeToExcel(\Carbon\Carbon::parse($linked_payment->date)),
                'LR Number' => $lr?->lr_number ?? '-',
                'Truck Number' => $truck?->truck_number ?? '-',
                'Transporter Name' => $transporter?->name ?? '-',
                'Short Name' => $transporter?->short_name ?? '-',
                'Beneficiary ID' => $transporter?->beneficiary_id ?? '-',
                'Amount' => $linked_payment['amount'] ?? 0,
                'Narration' => 'GR NO ' . ($lr?->gr_number ?? '-'),
                'TDS Deducted' => $tds_deducted,
                'Status' => $status,
            ];
        }

        // Step 3: Sort by date
        $allPayments = collect($allPayments)->sortBy('Date')->values();

        return $allPayments;
    }

    public function headings(): array
    {
        return [
            'Date',
            'LR Number',
            'Truck Number',
            'Transporter/Truck Owner Name',
            'Short Name',
            'Beneficiary ID',
            'Amount ()',
            'Narration',
            'TDS Deducted',
            'Status',
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

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
