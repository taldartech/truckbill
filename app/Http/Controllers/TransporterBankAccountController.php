<?php

namespace App\Http\Controllers;

use App\Models\TransporterBankAccount;
use App\Models\Transporter;
use Illuminate\Http\Request;

class TransporterBankAccountController extends Controller
{
    public function index()
    {
        $transporters = Transporter::all();
        $accounts = TransporterBankAccount::with('transporter')->paginate(10);
        return view('transporter_bank_accounts.index', compact('accounts', 'transporters'));
    }

    public function create()
    {
        $transporters = Transporter::all();
        return view('transporter_bank_accounts.create', compact('transporters'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'ifsc_code' => 'required|string|max:11',
            'beneficiary_name' => 'required|string|max:255',
            'bulk_payment_id' => 'nullable|string|max:255',
        ]);

        TransporterBankAccount::create($validated);
        return redirect()->route('transporter_bank_accounts.index')->with('success', 'Bank account added successfully!');
    }

    public function edit(TransporterBankAccount $account)
    {
        $transporters = Transporter::all();
        return view('transporter_bank_accounts.edit', compact('account', 'transporters'));
    }

    public function update(Request $request, TransporterBankAccount $account)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'ifsc_code' => 'required|string|max:11',
            'beneficiary_name' => 'required|string|max:255',
            'bulk_payment_id' => 'nullable|string|max:255',
        ]);

        $account->update($validated);
        return redirect()->route('transporter_bank_accounts.index')->with('success', 'Bank account updated successfully!');
    }

    public function destroy(TransporterBankAccount $account)
    {
        $account->delete();
        return redirect()->route('transporter_bank_accounts.index')->with('success', 'Bank account deleted successfully!');
    }
}
