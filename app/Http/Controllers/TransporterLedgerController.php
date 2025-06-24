<?php

namespace App\Http\Controllers;

use App\Models\TransporterLedger;
use App\Models\Transporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransporterLedgerController extends Controller
{
    public function index()
    {
        $ledgers = TransporterLedger::with('transporter')
            ->latest()
            ->paginate(10);
        $transporters = Transporter::all();
        return view('transporter_ledger.index', compact('ledgers', 'transporters'));
    }

    public function create()
    {
        $transporters = Transporter::all();
        return view('transporter_ledger.create', compact('transporters'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'amount' => 'required|numeric|min:0',
            'transaction_type' => 'required|in:debit,credit',
            'remark' => 'nullable|string',
        ]);

        TransporterLedger::create($validated);
        return redirect()->route('transporter_ledger.index')->with('success', 'Ledger entry added successfully!');
    }

    public function edit(TransporterLedger $ledger)
    {
        $transporters = Transporter::all();
        return view('transporter_ledger.edit', compact('ledger', 'transporters'));
    }

    public function update(Request $request, TransporterLedger $ledger)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'amount' => 'required|numeric|min:0',
            'transaction_type' => 'required|in:debit,credit',
            'remark' => 'nullable|string',
        ]);

        $ledger->update($validated);
        return redirect()->route('transporter_ledger.index')->with('success', 'Ledger entry updated successfully!');
    }

    public function destroy(TransporterLedger $ledger)
    {
        $ledger->delete();
        return redirect()->route('transporter_ledger.index')->with('success', 'Ledger entry deleted successfully!');
    }

    public function transporterBalance($transporterId)
    {
        $transporter = Transporter::findOrFail($transporterId);
        $balance = $transporter->ledgers()
            ->select(
                DB::raw('SUM(CASE WHEN transaction_type = "debit" THEN amount ELSE -amount END) as balance')
            )
            ->value('balance');
        
        return response()->json([
            'balance' => $balance,
            'formatted_balance' => number_format($balance, 2)
        ]);
    }
}
