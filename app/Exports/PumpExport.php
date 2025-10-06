<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PumpExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $startDate;
    private $endDate;
    private $pumpId;

    public function __construct($startDate, $endDate, $pumpId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->pumpId = $pumpId;
    }

    public function collection()
    {
        $userCompanyId = Auth::user()->company_id;
        $data = collect();

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
                //before table show pump_name
                $pumpName = $voucherPayments->where('freight_booking_id', $payment['freight_booking_id'])->first()->pump->pump_name;
                if ($lrDetails) {
                    $data->push([
                        'pump_name' => $pumpName,
                        'lr_number' => $lrDetails->lr_number ?? 'N/A',
                        'date' => \Carbon\Carbon::parse($payment['date'])->format('d-m-Y'),
                        'truck_number' => $lrDetails->truck->truck_number ?? 'N/A',
                        'destination' => $lrDetails->destination_state ?? 'N/A',
                        'amount' => $payment['total_amount'],
                    ]);
                }
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Pump Name',
            'LR Number',
            'Date',
            'Truck Number',
            'Destination',
            'Amount (₹)'
        ];
    }
}