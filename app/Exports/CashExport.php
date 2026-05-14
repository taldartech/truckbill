<?php

namespace App\Exports;

use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithColumnFormatting
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

        $paymentsQuery = VoucherPayment::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter', 'freight_booking'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('company_id', $companyId)
            ->whereNull('linked_voucher_id')
            ->where('payment_mode', 'cash');

        if ($this->paymentStatus) {
            $paymentsQuery->where('payment_status', $this->paymentStatus);
        }

        $payments = $paymentsQuery->get();

        $linkedPaymentsQuery = VoucherLinked::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter', 'freight_booking'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('company_id', $companyId)
            ->where('payment_mode', 'cash');

        if ($this->paymentStatus) {
            $linkedPaymentsQuery->where('payment_status', $this->paymentStatus);
        }

        $linkedPayments = $linkedPaymentsQuery->get();

        $allPayments = [];

        foreach ($payments as $payment) {
            $freightBooking = $payment->freight_booking;
            $lr = $freightBooking?->lr;
            $transporter = $lr?->transporter;
            $truck = $lr?->truck;
            $tdsDeducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
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
                'TDS Deducted' => $tdsDeducted,
                'Status' => $status,
            ];
        }

        foreach ($linkedPayments as $linkedPayment) {
            $freightBooking = $linkedPayment->freight_booking;
            $lr = $freightBooking?->lr;
            $transporter = $lr?->transporter;
            $truck = $lr?->truck;
            $tdsDeducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
            $status = ucfirst(str_replace('-', ' ', $linkedPayment->payment_status ?? 'N/A'));
            $allPayments[] = [
                'Date' => Date::dateTimeToExcel(\Carbon\Carbon::parse($linkedPayment->date)),
                'LR Number' => $lr?->lr_number ?? '-',
                'Truck Number' => $truck?->truck_number ?? '-',
                'Transporter Name' => $transporter?->name ?? '-',
                'Short Name' => $transporter?->short_name ?? '-',
                'Beneficiary ID' => $transporter?->beneficiary_id ?? '-',
                'Amount' => $linkedPayment['amount'] ?? 0,
                'Narration' => 'GR NO ' . ($lr?->gr_number ?? '-'),
                'TDS Deducted' => $tdsDeducted,
                'Status' => $status,
            ];
        }

        return collect($allPayments)->sortBy('Date')->values();
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
        return 'Cash Payments';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:G' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                ],
            ],
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
