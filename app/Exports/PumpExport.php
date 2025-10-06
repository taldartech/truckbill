<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PumpExport implements FromCollection, ShouldAutoSize, WithEvents
{
    private $startDate;
    private $endDate;
    private $pumpId;
    private $pumpName;

    public function __construct($startDate, $endDate, $pumpId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->pumpId = $pumpId;
        $this->pumpName = \App\Models\Pump::find($pumpId)->pump_name ?? 'Pump Report';
    }

    public function collection()
    {
        $data = collect();
        $userCompanyId = auth()->user()->company_id;

        // Get the pump name
        $pump = \App\Models\Pump::find($this->pumpId);
        if (!$pump) {
            return $data;
        }

        // Add the pump name as the first row
        $data->push(['Pump:', $pump->pump_name]);

        // Add an empty row
        $data->push([]);
         $data->push([
            'LR Number',
            'Date',
            'Truck Number',
            'Destination',
            'Amount (₹)'
        ]);

        // Add the headers
        // $data->push($this->headings());

        // Get all voucher payments for the selected pump and date range
        $voucherPayments = \App\Models\VoucherPayment::with(['freight_booking.lr', 'pump'])
            ->where('pump_id', $this->pumpId)
            ->where('company_id', $userCompanyId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        if ($voucherPayments->isNotEmpty()) {
            // Group by freight_booking_id and calculate total amount
            $groupedPayments = $voucherPayments->groupBy('freight_booking_id')->map(function ($group) {
                return [
                    'freight_booking_id' => $group->first()->freight_booking_id,
                    'total_amount' => $group->sum('amount'),
                    'date' => $group->first()->date
                ];
            });

            // Fetch all LR records related to these freight booking IDs
            $freightBookingIds = $groupedPayments->pluck('freight_booking_id')->toArray();
           
            $lrs = \App\Models\LorryReceipt::whereIn('freight_booking_id', $freightBookingIds)
                ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id')
                ->with('truck')
                ->get();

            // Format data for export
            foreach ($groupedPayments as $payment) {
                $lrDetails = $lrs->where('freight_booking_id', $payment['freight_booking_id'])->first();
                if ($lrDetails) {
                    $data->push([
                        $lrDetails->lr_number ?? 'N/A',
                        \Carbon\Carbon::parse($payment['date'])->format('d-m-Y'),
                        $lrDetails->truck->truck_number ?? 'N/A',
                        $lrDetails->destination_state ?? 'N/A',
                        $payment['total_amount'],
                    ]);
                }
            }

            // Add total row
            $totalAmount = $groupedPayments->sum('total_amount');
            $data->push(['', '', '', 'Total:', $totalAmount]);
        }

        return $data;
    }

    // public function headings(): array
    // {
    //     return [
    //         'LR Number',
    //         'Date',
    //         'Truck Number',
    //         'Destination',
    //         'Amount (₹)'
    //     ];
    // }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Style the total row
                $lastRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle("D{$lastRow}:E{$lastRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ]
                ]);
    
                // Format amount column (starts from row 1)
                $event->sheet->getStyle("E1:E{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            },
        ];
    }
}