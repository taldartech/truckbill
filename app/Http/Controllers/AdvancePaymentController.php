<?php

namespace App\Http\Controllers;

use App\Models\AdvancePayment;
use App\Models\Transporter;
use App\Services\Notifications\AdvancePaymentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdvancePaymentController extends Controller
{
    public function __construct(
        private readonly AdvancePaymentNotificationService $advancePaymentNotificationService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'advance_amount' => 'required|numeric',
            'payment_date' => 'required|date',
            'remark' => 'nullable|string|max:1000',
        ]);

        $validated['company_id'] = Auth::user()->company_id;

        $transporter = Transporter::findOrFail($validated['transporter_id']);
        $newBalance = (float) $transporter->advance_amount + (float) $validated['advance_amount'];

        if ($newBalance < 0) {
            throw ValidationException::withMessages([
                'advance_amount' => 'Deduction exceeds available book balance of ₹'.number_format((float) $transporter->advance_amount, 2).'.',
            ]);
        }

        $advancePayment = AdvancePayment::create($validated);

        // Update transporter's advance amount
        $transporter->advance_amount = $newBalance;
        $transporter->save();

        if ((float) $validated['advance_amount'] > 0) {
            $advancePayment->loadMissing(['company', 'transporter.transporterMaster']);
            $this->advancePaymentNotificationService->notifyAdvancePaid($advancePayment, auth()->user());
        }

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
