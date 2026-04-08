<?php

namespace App\Http\Controllers;

use App\Models\AdvancePayment;
use App\Models\Transporter;
use App\Services\Notifications\AdvancePaymentNotificationService;
use Illuminate\Http\Request;

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

        $validated['company_id'] = auth()->user()->company_id;

        $advancePayment = AdvancePayment::create($validated);

        // Update transporter's advance amount
        $transporter = Transporter::find($validated['transporter_id']);
        $transporter->advance_amount += $validated['advance_amount'];
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
