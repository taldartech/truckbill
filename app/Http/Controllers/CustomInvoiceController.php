<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\CustomInvoiceDetail;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomInvoiceController extends Controller
{
    public function create()
    {
        $user = auth()->user();
        $company = null;
        $gstSlab = 5;

        if ($user && $user->company_id) {
            $company = Company::find($user->company_id);
            $gstSlab = (int) ($company->gst_slab ?? 5);
        }

        return view('custom_invoices.create', compact('company', 'gstSlab'));
    }

    public function lrByNumber(Request $request)
    {
        $request->validate([
            'lr_number' => 'required|string',
        ]);

        $lrNumber = $request->input('lr_number');
        $companyId = auth()->user()->company_id;

        $lr = LorryReceipt::where('company_id', $companyId)
            ->where('lr_number', $lrNumber)
            ->with(['customer', 'truck', 'freightBookings'])
            ->first();

        if (! $lr) {
            return response()->json([
                'success' => false,
                'message' => 'LR number not found',
            ], 404);
        }

        $customer = $lr->customer;
        $freightBooking = $lr->freightBookings->first();

        if ($customer && ! $customer->relationLoaded('state')) {
            $customer->load('state');
        }

        $customerState = $customer ? $customer->state : null;
        $data = [
            'success' => true,
            'lr_id' => $lr->id,
            'lr_number' => $lr->lr_number,
            'customer' => [
                'id' => $customer ? $customer->id : null,
                'name' => $customer ? $customer->name : '',
                'address' => $customer ? $customer->address : '',
                'gst_number' => $customer ? $customer->gst_number : '',
                'pan_number' => $customer ? $customer->pan_number : '',
                'state_code' => $customerState ? $customerState->code : '',
                'state_name' => $customerState ? strtolower($customerState->name) : '',
            ],
            'lr_details' => [
                'truck_number' => $lr->truck ? $lr->truck->truck_number : '',
                'destination_city' => $lr->destination_city ?? '',
                'weight' => $lr->weight ?? 0,
                'invoice_number' => $lr->invoice_number ?? '',
                'gr_date' => $lr->gr_date ? Carbon::parse($lr->gr_date)->format('d/m/Y') : '',
                'memo_date' => $lr->memo_id ?? '',
            ],
        ];

        if ($freightBooking) {
            $data['lr_details']['damage_qty'] = $freightBooking->damage_qty ?? null;
            $data['lr_details']['damage_rate'] = $freightBooking->damage_rate ?? null;
            $data['lr_details']['shortage_qty'] = $freightBooking->shortage_qty ?? null;
            $data['lr_details']['shortage_rate'] = $freightBooking->shortage_rate ?? null;
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'custom_invoice' => 'required|in:rate_difference,shortage_damage',
                'manual_invoice_number' => [
                    'required',
                    'string',
                    Rule::unique('invoices')->where(fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    ),
                ],
                'invoice_type' => 'required|in:Reverse,Forward',
                'generated_date' => [
                    'required',
                    'date',
                    'after_or_equal:' . Carbon::today()->subMonth()->toDateString(),
                    'before_or_equal:' . Carbon::today()->addMonth()->toDateString(),
                ],
                'customer_id' => 'required|exists:consignees,id',
                'total_amount' => 'required|numeric|min:0',
                'net_amount' => 'nullable|numeric|min:0',
                'sgst' => 'nullable|numeric|min:0',
                'cgst' => 'nullable|numeric|min:0',
                'igst' => 'nullable|numeric|min:0',
                'lr_number' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'remark' => 'nullable|string',
            ]);

            if ($data['custom_invoice'] === 'rate_difference') {
                $request->validate([
                    'portal_rate' => 'nullable|numeric|min:0',
                    'diff' => 'nullable|numeric',
                ]);
            } else {
                $request->validate([
                    'damage_qty' => 'nullable|numeric|min:0',
                    'damage_rate' => 'nullable|numeric|min:0',
                    'shortage_qty' => 'nullable|numeric|min:0',
                ]);
            }

            $data['payment_status'] = 'pending';
            $data['company_id'] = auth()->user()->company_id;

            $lrNumber = $request->input('lr_number');
            $lr = LorryReceipt::where('company_id', auth()->user()->company_id)
                ->where('lr_number', $lrNumber)
                ->first();

            if (! $lr) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['lr_number' => "LR number {$lrNumber} not found"]);
            }

            $data['lr_ids'] = [$lr->id];

            $company = Company::find(auth()->user()->company_id);
            $customer = \App\Models\Consignee::find($data['customer_id']);
            $gstRate = ((int) ($company->gst_slab ?? 5)) / 100;
            $baseAmount = $data['total_amount'];

            $companyState = $company->state ? strtolower($company->state->name) : '';
            $customerState = $customer && $customer->state ? strtolower($customer->state->name) : '';

            $sgst = 0;
            $cgst = 0;
            $igst = 0;
            $netAmount = $baseAmount;

            if ($companyState === $customerState && $companyState !== '') {
                if ($data['invoice_type'] === 'Forward') {
                    $sgst = $baseAmount * ($gstRate / 2);
                    $cgst = $baseAmount * ($gstRate / 2);
                    $netAmount = $baseAmount + $sgst + $cgst;
                } else {
                    $sgst = $baseAmount * ($gstRate / 2);
                    $cgst = $baseAmount * ($gstRate / 2);
                    $netAmount = $baseAmount;
                }
            } else {
                if ($data['invoice_type'] === 'Forward') {
                    $igst = $baseAmount * $gstRate;
                    $netAmount = $baseAmount + $igst;
                } else {
                    $igst = $baseAmount * $gstRate;
                    $netAmount = $baseAmount;
                }
            }

            $data['base_amount'] = round($baseAmount, 2);
            $data['sgst'] = round($sgst, 2);
            $data['cgst'] = round($cgst, 2);
            $data['igst'] = round($igst, 2);
            $data['net_amount'] = round($netAmount, 2);

            if ($data['invoice_type'] === 'Forward') {
                $data['total_amount'] = round($netAmount, 2);
            } else {
                $data['total_amount'] = round($baseAmount + $sgst + $cgst + $igst, 2);
            }

            $invoice = Invoice::create($data);

            $detailData = [
                'invoice_id' => $invoice->id,
                'lr_id' => $lr->id,
                'amount' => $request->input('amount'),
                'remark' => $request->input('remark') ?? null,
                'sort_order' => 1,
            ];

            if ($data['custom_invoice'] === 'rate_difference') {
                $detailData['portal_rate'] = $request->input('portal_rate') ?? null;
                $detailData['diff'] = $request->input('diff') ?? null;
            } else {
                $detailData['damage_qty'] = $request->input('damage_qty') ?? null;
                $detailData['damage_rate'] = $request->input('damage_rate') ?? null;
                $detailData['shortage_qty'] = $request->input('shortage_qty') ?? null;
            }

            CustomInvoiceDetail::create($detailData);

            return redirect()->route('invoices.index')->with('success', 'Custom invoice created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Custom invoice store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['save' => 'Failed to save custom invoice. Please try again. Error: ' . $e->getMessage()]);
        }
    }
}
