<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Memo;
use App\Models\InvoiceMemoMap;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['company', 'customer'])->orderByDesc('id')->paginate(20);
        return view('invoices.index', compact('invoices'));
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
    $companies = Company::all();
    $customers = Customer::all();
    return view('invoices.create', compact('companies', 'customers'));
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
        ->where('customer_id', $customerId)
        ->whereNotNull('memo_id')
        ->distinct()
        ->get()
        ->map(function($memo) {
            return [
                'id' => $memo->memo_id,
                'memo_number' => 'Memo #' . $memo->memo_id,
                'selected' => request()->old('memo_ids') && in_array($memo->memo_id, request()->old('memo_ids'))
            ];
        });

    Log::info('Found memos for customer:', ['count' => count($memos)]);
    
    return response()->json($memos);
}



    public function store(Request $request)
    {
        $data = $request->validate([
            'manual_invoice_number' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
            'invoice_type' => 'required|in:Reverse,Forward',
            'generated_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,partial,paid',
            'memo_ids' => 'nullable|array',
            'memo_ids.*' => 'exists:memos,id',
        ]);
        $invoice = Invoice::create($data);
        if (!empty($data['memo_ids'])) {
            foreach ($data['memo_ids'] as $memoId) {
                InvoiceMemoMap::create([
                    'invoice_id' => $invoice->id,
                    'memo_id' => $memoId
                ]);
            }
        }
        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['company', 'customer']);
        // Get memos directly from lorry_receipts using the invoice's customer_id
        $memos = DB::table('lorry_receipts')
            ->select('memo_id', 'lr_number', 'source_city as from_location', 'destination_city as to_location', 'fr_rate as rate', 'amount')
            ->where('customer_id', $invoice->customer_id)
            ->whereNotNull('memo_id')
            ->get();
        
        return view('invoices.show', compact('invoice', 'memos'));
    }

    public function edit(Invoice $invoice)
    {
        $companies = Company::all();
        $customers = Customer::all();
        $memos = DB::table('lorry_receipts')
            ->select('memo_id')
            ->where('customer_id', $invoice->customer_id)
            ->whereNotNull('memo_id')
            ->distinct()
            ->get()
            ->map(function($memo) use ($invoice) {
                $selected = false;
                if (request()->old('memo_ids')) {
                    $selected = in_array($memo->memo_id, request()->old('memo_ids'));
                } else {
                    // Get memo_ids from the invoice's memoMaps relationship
                    $selected = $invoice->memoMaps->pluck('memo_id')->contains($memo->memo_id);
                }
                return (object)[
                    'memo_id' => $memo->memo_id,
                    'memo_number' => 'Memo #' . $memo->memo_id,
                    'selected' => $selected
                ];
            });

        return view('invoices.edit', compact('invoice', 'companies', 'customers', 'memos'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'manual_invoice_number' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
            'invoice_type' => 'required|in:Reverse,Forward',
            'generated_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,partial,paid',
            'memo_ids' => 'nullable|array',
            'memo_ids.*' => 'exists:lorry_receipts,memo_id',
        ]);
        
        // Handle memo_ids separately since it's not a direct column
        $memoIds = $data['memo_ids'] ?? [];
        unset($data['memo_ids']);
        
        $invoice->update($data);
        
        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }
}
