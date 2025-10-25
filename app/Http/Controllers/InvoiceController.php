<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Memo;
use App\Models\InvoiceMemoMap;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
  public function index(Request $request)
  {
    // Handle DataTables AJAX request
    if ($request->ajax()) {
      return $this->getInvoicesData($request);
    }

    $invoices = Invoice::with(['company', 'consignee'])->orderByDesc('id');

    if (auth()->user()->userRole->name !== 'super_admin') {
      $invoices = $invoices->where('company_id', auth()->user()->company_id);
    }
    $invoices = $invoices->paginate(20);
    return view('invoices.index', compact('invoices'));
  }

  public function getInvoicesData(Request $request)
  {
    try {
      $query = Invoice::with(['company', 'consignee'])
        ->leftJoin('companies', 'invoices.company_id', '=', 'companies.id')
        ->leftJoin('consignees', 'invoices.consignee_id', '=', 'consignees.id')
        ->select('invoices.*');

      // Only apply company_id filter for non-super_admin users
      if (auth()->user()->userRole->name !== 'super_admin') {
        $query->where('invoices.company_id', auth()->user()->company_id);
      }

      return DataTables::of($query)
        ->addColumn('company_name', function ($row) {
          return $row->company ? $row->company->name : '-';
        })
        ->addColumn('consignee_name', function ($row) {
          return $row->consignee ? $row->consignee->name : '-';
        })
        ->addColumn('generated_date_formatted', function ($row) {
          return $row->generated_date ? date('d-m-Y', strtotime($row->generated_date)) : '-';
        })
        ->addColumn('total_amount_formatted', function ($row) {
          return $row->total_amount ? '₹' . number_format($row->total_amount, 2) : '₹0.00';
        })
        ->addColumn('payment_status_badge', function ($row) {
          $statusClass = match ($row->payment_status) {
            'paid' => 'success',
            'pending' => 'warning',
            'overdue' => 'danger',
            default => 'secondary'
          };
          $statusText = ucfirst($row->payment_status ?? 'Unknown');
          return '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>';
        })
        ->addColumn('invoice_type_badge', function ($row) {
          $typeClass = match ($row->invoice_type) {
            'manual' => 'primary',
            'auto' => 'info',
            default => 'secondary'
          };
          $typeText = ucfirst($row->invoice_type ?? 'Unknown');
          return '<span class="badge bg-' . $typeClass . '">' . $typeText . '</span>';
        })
        ->addColumn('actions', function ($row) {
          return $this->getActionButtons($row);
        })
        ->filterColumn('company_name', function ($query, $keyword) {
          $query->whereHas('company', function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%");
          });
        })
        ->filterColumn('consignee_name', function ($query, $keyword) {
          $query->whereHas('consignee', function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%");
          });
        })
        ->orderColumn('company_name', 'companies.name $1')
        ->orderColumn('consignee_name', 'consignees.name $1')
        ->rawColumns(['payment_status_badge', 'invoice_type_badge', 'actions'])
        ->make(true);
    } catch (\Exception $e) {
      \Log::error('DataTables error: ' . $e->getMessage());
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  private function getActionButtons($invoice)
  {
    $actions = '<div class="btn-group" role="group">';

    // View button
    $actions .= '<a href="' . route('invoices.show', $invoice->id) . '" class="btn btn-sm btn-outline-info" title="View">';
    $actions .= '<i class="bi bi-eye"></i>';
    $actions .= '</a>';

    // Edit button
    $actions .= '<a href="' . route('invoices.edit', $invoice->id) . '" class="btn btn-sm btn-outline-primary" title="Edit">';
    $actions .= '<i class="bi bi-pencil"></i>';
    $actions .= '</a>';

    // Print button
    $actions .= '<a href="' . route('invoices.print', $invoice->id) . '" class="btn btn-sm btn-outline-secondary" title="Print" target="_blank">';
    $actions .= '<i class="bi bi-printer"></i>';
    $actions .= '</a>';

    // Delete button
    $actions .= '<form method="POST" action="' . route('invoices.destroy', $invoice->id) . '" style="display: inline;">';
    $actions .= csrf_field();
    $actions .= method_field('DELETE');
    $actions .= '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm(\'Are you sure you want to delete this invoice?\')">';
    $actions .= '<i class="bi bi-trash"></i>';
    $actions .= '</button>';
    $actions .= '</form>';

    $actions .= '</div>';

    return $actions;
  }

  // public function create()
  // {
  //     $companies = Company::all();
  //     $customers = Customer::all();

  //     // Get memo_ids directly from database
  //     $memoIds = DB::table('lorry_receipts')
  //         ->select('memo_id')
  //         ->distinct()
  //         ->pluck('memo_id')
  //         ->toArray();

  //     // Format as array for view
  //     $memos = array_map(function($id) {
  //         return [
  //             'id' => $id,
  //             'memo_number' => 'Memo #' . $id  // You might want to adjust this based on your actual memo_number format
  //         ];
  //     }, $memoIds);

  //     return view('invoices.create', compact('companies', 'customers', 'memos'));
  // }
  public function create()
  {
    $user = auth()->user();
    $company = null;

    if ($user && $user->company_id) {
      $company = Company::find($user->company_id);
    }

    $consignees = Consignee::all()->where('company_id', $user->company_id);

    return view('invoices.create', compact('company', 'consignees'));
  }



  public function getCustomerMemos($customerId)
  {
    // Log the incoming request
    // Log::info('Fetching memos for customer:', ['customer_id' => $customerId]);

    // // Validate the customer ID
    // if (!is_numeric($customerId)) {
    //     Log::warning('Invalid customer ID:', ['customer_id' => $customerId]);
    //     return response()->json(['error' => 'Invalid customer ID'], 400);
    // }

    // Get memos from lorry_receipts
    $memos = DB::table('lorry_receipts')
      ->select('memo_id')
      ->where('company_id', auth()->user()->company_id)
      ->where('customer_id', $customerId)
      ->where('invoice_generated', 0) // ✅ Only those not yet invoiced
      ->whereNotNull('memo_id')
      ->distinct()
      ->get()
      ->map(function ($memo) {
        return [
          'id' => $memo->memo_id,
          'memo_number' => 'Memo #' . $memo->memo_id,
          'selected' => request()->old('memo_ids') && in_array($memo->memo_id, request()->old('memo_ids'))
        ];
      });

    Log::info('Found memos for customer:', ['count' => count($memos)]);

    return response()->json($memos);
  }

  function getAllCustomerMemos($customerId)
  {
    $memos = LorryReceipt::where('company_id', auth()->user()->company_id)
      ->where('customer_id', $customerId)
      ->whereNotNull('memo_id')
      ->distinct()
      ->pluck('memo_id');
    return response()->json($memos);
  }


  public function store(Request $request)
  {
    //   dd($request->all());
    $data = $request->validate([
      'manual_invoice_number' => 'nullable|string',
      'invoice_type' => 'required|in:Reverse,Forward',
      'generated_date' => 'required|date',
      'customer_id' => 'required|exists:consignees,id',
      'total_amount' => 'required|numeric|min:0',


      'memo_ids.*' => 'exists:lorry_receipts,memo_id',
      'net_amount' => 'nullable|numeric|min:0',
      'sgst' => 'nullable|numeric|min:0',
      'cgst' => 'nullable|numeric|min:0',
      'igst' => 'nullable|numeric|min:0',
    ]);
    $data['payment_status'] = 'pending';
    $data['company_id'] = auth()->user()->company_id;

    // // 🔹 Fetch customer state
    // $customer = \App\Models\Consignee::findOrFail($data['customer_id']);
    // $state = strtolower($customer->state);

    // $grandTotal = $data['total_amount'];   // this is the form’s base total
    // $invoiceType = strtolower($data['invoice_type']);

    // // 🔹 Recalculate expected tax values
    // $expected = [
    //     'sgst' => 0,
    //     'cgst' => 0,
    //     'igst' => 0,
    //     'net_amount' => $grandTotal,
    //     'total_amount' => $grandTotal, // also track base amount
    // ];

    // if ($state === 'rajasthan') {
    //     if ($invoiceType === 'forward') {
    //         $expected['sgst'] = $grandTotal * 0.025;
    //         $expected['cgst'] = $grandTotal * 0.025;
    //         $expected['net_amount'] = $grandTotal + $expected['sgst'] + $expected['cgst'];
    //         $expected['total_amount'] = $expected['net_amount']; // frontend sets total_amount=net in this case
    //     } else { // Reverse
    //         $expected['sgst'] = $grandTotal * 0.025;
    //         $expected['cgst'] = $grandTotal * 0.025;
    //         $gst = $grandTotal * 0.05;
    //         $expected['net_amount'] = $grandTotal + $gst;
    //         $expected['total_amount'] = $grandTotal; // frontend keeps base amount for reverse
    //     }
    // } else {
    //     if ($invoiceType === 'forward') {
    //         $expected['igst'] = $grandTotal * 0.05;
    //         $expected['net_amount'] = $grandTotal + $expected['igst'];
    //         $expected['total_amount'] = $expected['net_amount'];
    //     } else { // Reverse
    //         $expected['igst'] = $grandTotal * 0.05;
    //         $expected['net_amount'] = $grandTotal + $expected['igst'];
    //         $expected['total_amount'] = $grandTotal;
    //     }
    // }

    // // 🔹 Compare request values with expected (including total_amount)
    // if (
    //     round($data['sgst'], 2) !== round($expected['sgst'], 2) ||
    //     round($data['cgst'], 2) !== round($expected['cgst'], 2) ||
    //     round($data['igst'], 2) !== round($expected['igst'], 2) ||
    //     round($data['net_amount'], 2) !== round($expected['net_amount'], 2) ||
    //     round($data['total_amount'], 2) !== round($expected['total_amount'], 2)
    // ) {
    //     return back()->with('warning', '⚠️ Invoice mismatch detected (Amount/Tax). Please re-check and try again.');
    // }



    // Separate memo_ids from the rest of invoice data
    $memoIds = $request->input('memo_ids', []);
    // unset($data['memo_ids']);

    // ✅ Update lorry_receipts before invoice creation
    if (!empty($memoIds)) {
      \App\Models\LorryReceipt::whereIn('memo_id', $memoIds)
        ->update(['invoice_generated' => 1]);
      $data['lr_ids'] = \App\Models\LorryReceipt::whereIn('memo_id', $memoIds)->pluck('id')->toArray();
    }

    $invoice = Invoice::create($data);
    // if (!empty($data['memo_ids'])) {
    //     foreach ($data['memo_ids'] as $memoId) {
    //         InvoiceMemoMap::create([
    //             'invoice_id' => $invoice->id,
    //             'memo_id' => $memoId
    //         ]);
    //     }
    // }

    // if (!empty($memoIds)) {
    //     foreach ($memoIds as $memoId) {
    //         InvoiceMemoMap::create([
    //             'invoice_id' => $invoice->id,
    //             'memo_id' => $memoId
    //         ]);
    //     }
    // }

    return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
  }

  public function show(Request $request, Invoice $invoice)
  {
    $invoice->load(['company', 'consignee']);
    // Get memos directly from invoice table in invoice tabble their is lr_ids in arrays form fetch lr_ids and then use this lr_ids to fetch 'memo_id', 'lr_number', 'source_city as from_location', 'destination_city as to_location', 'fr_rate as rate', 'amount'
    $lrIds = $invoice->lr_ids;
    $memos = DB::table('lorry_receipts')
      ->select('memo_id', 'lr_number', 'source_city as from_location', 'destination_city as to_location', 'fr_rate as rate', 'amount')
      ->whereIn('id', $lrIds)
      ->whereNotNull('memo_id')
      ->get();

    if ($request->ajax()) {
      return response()->json([
        'id' => $invoice->id,
        'manual_invoice_number' => $invoice->manual_invoice_number,
        'invoice_type' => $invoice->invoice_type,
        'generated_date' => $invoice->generated_date,
        'payment_status' => $invoice->payment_status,
        'total_amount' => $invoice->total_amount,
        'paid_amount' => $invoice->paid_amount,
        'company' => $invoice->company,
        'consignee' => $invoice->consignee,
        'memos' => $memos,
      ]);
    }

    return view('invoices.show', compact('invoice', 'memos'));
  }

  public function edit(Invoice $invoice)
  {
    $companies = Company::all();
    $consignees = Consignee::all();


    // Get all memos for this customer
    $memos = DB::table('lorry_receipts')
      ->select('memo_id')
      ->where('customer_id', $invoice->customer_id)
      ->whereNotNull('memo_id')
      ->distinct()
      ->get()
      ->map(function ($memo) {
        // Only show memos that have an invoice_id (indicating they're used)
        $hasInvoice = DB::table('lorry_receipts')
          ->where('memo_id', $memo->memo_id)
          ->exists();

        return (object)[
          'memo_id' => $memo->memo_id,
          'memo_number' => 'Memo #' . $memo->memo_id,
          'selected' => $hasInvoice
        ];
      });

    return view('invoices.edit', compact('invoice', 'companies', 'consignees', 'memos'));
  }

  public function update(Request $request, Invoice $invoice)
  {
    if ($invoice->payment_status === 'paid') {
      return redirect()->back()->with('error', 'Cannot edit a paid invoice.');
    }
    if ($invoice->payment_status === 'partial') {
      $data = $request->validate([
        'payment_status' => 'required|in:partial,paid',
      ]);
      $invoice->update($data);
      return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }
    $data = $request->validate([
      'manual_invoice_number' => 'nullable|string',
      'generated_date' => 'required|date',
      'payment_status' => 'required|in:pending,partial,paid',
    ]);

    $invoice->update($data);

    return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
  }

  public function destroy(Invoice $invoice)
  {
    if ($invoice->payment_status !== 'pending') {
      return redirect()->back()->with('error', 'Cannot delete invoice.');
    }
    $lr_ids = $invoice->lr_ids ?? [];
    if (!empty($lr_ids)) {
      LorryReceipt::whereIn('id', $lr_ids)
        ->update(['invoice_generated' => 0]);
    }
    $invoice->delete();
    return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
  }

  public function getLrs($memoIds)
  {
    $memoIds = explode(',', $memoIds);
    Log::info('Fetching LR numbers for memos:', ['memo_ids' => $memoIds]);

    $lrs = LorryReceipt::whereIn('memo_id', $memoIds)
      ->with(['transporter', 'consignor', 'consignee', 'truck'])
      ->select('lr_number', 'memo_id', 'source_city', 'destination_city', 'transporter_id', 'consignor_id', 'consignee_id', 'gr_date', 'amount', 'truck_id', 'weight')
      ->where('invoice_generated', 0)
      ->get()
      ->groupBy('memo_id');

    $result = [];
    foreach ($memoIds as $memoId) {
      $result[$memoId] = $lrs->get($memoId)?->map(function ($lr) {
        return [
          'lr_number' => $lr->lr_number,
          'source_city' => $lr->source_city,
          'destination_city' => $lr->destination_city,
          'transporter' => $lr->transporter?->name ?? '',
          'consignor' => $lr->consignor?->name ?? '',
          'consignee' => $lr->consignee?->name ?? '',
          'gr_date' => $lr->gr_date,
          'amount' => $lr->amount,
          'truck_number' => $lr->truck?->truck_number ?? '',
          'weight' => $lr->weight
        ];
      })->toArray() ?? [];
    }

    return response()->json($result);
  }

  public function getInvoiceNumbersForCustomer($customerId)
  {
    $invoices = Invoice::where('customer_id', $customerId)
      ->where('company_id', Auth::user()->company_id)
      ->orderByDesc('id')
      ->get(['id', 'manual_invoice_number']);

    return response()->json($invoices);
  }
}
