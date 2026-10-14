<?php

namespace App\Http\Controllers;

use App\Services\TransporterAdvanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvancePaymentController extends Controller
{
    public function __construct(
        private readonly TransporterAdvanceLedgerService $transporterAdvanceLedgerService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'advance_amount' => 'required|numeric',
            'payment_date' => 'required|date',
            'remark' => 'nullable|string|max:1000',
        ]);

        $companyId = (int) (Auth::user()->userRole?->name === 'super_admin' && session('super_admin_company_id')
            ? session('super_admin_company_id')
            : (Auth::user()->company_id ?? \App\Models\Transporter::where('id', $validated['transporter_id'])->value('company_id')));

        $advancePayment = $this->transporterAdvanceLedgerService->recordAdvancePayment(
            companyId: $companyId,
            transporterId: (int) $validated['transporter_id'],
            advanceAmount: (float) $validated['advance_amount'],
            paymentDate: $validated['payment_date'],
            remark: $validated['remark'] ?? null,
            actor: auth()->user(),
            notifyCredit: true
        );

        // Handle AJAX request
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Advance Payment created successfully.',
                'data' => $advancePayment,
            ]);
        }

        return redirect()->route('advance-payments.index')
            ->with('success', 'Advance Payment created successfully.');
    }
}
