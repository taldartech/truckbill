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
}
