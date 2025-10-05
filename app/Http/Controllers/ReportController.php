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
use App\Models\Invoice;
use App\Exports\InvoiceExport;
use App\Models\Consignees;

class ReportController extends Controller
{
  public function consignor_consignee(Request $request)
  {
    $consignees = Consignee::all();
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
      $query = FreightBooking::whereHas('lr', function($query) use ($startDate, $endDate) {
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
            'freightBookings' => function($q) {
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
    $consignees = Consignee::all();
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
  public function invoice(Request $request)
  {
    $endDate = $request->input('end_date') ?? date('Y-m-d');
    $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    $user = Auth::user()->company_id;
    $invoices = [];
    $generated = false;

    if ($request->input('end_date') || $request->input('start_date')) {
      $query = Invoice::with(['consignee', 'consignee.state'])
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

    $export = Excel::download(new InvoiceExport($startDate, $endDate), 'invoice_report.xlsx');
    return $export;
  }
}
