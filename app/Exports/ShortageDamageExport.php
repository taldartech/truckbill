<?php

namespace App\Exports;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Invoice;
use App\Models\Truck;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ShortageDamageExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    private $startDate, $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $user = Auth::user()->company_id;
        if ($this->startDate || $this->endDate) {

            $query = LorryReceipt::with([
                'freightBookings' => function ($q) {
                    // Sirf wahi freight bookings load karo jinke shortage/damage amount > 0
                    $q->with(['voucherPayments'])
                        ->where(function ($sub) {
                            $sub->where('shortage_amount', '>', 0)
                                ->orWhere('damage_amount', '>', 0);
                        });
                },
                'consignor',
                'consignee',
                'customer',
                'truck'
            ])
                ->whereBetween('gr_date', [$this->startDate, $this->endDate])
                // Sirf wahi LRs jinke freightBookings me shortage/damage amount > 0
                ->whereHas('freightBookings', function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('shortage_amount', '>', 0)
                            ->orWhere('damage_amount', '>', 0);
                    });
                })
                ->orderBy('gr_date', 'asc');

            if ($user) {
                $query->where('company_id', $user);
            }

            $lrs = $query->get();

            // Debug: Log the first few records to check relationships
            if ($lrs->isNotEmpty()) {
                \Log::info('First LorryReceipt data:', [
                    'lr_id' => $lrs[0]->id,
                    'lr_number' => $lrs[0]->lr_number,
                    'has_freight_bookings' => $lrs[0]->relationLoaded('freightBookings') && $lrs[0]->freightBookings->isNotEmpty(),
                    'freight_booking_count' => $lrs[0]->freightBookings->count(),
                    'voucher_payments' => $lrs[0]->freightBookings->isNotEmpty() ?
                        $lrs[0]->freightBookings[0]->voucherPayments->toArray() : 'No freight bookings'
                ]);
            }

            $generated = true;
        } else {
            $lrs = [];
            $generated = false;
        }

        // Map data to the desired format
        return $lrs->map(function ($lr, $index) {

            // Get the first freight booking (or null if none exists)
            $freightBooking = $lr->freightBookings->first();

            // Initialize payments array with all payment types
            $payments = [
                'cash' => 0,
                'adjust in advance' => 0,
                'bank transfer' => 0,
                'fuel(paid to pump)' => 0,
            ];

            // If we have a freight booking with loaded voucher payments
            if ($freightBooking && $freightBooking->relationLoaded('voucherPayments')) {
                // Group payments by payment mode and sum amounts
                $paymentSums = $freightBooking->voucherPayments
                    ->groupBy('payment_mode')
                    ->map(function ($payments) {
                        return $payments->sum('amount');
                    });

                // Map the grouped payments to our expected keys
                foreach ($payments as $mode => $amount) {
                    $normalizedMode = strtolower(trim($mode));
                    $matchingMode = $paymentSums->keys()->first(function ($key) use ($normalizedMode) {
                        return strtolower(trim($key)) === $normalizedMode;
                    });

                    if ($matchingMode !== null) {
                        $payments[$mode] = $paymentSums[$matchingMode];
                    }
                }
            }


            $lrIds = $lr->id;
            // in invoice table lr_ids are stored in array form

            $invoice = Invoice::whereJsonContains('lr_ids', $lrIds)->first();
            //   in truck table their is dirver id from that find mobile number
            $truck = Truck::where('driver_id', $lr->truck->driver_id)->first();

            return [
                "Sr No" => $index + 1,
                "LR No" => $lr->lr_number,
                "Date" => Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)),
                "Truck No" => $lr->truck->truck_number ?? 'N/A',
                "Weight" => $lr->weight,
                "TR Rate" => (float)($freightBooking->tr_fr_rate ?? 'N/A'),
                "TR Freight" => (float)($freightBooking->tr_amount ?? 'N/A'),
                "Consignor" => $lr->consignor->name ?? 'N/A',
                "Consignee" => $lr->consignee->name ?? 'N/A',
                "Destination" => $lr->destination_city,
                "Shortage Amount" => (float)($freightBooking->shortage_amount ?? 0),
                "Damage Amount" => (float)($freightBooking->damage_amount ?? 0),
            ];
        });
    }

    public function headings(): array
    {
        return [
            "Sr No",
            "LR No",
            "GR Date",

            "Truck No",
            "Weight",
            "LR Rate",
            "LR Amount",

            "Consignor",
            "Consignee",
            "Destination",
            "Shortage Amount",
            "Damage Amount",

        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
