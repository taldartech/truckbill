<?php

namespace App\Http\Controllers;

use App\Exports\ConsignorConsigneeExport;
use App\Exports\TDSExport;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DispatchExport;
use App\Exports\PartyLedgerExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoice;
use App\Exports\InvoiceExport;
use App\Exports\PartyBillExport;
use App\Models\Consignees;
use App\Exports\ShortageDamageExport;
use App\Exports\EwayBillExport;
use App\Exports\MemoCopyExport;
use App\Models\Pump;
use App\Exports\PumpExport;
use App\Models\VoucherPayment;
use App\Exports\BankExport;
use App\Exports\UltratechBillExport;
use App\Models\VoucherLinked;
use Carbon\Carbon;

class ReportController extends Controller
{
  public function consignor_consignee(Request $request)
  {
    $consignees = Consignee::where('company_id', Auth::user()->company_id)->get();
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $consignorId = $request->input('consignor_id') ?? null;
    $consigneeId = $request->input('consignee_id') ?? null;

    if ($consignorId || $consigneeId) {
      $user = Auth::user()->company_id;
      $query = LorryReceipt::whereBetween('gr_date', [$startDate, $endDate]);
      if ($user) {
        $query->where('company_id', $user);
      }

      if ($consigneeId) {
        $query->where('consignee_id', $consigneeId);
      }

      if ($consignorId) {
        $query->where('consignor_id', $consignorId);
      }

      $lrs = $query->get();
    } else {
      $lrs = [];
    }

    return view('reports.consignor-consignee', compact('lrs', 'consignees', 'startDate', 'endDate', 'consignorId', 'consigneeId'));
  }

  public function consignor_consignee_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $consignorId = $request->input('consignor_id');
    $consigneeId = $request->input('consignee_id');

