<?php

namespace App\Exports;

use App\Models\VoucherPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoucherPaymentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = VoucherPayment::with([
            'freight_booking.lr',
            'company',
            'freight_booking.transporter',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'freight_booking.lr.truck'
        ]);

        // Apply filters
        if (!empty($this->filters['voucher_number'])) {
            $query->where('voucher_number', 'like', '%' . $this->filters['voucher_number'] . '%');
        }

        if (!empty($this->filters['payment_type'])) {
            $query->where('payment_type', $this->filters['payment_type']);
        }

        if (!empty($this->filters['payment_mode'])) {
            $query->where('payment_mode', $this->filters['payment_mode']);
        }

        if (!empty($this->filters['transaction_id'])) {
            $query->where('transaction_id', 'like', '%' . $this->filters['transaction_id'] . '%');
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('payment_date', [
                $this->filters['start_date'],
                $this->filters['end_date']
            ]);
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Voucher #',
            'Payment Date',
            'Amount',
            'Payment Type',
            'Payment Mode',
            'Transaction ID',
            'Status',
            'Truck No',
            'Transporter',
            'From',
            'To',
            'Created At'
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->voucher_number,
            $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-M-Y') : 'N/A',
            number_format($payment->amount, 2),
            $payment->payment_type,
            $payment->payment_mode,
            $payment->transaction_id ?? 'N/A',
            ucfirst($payment->status),
            $payment->freight_booking->lr->truck->truck_number ?? 'N/A',
            $payment->freight_booking->transporter->name ?? 'N/A',
            $payment->freight_booking->lr->consignor->name ?? 'N/A',
            $payment->freight_booking->lr->consignee->name ?? 'N/A',
            $payment->created_at->format('d-M-Y H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAD3']
                ]
            ],
            // Set border for all cells
            'A1:L' . ($this->collection()->count() + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}
