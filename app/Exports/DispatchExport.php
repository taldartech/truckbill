<?php

namespace App\Exports;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Invoice;
use App\Models\Truck;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DispatchExport implements FromCollection, WithHeadings, ShouldAutoSize
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
    // Fetch data based on the provided parameters
    // $company_id = Auth::user()->company_id;
    // $query = FreightBooking::whereHas('lr', function ($query) {
    //   $query->whereBetween('gr_date', [$this->startDate, $this->endDate]);
    // });

    // if ($company_id) {
    //   $query->where('company_id', $company_id);
    // }
    // $query->where('tds_amount', '>', 0);

    // $lrs = $query->get();
    $user = Auth::user()->company_id;
    if ($this->startDate || $this->endDate) {
      // First, get the LorryReceipts with their freight bookings and voucher payments
      $query = LorryReceipt::with([
          'freightBookings' => function($q) {
              $q->with(['voucherPayments']);
          },
          'consignor',
          'consignee',
          'customer',
          'truck'
      ])->whereBetween('gr_date', [$this->startDate, $this->endDate]);

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
        "Date" => $lr->gr_date,
        "LR No" => $lr->lr_number,
        "Truck No" => $lr->truck->truck_number ?? 'N/A',
        "Destination" => $lr->destination_city ,
        "Invoice No." => $lr->invoice_number,
       
        "Weight" => $lr->weight,
        "Bags" => $lr->no_of_units,
        "Fr rate" => $lr->fr_rate,
        " Amount" => $lr->amount,
        "Unloading st." => $lr->destination_state,
        "Distt. " => $lr->destination_city,
        "State" => $lr->destination_state,
        "Consignor" => $lr->consignor->name ?? 'N/A',
        "Consignee" => $lr->consignee->name ?? 'N/A',
        "GST Number" => $lr->customer->gst_number ?? 'N/A',
        "TR Rate" => (float)($freightBooking->tr_fr_rate ?? 'N/A'),
        "TR Freight" => (float)($freightBooking->tr_amount ?? 'N/A'),
        "TDS" => (float)($freightBooking->tds_amount ?? 'N/A'),
        "Cash Advance" =>$payments['cash'],
        "Bank" =>$payments['bank transfer'],
        "Adjust In Advance" =>$payments['adjust in advance'],
        "Diesel" =>$payments['fuel(paid to pump)'],
        "Total" => (float)($payments['cash'] + $payments['bank transfer'] + $payments['adjust in advance'] + $payments['fuel(paid to pump)'] + ($freightBooking->tds_amount ?? 0)),
        "Damage Amount" => (float)($freightBooking->damage_amount ?? 0),
        "Shortage Amount" => (float)($freightBooking->shortage_amount ?? 0),
        "Detention" => (float)($freightBooking->detention ?? 0),
        "Deviation" => (float)($freightBooking->deviation ?? 0),
        "Misc." => (float)($freightBooking->misc ?? 0),
        "Balance" => (float)($freightBooking->balance_amount ?? 0),
        "Diff" => (float)($lr->amount - ($freightBooking->balance_amount ?? 0)),
        "Memo No." => $lr->memo_id ?? 'N/A',
        "Bill No." => $invoice->manual_invoice_number ?? 'N/A',
        "Driver No." => $truck->driver->mobile ?? 'N/A',
        "Made to Payment" => $lr->transporter->name ?? 'N/A',
        "short name" => $lr->transporter->short_name ?? 'N/A',
        "transport type" => $lr->transporter_type ?? 'N/A',
        "TDS Deducted" => $lr->transporter->tds_percentage == 0 ? 'No' : 'Yes',
        "E-way Bill No." => $freightBooking->eway_bill_no ?? 'N/A',
        "E-way Bill Expiry Date" => $freightBooking->eway_bill_expiry_date ?? 'N/A',
        "Remarks" => $lr->delivery_status ?? 'N/A',
        "PAN No" => $lr->transporter->pan_number ?? 'N/A',
      
       
      ];
    });
  }

  public function headings(): array
  {
    return [
      "Sr No",
      "GR Date",
      "LR No",
      "Truck No",
      "Destination",
      "Invoice No.",
      "Weight",
      "Bags",
      "Fr rate",
      "Amount",
      "Unloading st.",
      "Distt.",
      "State",
      "Consignor",
      "Consignee",
      "GST Number",
      "Tr Rate",
      "Tr Freight",
      "TDS",
      "Cash Advance",
      "Bank",
      "Adjust In Advance",
      "Diesel",
      "Total",
      "Damage Amount",
      "Shortage Amount",
      "Detention",
      "Deviation",
      "Misc.",
      "Balance",
      "Diff",
      "Memo No.",
      "Bill No.",
      "Driver No.",
      "Transport Name",
      "Made to Payment",
      "Payment Made to",
      "TDS Deducted",
      "E-way Bill No.",
      "E-way Bill Expiry Date",
      "Remarks",
      "PAN No"
    ];
  }
}
