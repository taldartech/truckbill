<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
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
            'Status',
        ]);

        // Step 1: Fetch records from voucher_payments where linked_voucher_id is null
        $voucherPayments = \App\Models\VoucherPayment::with(['freight_booking.lr', 'pump', 'freight_booking'])
            ->where('pump_id', $this->pumpId)
            ->where('company_id', $companyId)
            ->whereNull('linked_voucher_id')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        // Step 2: Fetch all records from voucher_linked table
        $linkedPayments = \App\Models\VoucherLinked::with(['freight_booking.lr', 'freight_booking'])
            ->where('pump_id', $this->pumpId)
            ->where('company_id', $companyId)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $allPayments = collect();
        $processedFreightBookingIds = [];
        $totalAmount = 0;

        // Process unlinked voucher payments
        foreach ($voucherPayments as $payment) {
            // Skip if we've already processed this freight booking
            if (in_array($payment->freight_booking_id, $processedFreightBookingIds)) {
                continue;
            }

            $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $payment->freight_booking_id)
                ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                ->with('truck')
                ->first();

            if ($lrDetails) {
                $allPayments->push([
                    'date' => \Carbon\Carbon::parse($payment->date),
                    'lr_number' => $lrDetails->lr_number ?? 'N/A',
                    'truck_number' => $lrDetails->truck->truck_number ?? 'N/A',
                    'destination' => $lrDetails->destination_city . ', ' . $lrDetails->destination_state,
                    'amount' => $payment->amount,
                    'payment_status' => $payment->payment_status ?? 'N/A',
                ]);

                $totalAmount += $payment->amount;
                $processedFreightBookingIds[] = $payment->freight_booking_id;
            }
        }

        // Process linked voucher payments
        foreach ($linkedPayments as $linked) {
            // Skip if we've already processed this freight booking
            if (in_array($linked->freight_booking_id, $processedFreightBookingIds)) {
                continue;
            }

            $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $linked->freight_booking_id)
                ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                ->with('truck')
                ->first();

            if ($lrDetails) {
                $allPayments->push([
                    'date' => \Carbon\Carbon::parse($linked->date),
                    'lr_number' => $lrDetails->lr_number ?? 'N/A',
                    'truck_number' => $lrDetails->truck->truck_number ?? 'N/A',
                    'destination' => $lrDetails->destination_city . ', ' . $lrDetails->destination_state,
                    'amount' => $linked->amount,
                    'payment_status' => $linked->payment_status ?? 'N/A',
                ]);

                $totalAmount += $linked->amount;
                $processedFreightBookingIds[] = $linked->freight_booking_id;
            }
        }

        // Step 3: Sort by date
        $allPayments = $allPayments->sortBy('date')->values();

        // Add rows to data collection
        foreach ($allPayments as $payment) {
            $status = ucfirst(str_replace('-', ' ', $payment['payment_status'] ?? 'N/A'));
            $data->push([
                $payment['lr_number'],
                $payment['date']->format('d-m-Y'),
                $payment['truck_number'],
                $payment['destination'],
                $payment['amount'],
                $status,
            ]);
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