    $export = Excel::download(new ConsignorConsigneeExport($startDate, $endDate, $consignorId, $consigneeId), 'consignor_consignee_report.xlsx');
    return $export;
  }

  public function tds(Request $request)
  {
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $user = Auth::user()->company_id;
    if ($request->input('end_date') || $request->input('start_date')) {
      $query = FreightBooking::whereHas('lr', function ($query) use ($startDate, $endDate) {
        $query->whereBetween('gr_date', [$startDate, $endDate]);
      });
      if ($user) {
        $query->where('company_id', $user);
      }
      $query->where('tds_amount', '>', 0);
      $lrs = $query->get();
      $generated = true;
    } else {
      $lrs = [];
      $generated = false;
    }
    return view('reports.tds', compact('lrs', 'startDate', 'endDate', 'generated'));
  }

  public function tds_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $export = Excel::download(new TDSExport($startDate, $endDate), 'tds_report.xlsx');
    return $export;
  }
  // public function dispatch(Request $request)
  // {
  //   $endDate = $request->input('end_date') ?? date('Y-m-d');
  //   $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

  //   $user = Auth::user()->company_id;
  //   if ($request->input('end_date') || $request->input('start_date')) {


  //     $query = FreightBooking::with('voucherPayments')->whereHas('lr', function($query) use ($startDate, $endDate) {
  //       $query->whereBetween('gr_date', [$startDate, $endDate]);
  //     });
  //     if ($user) {
  //       $query->where('company_id', $user);
  //     }
  //     // $query->where('tds_amount', '>', 0);
  //     $lrs = $query->get();
  //     $generated = true;
  //   } else {
  //     $lrs = [];
  //     $generated = false;
  //   }
  //   return view('reports.dispatch', compact('lrs', 'startDate', 'endDate', 'generated'));
  // }
  public function dispatch(Request $request)
  {
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $user = Auth::user()->company_id;

    if ($request->input('end_date') || $request->input('start_date')) {
      // First, get the LorryReceipts with their freight bookings and voucher payments
      $query = LorryReceipt::with([
        'freightBookings' => function ($q) {
          $q->with(['voucherPayments']);
        },
        'consignor',
        'consignee',
        'customer',
        'truck'
      ])->whereBetween('gr_date', [$startDate, $endDate]);

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

    return view('reports.dispatch', compact('lrs', 'startDate', 'endDate', 'generated'));
  }
  public function dispatch_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $export = Excel::download(new DispatchExport($startDate, $endDate), 'dispatch_report.xlsx');
    return $export;
  }

  public function party_ledger(Request $request)
  {
    $consignees = Consignee::where('company_id', Auth::user()->company_id)->get();
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $customerId = $request->input('customer_id') ?? null;

    if ($customerId) {
      $user = Auth::user()->company_id;
      $query = LorryReceipt::whereBetween('gr_date', [$startDate, $endDate]);
      $query->where('invoice_generated', 1);
      if ($user) {
        $query->where('company_id', $user);
      }

      if ($customerId) {
        $query->where('customer_id', $customerId);
      }

      $lrs = $query->get();
    } else {
      $lrs = [];
    }
    return view('reports.party-ledger', compact('lrs', 'consignees', 'startDate', 'endDate', 'customerId'));
  }

  public function party_ledger_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $customerId = $request->input('customer_id');

    $export = Excel::download(new PartyLedgerExport($startDate, $endDate, $customerId), 'party_ledger_report.xlsx');
    return $export;
  }

  public function lr_copy(Request $request)
  {
    $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
    $lr = null;
    $customerId = $request->input('customer_id') ?? null;
    $lrs = LorryReceipt::where('customer_id', $customerId)->get();
    $lr_id = $request->input('lr_id') ?? null;
    if ($lr_id) {
      $lr = LorryReceipt::find($request->input('lr_id'));
    }
    return view('reports.lr-copy', compact('lr', 'customers', 'customerId', 'lr_id', 'lrs'));
  }

  public function lr_copy_export(Request $request)
  {
    if (!$request->input('lr_id')) {
      return redirect()->back()->with('error', 'Please select a Lorry Receipt to export.');
    }
    $lr = LorryReceipt::find($request->input('lr_id'));
    $html = view('reports.exports.lr-copy', compact('lr'))->render();

    $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

    return $pdf->stream('lr_copy.pdf');
  }
  public function invoice(Request $request)
  {
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $user = Auth::user()->company_id;
    $invoices = [];
    $generated = false;

    if ($request->input('end_date') || $request->input('start_date')) {
      $query = Invoice::with(['consignee', 'consignee.state', 'company'])
        ->whereBetween('generated_date', [$startDate, $endDate]);

      if ($user) {
        $query->where('company_id', $user);
      }

      $invoices = $query->orderBy('generated_date', 'desc')->get();
      $generated = true;
    }

    return view('reports.invoice', compact('invoices', 'startDate', 'endDate', 'generated'));
  }
  public function invoice_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $export = Excel::download(new InvoiceExport($startDate, $endDate), 'GSTR1.xlsx');
    return $export;
  }
  public function shortage_damage(Request $request)
  {
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $user = Auth::user()->company_id;
    $invoices = [];
    $generated = false;

    if ($request->input('end_date') || $request->input('start_date')) {
      // First, get the LorryReceipts with their freight bookings and voucher payments
      $query = LorryReceipt::with([
        'freightBookings' => function ($q) {
          $q->with(['voucherPayments']);
        },
        'consignor',
        'consignee',
        'customer',
        'truck'
      ])->whereBetween('gr_date', [$startDate, $endDate]);

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


    return view('reports.shortage-damage', compact('lrs', 'startDate', 'endDate', 'generated'));
  }
  public function shortage_damage_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $export = Excel::download(new ShortageDamageExport($startDate, $endDate), 'shortage_damage_report.xlsx');
    return $export;
  }
  public function eway_bill(Request $request)
  {
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $user = Auth::user()->company_id;
    $invoices = [];
    $generated = false;

    if ($request->input('end_date') || $request->input('start_date')) {
      // First, get the LorryReceipts with their freight bookings and voucher payments
      $query = FreightBooking::whereBetween('eway_bill_expiry', [$startDate, $endDate]);
      if ($user) {
        $query->where('company_id', $user);
      }
      $frs = $query->get();
      $generated = true;
    } else {
      $frs = [];
      $generated = false;
    }

    return view('reports.eway-bill', compact('frs', 'startDate', 'endDate', 'generated'));
  }
  public function eway_bill_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $export = Excel::download(new EwayBillExport($startDate, $endDate), 'eway_bill_report.xlsx');
    return $export;
  }

  //   public function pump(Request $request)
  // {
  //   $pumps = Pump::all();
  //     $endDate = $request->input('end_date') ?? date('Y-m-d');
  //     $startDate = max(
  //         $request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')),
  //         date('Y-m-d', strtotime('-3 months', strtotime($endDate)))
  //     );

  //     $pumpId = $request->input('pump_id');

  //     $userCompanyId = Auth::user()->company_id;

  //     $pumpData = collect(); // default empty collection

  //     if ($pumpId) {
  //         // Step 1: Get all voucher payments with this pump id and company
  //         $voucherPayments = \App\Models\VoucherPayment::where('pump_id', $pumpId)

  //         ->whereNull('linked_voucher_id')
  //             ->where('company_id', $userCompanyId)
  //             ->whereBetween('date', [$startDate, $endDate])
  //             ->get();


  //         if ($voucherPayments->isNotEmpty()) {
  //             // Step 2: Group by freight_booking_id and calculate total amount
  //             // $groupedPayments = $voucherPayments->groupBy('freight_booking_id')->map(function ($group) {
  //             //     return [
  //             //         'freight_booking_id' => $group->first()->freight_booking_id,
  //             //         'total_amount' => $group->first()->amount,
  //             //     ];
  //             // });

  //             // Step 3: Fetch all LR records related to these freight booking IDs
  //             $freightBookingIds = $voucherPayments->pluck('freight_booking_id')->toArray();

  //             $lrs = \App\Models\LorryReceipt::whereIn('freight_booking_id', $freightBookingIds)
  //                 ->select('freight_booking_id', 'lr_number', 'destination_state','truck_id')
  //                 ->get();

  //             // Step 4: Merge LR data with payment sums
  //             $pumpData = $voucherPayments->map(function ($payment) use ($lrs) {
  //                 $lrDetails = $lrs->where('freight_booking_id', $payment['freight_booking_id'])->values();
  //               //send date from voucher payment
  //               $voucherPayment = \App\Models\VoucherPayment::where('freight_booking_id', $payment['freight_booking_id'])->first();
  //               $date = $voucherPayment->date;
  //                 return [
  //                     'freight_booking_id' => $payment['freight_booking_id'],
  //                     'amount' => $payment['amount'],
  //                     'lrs' => $lrDetails,
  //                     'date' => Carbon::parse($date)->format('d-m-Y'),
  //                 ];
  //             })->values();

  //         }
  //     }

  //     // Step 5: Return view with data
  //     return view('reports.pump', compact('pumpData', 'startDate', 'endDate', 'pumpId', 'pumps'));
  // }
  // public function pump(Request $request)
  // {
  //     $pumps = Pump::all();
  //     $endDate = $request->input('end_date') ?? date('Y-m-d');
  //     $startDate = max(
  //         $request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')),
  //         date('Y-m-d', strtotime('-3 months', strtotime($endDate)))
  //     );

  //     $pumpId = $request->input('pump_id');
  //     $companyId = Auth::user()->company_id;

  //     // --- Step 1: Query unlinked (direct) payments ---
  //     $paymentQuery = \App\Models\VoucherPayment::with([
  //             'freight_booking.lr.truck'
  //         ])
  //         ->where('payment_mode', 'bank transfer') // or 'pump'? Adjust if needed
  //         ->whereNull('linked_voucher_id')
  //         ->where('company_id', $companyId)
  //         ->when($pumpId, fn($q) => $q->where('pump_id', $pumpId))
  //         ->whereBetween('date', [$startDate, $endDate]);

  //     // --- Step 2: Query linked vouchers ---
  //     $linkedQuery = \App\Models\VoucherLinked::with([
  //             'freight_booking.lr.truck'
  //         ])
  //         ->where('payment_mode', 'bank transfer') // or 'pump'? Adjust if needed
  //         ->where('company_id', $companyId)
  //         ->when($pumpId, fn($q) => $q->where('pump_id', $pumpId))
  //         ->whereBetween('date', [$startDate, $endDate]);

  //     // --- Step 3: Fetch both datasets ---
  //     $payments = $paymentQuery->get()->map(function ($payment) {
  //         return [
  //             'freight_booking_id' => $payment->freight_booking_id,
  //             'lr_number'          => $payment->freight_booking->lr->lr_number ?? 'N/A',
  //             'truck_number'       => $payment->freight_booking->lr->truck->truck_number ?? 'N/A',
  //             'destination_state'  => $payment->freight_booking->lr->destination_state ?? 'N/A',
  //             'amount'             => $payment->amount,
  //             'date'               => \Carbon\Carbon::parse($payment->date)->format('d-m-Y'),
  //             'type'               => 'Unlinked', // for clarity
  //         ];
  //     });

  //     $linkedPayments = $linkedQuery->get()->map(function ($linked) {
  //         return [
  //             'freight_booking_id' => $linked->freight_booking_id,
  //             'lr_number'          => $linked->freight_booking->lr->lr_number ?? 'N/A',
  //             'truck_number'       => $linked->freight_booking->lr->truck->truck_number ?? 'N/A',
  //             'destination_state'  => $linked->freight_booking->lr->destination_state ?? 'N/A',
  //             'amount'             => $linked->amount,
  //             'date'               => \Carbon\Carbon::parse($linked->date)->format('d-m-Y'),
  //             'type'               => 'Linked',
  //         ];
  //     });

  //     // --- Step 4: Merge both ---
  //     $pumpData = $payments->merge($linkedPayments);

  //     // --- Step 5: Return to view ---
  //     return view('reports.pump', compact('pumpData', 'startDate', 'endDate', 'pumpId', 'pumps'));
  // }
  public function pump(Request $request)
  {
    $pumps = Pump::all();
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max(
      $request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')),
      date('Y-m-d', strtotime('-3 months', strtotime($endDate)))
    );

    $pumpId = $request->input('pump_id');
    $companyId = Auth::user()->company_id;

    $pumpData = collect(); // default empty collection

    if ($pumpId) {
      // --- Step 1: Fetch voucher payments (unlinked + linked) ---
      $voucherPayments = \App\Models\VoucherPayment::where('pump_id', $pumpId)
        ->where('company_id', $companyId)
        ->whereBetween('date', [$startDate, $endDate])
        ->get();

      // --- Step 2: Prepare merged data ---
      $pumpData = $voucherPayments->map(function ($payment) {
        // If linked_voucher_id exists, fetch from voucher_linked table


        if ($payment->linked_voucher_id) {
          // $linked = \App\Models\VoucherLinked::find($payment->linked_voucher_id)->where('pump_id', $payment->pump_id)->first();
          //from voucherlinked do not display same lr number from voucherlinked table 2 times
          $linked = \App\Models\VoucherLinked::where('id', $payment->linked_voucher_id)
            ->where('company_id', $payment->company_id)
            ->where('freight_booking_id', $payment->freight_booking_id)

            ->where('pump_id', $payment->pump_id)
            ->first();

          if ($linked) {
            $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $linked->freight_booking_id)
              ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id')
              ->get();

            return [
              'freight_booking_id' => $linked->freight_booking_id,
              'amount'             => $linked->amount,
              'lrs'                => $lrDetails,
              'date'               => \Carbon\Carbon::parse($linked->date)->format('d-m-Y'),
              'type'               => 'Linked',
            ];
          }
        }
        // Else fetch directly from voucher payments (unlinked)
        else {
          $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $payment->freight_booking_id)
            ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id')
            ->get();

          return [
            'freight_booking_id' => $payment->freight_booking_id,
            'amount'             => $payment->amount,
            'lrs'                => $lrDetails,
            'date'               => \Carbon\Carbon::parse($payment->date)->format('d-m-Y'),
            'type'               => 'Unlinked',
          ];
        }
      })->filter(); // remove nulls
    }


    // --- Step 3: Return view ---
    return view('reports.pump', compact('pumpData', 'startDate', 'endDate', 'pumpId', 'pumps'));
  }





  public function pump_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $pumpId = $request->input('pump_id');

    $export = Excel::download(new PumpExport($startDate, $endDate, $pumpId), 'pump_report.xlsx');
    return $export;
  }

  public function memo_copy(Request $request)
  {
    $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
    $customerId = $request->input('customer_id') ?? null;
    $memo_id = $request->input('memo_id') ?? null;

    $memo_ids = [];
    $lrs = [];
    if ($customerId) {
      $memo_ids = LorryReceipt::where('customer_id', $customerId)
        ->whereNotNull('memo_id')
        ->pluck('memo_id')->unique();
      $lrs = LorryReceipt::where('memo_id', $memo_id)->get();
    }
    return view('reports.memo-copy', compact('customers', 'customerId', 'memo_id', 'memo_ids', 'lrs'));
  }

  // public function memo_copy_export(Request $request)
  // {
  //   if (!$request->input('memo_id')) {
  //     return redirect()->back()->with('error', 'Please select a Memo to export.');
  //   }
  //   $memo_id = $request->input('memo_id');

  //   try {
  //     $export = Excel::download(new MemoCopyExport($memo_id), 'memo_copy.xlsx');
  //   } catch (\Exception $e) {
  //     dd($e);
  //   }

  //   return $export;
  // }
 
  public function memo_copy_export(Request $request)
{
    if (!$request->input('memo_id')) {
        return redirect()->back()->with('error', 'Please select a Memo to export.');
    }
    
    $memo_id = $request->input('memo_id');
    $exportType = $request->input('type', 'excel'); // Default to excel for backward compatibility

    try {
        if ($exportType === 'pdf') {
            $lrs = LorryReceipt::where('memo_id', $memo_id)->get();
            
            if ($lrs->isEmpty()) {
                return redirect()->back()->with('error', 'No data found for the selected memo.');
            }

            // Generate PDF
            $html = view('reports.exports.memo-copy-pdf', compact('lrs', 'memo_id'))->render();
            $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');
            return $pdf->stream("memo_copy_{$memo_id}.pdf");
        } else {
            // Existing Excel export
            return Excel::download(new MemoCopyExport($memo_id), 'memo_copy.xlsx');
        }
    } catch (\Exception $e) {
        \Log::error('Error exporting memo copy: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error generating export: ' . $e->getMessage());
    }
}
  public function bank(Request $request)
  {
    $companyId = Auth::user()->company_id;
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    if ($request->input('end_date')) {
      $query = VoucherPayment::whereBetween('date', [$startDate, $endDate]);
      $query->where('company_id', $companyId);
      $query->whereNull('linked_voucher_id');
      $query->where('payment_mode', 'bank transfer');
      $query->where('payment_status', 'in-progress');
      $payments = $query->get();

      $query1 = VoucherLinked::whereBetween('date', [$startDate, $endDate]);
      $query1->where('company_id', $companyId);
      $query1->where('payment_mode', 'bank transfer');
      $query1->where('payment_status', 'in-progress');
      $linked_payments = $query1->get();
    }
    $allPayments = [];
    if (isset($payments)) {
      foreach ($payments as $payment) {
        $tds_deducted = $payment->freight_booking->tds_amount == 0 ? 'No' : 'Yes';
        $allPayments[] = [
          'date' => $payment->date,
          'lr_number' => $payment->freight_booking->lr->lr_number ?? 'N/A',
          'gr_number' => $payment->freight_booking->lr->gr_number ?? 'N/A',
          'truck_number' => $payment->freight_booking->lr->truck->truck_number ?? 'N/A',
          'transporter_name' => $payment->freight_booking->lr->transporter->name ?? 'N/A',
          'name' => $payment->freight_booking->lr->transporter->short_name ?? 'N/A',
          'beneficiary_id' => $payment->freight_booking->lr->transporter->beneficiary_id ?? 'N/A',
          'amount' => $payment['amount'],
          'tds_deducted' => $tds_deducted,
        ];
      }
    }
    if (isset($linked_payments)) {
      foreach ($linked_payments as $linked_payment) {
        $tds_deducted = $linked_payment->freight_booking->tds_amount == 0 ? 'No' : 'Yes';
        $allPayments[] = [
          'date' => $linked_payment->date,
          'lr_number' => $linked_payment->freight_booking->lr->lr_number ?? 'N/A',
          'gr_number' => $linked_payment->freight_booking->lr->gr_number ?? 'N/A',
          'truck_number' => $linked_payment->freight_booking->lr->truck->truck_number ?? 'N/A',
          'transporter_name' => $linked_payment->freight_booking->lr->transporter->name ?? 'N/A',
          'name' => $linked_payment->freight_booking->lr->transporter->short_name ?? 'N/A',
          'beneficiary_id' => $linked_payment->freight_booking->lr->transporter->beneficiary_id ?? 'N/A',
          'amount' => $linked_payment['amount'],
          'tds_deducted' => $tds_deducted,
        ];
      }
      $allPayments = collect($allPayments)->sortBy('date')->values();
    }

    return view('reports.bank', compact('startDate', 'endDate', 'allPayments'));
  }

  public function bank_export(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $export = Excel::download(new BankExport($startDate, $endDate), 'bank_report.xlsx');
    return $export;
  }

  public function voucher_receipt_export(Request $request)
  {
    if (!$request->input('lr_id')) {
      return redirect()->back()->with('error', 'Please select a Lorry Receipt to export.');
    }
    $lr = LorryReceipt::find($request->input('lr_id'));
    if ($lr->linked_lr_id) {
      $lrs = LorryReceipt::where('linked_lr_id', $lr->linked_lr_id)->get();
    } else {
      $lrs = collect([$lr]);
    }

    // Set the font configuration for the PDF
    $html = view('reports.exports.voucher-receipt', compact('lrs'))->render();

    // Render with mPDF for proper Devanagari shaping
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
      'mode' => 'utf-8',
      'format' => 'A4',
      'orientation' => 'P',
      'fontDir' => array_merge($fontDirs, [storage_path('fonts')]),
      'fontdata' => $fontData + [
        'notosansdevanagari' => [
          'R' => 'NotoSansDevanagari-Regular.ttf',
          'B' => 'NotoSansDevanagari-Bold.ttf',
        ],
      ],
      'default_font' => 'notosansdevanagari',
      'useOTL' => 0xFF, // Enable OpenType layout (Indic shaping)
      'useKerning' => true,
      'autoScriptToLang' => true,
      'autoLangToFont' => true,
    ]);

    $mpdf->WriteHTML($html);
    return $mpdf->Output('voucher_receipt.pdf', 'I');
  }

  public function party_bill(Request $request)
  {
    $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
    $customerId = $request->input('customer_id') ?? null;
    $id = $request->input('invoice_number') ?? null;
    $invoices = [];
    $invoice = null;
    if ($customerId) {
      $invoices = Invoice::where('customer_id', $customerId)->get();
      $invoice = Invoice::find($id);
    }
    return view('reports.party-bill', compact('customers', 'customerId', 'id', 'invoices', 'invoice',));
  }

  // public function party_bill_export(Request $request)
  // {
  //   if (!$request->input('invoice_number')) {
  //     return redirect()->back()->with('error', 'Please select an Invoice to export.');
  //   }
  //   $id = $request->input('invoice_number');
  //   $export = Excel::download(new PartyBillExport($id), 'party_bill.xlsx');
  //   return $export;
  // }
  public function party_bill_export(Request $request)
  {
      if (!$request->input('invoice_number')) {
          return redirect()->back()->with('error', 'Please select an Invoice to export.');
      }
      
      $id = $request->input('invoice_number');
      $exportType = $request->input('type', 'excel');
  
      try {
          if ($exportType === 'pdf') {
              // Load the invoice with necessary relationships
              $invoice = Invoice::with([
                  'consignee.city',
                  'consignee.state',
                  'company.city',
                  'company.state'
              ])->findOrFail($id);
  
              // Load LorryReceipts separately since there's no direct relationship
              $lrs = LorryReceipt::where('invoice_number', $invoice->invoice_number)
              ->with(['consignor', 'consignee'])
              ->get();
          
              
              if ($lrs->isEmpty()) {
                  return redirect()->back()->with('error', 'No Lorry Receipts found for this invoice.');
              }
  
              // Calculate any additional data needed for the view
              $totalAmount = $lrs->sum('total_amount');
              $cgst = $totalAmount * 0.025; // 2.5% CGST
              $sgst = $totalAmount * 0.025; // 2.5% SGST
              $igst = $totalAmount * 0.05;  // 5% IGST (if applicable)
  
              // Generate PDF
              $html = view('reports.exports.party-bill-pdf', compact(
                  'invoice', 
                  'lrs', 
                  'totalAmount',
                  'cgst',
                  'sgst',
                  'igst'
              ))->render();
  
              $pdf = \PDF::loadHTML($html)
                  ->setPaper('a4', 'portrait')
                  ->setOption('isHtml5ParserEnabled', true)
                  ->setOption('isRemoteEnabled', true);
  
              return $pdf->stream("party_bill_{$invoice->invoice_number}.pdf");
          } else {
              // Existing Excel export
              return Excel::download(new PartyBillExport($id), 'party_bill.xlsx');
          }
      } catch (\Exception $e) {
          \Log::error('Error in party_bill_export:', [
              'error' => $e->getMessage(),
              'trace' => $e->getTraceAsString(),
              'invoice_id' => $id,
              'type' => $exportType ?? 'unknown'
          ]);
          
          return redirect()->back()
              ->with('error', 'Error generating export: ' . $e->getMessage());
      }
  }

  public function ultratech_bill(Request $request)
  {
    $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
    $customerId = $request->input('customer_id') ?? null;
    $id = $request->input('invoice_number') ?? null;
    $invoices = [];
    $invoice = null;
    if ($customerId) {
      $invoices = Invoice::where('customer_id', $customerId)->get();
      $invoice = Invoice::find($id);
    }
    return view('reports.ultratech-bill', compact('customers', 'customerId', 'id', 'invoices', 'invoice',));
  }

  public function ultratech_bill_export(Request $request)
  {
    if (!$request->input('invoice_number')) {
      return redirect()->back()->with('error', 'Please select an Invoice to export.');
    }
    $id = $request->input('invoice_number');
    $export = Excel::download(new UltratechBillExport($id), 'ultratech_bill.xlsx');
    return $export;
  }
}
