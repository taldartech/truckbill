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
        $companyId = auth()->user()->company_id;

        // Get the pump name
        $pump = \App\Models\Pump::find($this->pumpId);
        if (!$pump) {
            return $data;
        }

        // Add the pump name and date range as the first rows
        $data->push(['Pump:', $pump->pump_name]);
        $data->push(['Date Range:', $this->startDate . ' to ' . $this->endDate]);
        $data->push([]); // Empty row

        // Add headers
        $data->push([
            'LR Number',
            'Date',
            'Truck Number',
            'Destination',
            'Amount (₹)',

        ]);

        // Get all voucher payments for the selected pump and date range
        $voucherPayments = \App\Models\VoucherPayment::with(['freight_booking.lr', 'pump'])
            ->where('pump_id', $this->pumpId)
            ->where('company_id', $companyId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $processedFreightBookingIds = [];
        $totalAmount = 0;

        foreach ($voucherPayments as $payment) {
            // Skip if we've already processed this freight booking
            if (in_array($payment->freight_booking_id, $processedFreightBookingIds)) {
                continue;
            }

            $lrDetails = null;
            $amount = 0;
            $date = null;


            if ($payment->linked_voucher_id) {
                // Handle linked voucher
                $linked = \App\Models\VoucherLinked::where('id', $payment->linked_voucher_id)
                    ->where('company_id', $companyId)
                    ->where('freight_booking_id', $payment->freight_booking_id)
                    ->where('pump_id', $payment->pump_id)
                    ->first();

                if ($linked) {
                    $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $linked->freight_booking_id)
                        ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                        ->with('truck')
                        ->first();

                    $amount = $linked->amount;
                    $date = $linked->date;
                }
            } else {
                // Handle unlinked voucher
                $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $payment->freight_booking_id)
                    ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                    ->with('truck')
                    ->first();

                $amount = $payment->amount;
                $date = $payment->date;
            }

            if ($lrDetails) {
                $data->push([
                    $lrDetails->lr_number ?? 'N/A',
                    \Carbon\Carbon::parse($date)->format('d-m-Y'),
                    $lrDetails->truck->truck_number ?? 'N/A',
                    $lrDetails->destination_city . ', ' . $lrDetails->destination_state,
                    $amount,
                ]);

                $totalAmount += $amount;
                $processedFreightBookingIds[] = $payment->freight_booking_id;
            }
        }

        // Add total row
        $data->push(['', '', '', 'Total:', $totalAmount, '']);

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
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
