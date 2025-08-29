<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
use App\Models\AdvancePayment;
use Illuminate\Http\Request;

class AdvancePaymentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getAdvancePaymentsData($request);
        }

        return view('advance-payments.index');
    }

    private function getAdvancePaymentsData(Request $request)
    {
        $query = AdvancePayment::with('transporter')
            ->where('company_id', auth()->user()->company_id);


        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }

        // Apply search filters
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1:
                            $query->whereHas('transporter', fn($q) => $q->where('name', 'like', "%$searchValue%"));
                            break;
                        case 2:
                            $query->where('advance_amount', 'like', "%$searchValue%");
                            break;
                        case 3:
                            $query->where('payment_date', 'like', "%$searchValue%");
                            break;
                    }
                }
            }
        }

        // Sorting
        if ($request->has('order')) {
            $order = $request->order[0];
            switch ($order['column']) {
                case 0:
                    $query->orderBy('id', $order['dir']);
                    break;
                case 1:
                    $query->join('transporters', 'advance_payments.transporter_id', '=', 'transporters.id')
                        ->orderBy('transporters.name', $order['dir'])
                        ->select('advance_payments.*');
                    break;
                case 2:
                    $query->orderBy('advance_amount', $order['dir']);
                    break;
                case 3:
                    $query->orderBy('payment_date', $order['dir']);
                    break;
            }
        }

        $total = AdvancePayment::where('company_id', auth()->user()->company_id)->count();
        $filtered = $query->count();

        $data = $query->skip($request->start)->take($request->length)->get();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data->map(function ($item) {
                return [
                    'id' => $item->id,
                    'transporter' => $item->transporter->name ?? '-',
                    'advance_amount' => number_format($item->advance_amount, 2),
                    'payment_date' => $item->payment_date,
                    'actions' => $this->getActionButtons($item),
                ];
            }),
        ]);
    }

    private function getActionButtons($payment)
    {
        return view('advance-payments.partials.actions', compact('payment'))->render();
    }

    public function create($transporter_id)
    {
        $transporter = Transporter::findOrFail($transporter_id);
        $preFilled = [
            'transporter_id' => $transporter->id,
            'transporter_name' => $transporter->name
        ];
        return view('advance_payments.create', compact('preFilled'));
    }

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

    public function edit(AdvancePayment $advance_payment)
    {
        $transporters = Transporter::where('company_id', auth()->user()->company_id)->get();
        return view('advance-payments.edit', compact('advance_payment', 'transporters'));
    }

    public function update(Request $request, AdvancePayment $advance_payment)
    {
        $validated = $request->validate([
            'transporter_id' => 'required|exists:transporters,id',
            'advance_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
        ]);

        $advance_payment->update($validated);

        return redirect()->route('advance-payments.index')
            ->with('success', 'Advance Payment updated successfully.');
    }

    public function destroy($id)
    {
        $advance_payment = AdvancePayment::findOrFail($id);
        $advance_payment->delete();

        return redirect()->route('advance-payments.index')->with('success', 'Advance Payment deleted successfully.');
    }
}

