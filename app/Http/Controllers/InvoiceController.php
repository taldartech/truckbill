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

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['company', 'customer'])->orderByDesc('id')->paginate(20);
        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $companies = Company::all();
        $customers = Customer::all();
        
        // Get memo_ids directly from database
        $memoIds = DB::table('lorry_receipts')
            ->select('memo_id')
            ->distinct()
            ->pluck('memo_id')
            ->toArray();
        
        // Format as array for view
        $memos = array_map(function($id) {
            return [
                'id' => $id,
                'memo_number' => 'Memo #' . $id  // You might want to adjust this based on your actual memo_number format
            ];
        }, $memoIds);
        
        return view('invoices.create', compact('companies', 'customers', 'memos'));
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
                    'memo_id' => $memoId,
                ]);
            }
        }
        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function edit(Invoice $invoice)
    {
        $companies = Company::all();
        $customers = Customer::all();
        $memos = Memo::all();
        $selectedMemos = $invoice->memos->pluck('id')->toArray();
        return view('invoices.edit', compact('invoice', 'companies', 'customers', 'memos', 'selectedMemos'));
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
            'memo_ids.*' => 'exists:memos,id',
        ]);
        $invoice->update($data);
        // Sync memos
        InvoiceMemoMap::where('invoice_id', $invoice->id)->delete();
        if (!empty($data['memo_ids'])) {
            foreach ($data['memo_ids'] as $memoId) {
                InvoiceMemoMap::create([
                    'invoice_id' => $invoice->id,
                    'memo_id' => $memoId,
                ]);
            }
        }
        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }
}
