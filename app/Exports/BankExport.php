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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BankExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithColumnFormatting
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

        $query = VoucherPayment::whereBetween('date', [$this->startDate, $this->endDate]);
        $query->where('company_id', $companyId);
        $query->whereNull('linked_voucher_id');
        $query->where('payment_mode', 'bank transfer');
        $query->where('payment_status', 'in-progress');
        $payments = $query->get();

        $query1 = VoucherLinked::whereBetween('date', [$this->startDate, $this->endDate]);
        $query1->where('company_id', $companyId);
        $query1->where('payment_mode', 'bank transfer');
        $query1->where('payment_status', 'in-progress');
        $linked_payments = $query1->get();
        $allPayments = [];
        if (isset($payments)) {
            foreach ($payments as $payment) {
                $tds_deducted = $payment->freight_booking->tds_amount == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'Date' => Date::dateTimeToExcel(\Carbon\Carbon::parse($payment->date)),
                    'LR Number' => $payment->freight_booking->lr->lr_number ?? '-',
                    'Truck Number' => $payment->freight_booking->lr->truck->truck_number ?? '-',
                    'Transporter Name' => $payment->freight_booking->lr->transporter->name ?? '-',
                    'Short Name' => $payment->freight_booking->lr->transporter->short_name ?? '-',
                    'Beneficiary ID' => $payment->freight_booking->lr->transporter->beneficiary_id ?? '-',
                    'Amount' => $payment['amount'] ?? 0,
                    'Narration' => $payment->freight_booking->lr->gr_number ?? '-',
                    'TDS Deducted' => $tds_deducted,
                ];
            }
        }
        if (isset($linked_payments)) {
            foreach ($linked_payments as $linked_payment) {
                $tds_deducted = $linked_payment->freight_booking->tds_amount == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'Date' => Date::dateTimeToExcel(\Carbon\Carbon::parse($linked_payment->date)),
                    'LR Number' => $linked_payment->freight_booking->lr->lr_number ?? '-',
                    'Truck Number' => $linked_payment->freight_booking->lr->truck->truck_number ?? '-',
                    'Transporter Name' => $linked_payment->freight_booking->lr->transporter->name ?? '-',
                    'Short Name' => $linked_payment->freight_booking->lr->transporter->short_name ?? '-',
                    'Beneficiary ID' => $linked_payment->freight_booking->lr->transporter->beneficiary_id ?? '-',
                    'Amount' => $linked_payment['amount'] ?? 0,
                    'Narration' => $linked_payment->freight_booking->lr->gr_number ?? '-',
                    'TDS Deducted' => $tds_deducted,
                ];
            }
            $allPayments = collect($allPayments)->sortBy('Date')->values();
        }

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
            'Amount (₹)',
            'Narration',
            'TDS Deducted',
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
