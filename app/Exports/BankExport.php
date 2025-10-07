<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class BankExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $payments;
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = \App\Models\VoucherPayment::with([
                'freight_booking.lr',
                'freight_booking.truck',
                'freight_booking.transporter'
            ])
            ->where('payment_mode', 'bank transfer')
            ->whereHas('freight_booking', function($q) {
                $companyId = auth()->user()->company_id;
                if ($companyId) {
                    $q->where('company_id', $companyId);
                }
            });

        if ($this->startDate) {
            $query->where('date', '>=', $this->startDate);
        }
        
        if ($this->endDate) {
            $query->where('date', '<=', $this->endDate);
        }

        $this->payments = $query->get()
            ->map(function($payment) {
                return [
                    'date' => $payment->date,
                    'lr_number' => $payment->freight_booking->lr->lr_number ?? 'N/A',
                    'truck_number' => $payment->freight_booking->lr->truck->truck_number ?? 'N/A',
                    'party' => $payment->freight_booking->transporter->name ?? 'N/A',
                    'name'=>$payment->freight_booking->transporter->short_name ?? 'N/A',
                    'beneficiary_id' => $payment->freight_booking->transporter->beneficiary_id ?? 'N/A',
                    'amount' => $payment->amount,
                ];
            });

        return $this->payments;
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
            'A:F' => [
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