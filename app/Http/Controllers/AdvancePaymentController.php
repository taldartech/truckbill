<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
use App\Models\AdvancePayment;
use Illuminate\Http\Request;

class AdvancePaymentController extends Controller
{
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

        // Handle AJAX request
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Advance Payment created successfully.',
                'data' => $advancePayment
            ]);
        }

        return redirect()->route('advance-payments.index')
            ->with('success', 'Advance Payment created successfully.');
    }
}

