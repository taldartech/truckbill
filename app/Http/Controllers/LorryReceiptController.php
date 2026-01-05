<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\GoodType;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class LorryReceiptController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getLorryReceiptsData($request);
        }

        return view('lorry_receipts.index');
    }

    public function getByLinkedId($linkedId)
    {
        try {

            $lrs = \App\Models\LorryReceipt::with(['truck', 'freightBookings'])
                ->where('company_id', Auth::user()->company_id)
                ->where('linked_lr_id', $linkedId)
                ->get()
                ->map(function ($lr) {
                    $firstFreight = $lr->freightBookings->last();

                    return [
                        'id' => $lr->id,
                        'lr_number' => $lr->lr_number,
                        'truck_number' => $lr->truck ? $lr->truck->truck_number : '',
                        'freight_booking_id' => $lr->freight_booking_id,
                        'balance_amount' => $firstFreight ? (float) $firstFreight->balance_amount : 0,
                    ];
                })->sortBy('balance_amount') // 🔹 Sort ascending by balance
                ->values();

            return response()->json($lrs->values());
        } catch (\Exception $e) {
            Log::error('Error in getByLinkedId: '.$e->getMessage());

            return response()->json([
                'error' => 'An error occurred while fetching linked LRs',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getLorryReceiptsData(Request $request)
    {
        try {
            $query = LorryReceipt::with(['truck', 'transporter', 'consignor', 'consignee', 'company', 'customer']);

            // Only apply company_id filter for non-super_admin users
            if (Auth::user()->userRole->name !== 'super_admin') {
                $query->where('company_id', Auth::user()->company_id);
            }
            $query->orderBy('gr_date', 'desc')->orderBy('linked_lr_id', 'desc');
            $query->when($request->has('gr_date_from') && $request->has('gr_date_to'), function ($query) use ($request) {
                $query->whereBetween('gr_date', [$request->gr_date_from, $request->gr_date_to]);
            });

            return DataTables::of($query)
                ->addColumn('sr_no', function ($row) {
                    static $counter = 0;

                    return ++$counter;
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->customer ? $row->customer->name : '-';
                })
                ->addColumn('consignor_name', function ($row) {
                    return $row->consignor ? $row->consignor->name : '-';
                })
                ->addColumn('consignee_name', function ($row) {
                    return $row->consignee ? $row->consignee->name : '-';
                })
                ->addColumn('truck_number', function ($row) {
                    return $row->truck ? $row->truck->truck_number : '-';
                })
                ->addColumn('gr_date_formatted', function ($row) {
                    return $row->gr_date ? date('d-m-Y', strtotime($row->gr_date)) : '-';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return $row->amount ? '₹'.number_format($row->amount, 2) : '₹0.00';
                })
                ->addColumn('status_badge', function ($row) {
                    if ($row->invoice_generated == 1) {
                        $statusClass = 'success';
                        $statusText = 'Invoiced';
                    } else {
                        $statusClass = match ($row->delivery_status) {
                            'Pending' => 'warning',
                            'Delivered' => 'success',
                            'Cancelled' => 'danger',
                            'In-Progress' => 'info',
                            default => 'secondary',
                        };
                        $statusText = $row->delivery_status ?? 'Unknown';
                    }

                    return '<span class="badge bg-'.$statusClass.'">'.$statusText.'</span>';
                })

                ->addColumn('actions', function ($row) {
                    return $this->getActionButtons($row);
                })
                ->rawColumns(['sr_no', 'lr_number', 'customer_name', 'consignor_name', 'consignee_name', 'truck_number', 'gr_date_formatted', 'amount_formatted', 'status_badge', 'actions', 'row_class'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('DataTables error: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getActionButtons($lorryReceipt)
    {
        $buttons = '<div class="d-flex gap-1 justify-content-center">';

        // Freight booking button
        $freight_booking_id = $lorryReceipt->freight_booking_id;
        $freightBooking = FreightBooking::find($freight_booking_id);
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();

        if ($freight_booking_id) {
            $buttons .= '<a href="'.route('freight_bookings.edit', $freight_booking_id).'" class="btn-action btn-action-warning" title="Edit Freight Booking" data-bs-toggle="tooltip">';
            $buttons .= '<i class="bi bi-truck"></i></a>';
        } else {
            if ($company->lr_credits <= 0) {
                $buttons .= '<button class="btn-action btn-action-muted" title="Insufficient Credits" data-bs-toggle="tooltip" onclick="alert(\'Insufficient credits\')">';
                $buttons .= '<i class="bi bi-truck"></i></button>';
            } elseif ($company->plan_end_date && $today->gt($planEndDate)) {
                $buttons .= '<button class="btn-action btn-action-muted" title="Plan Expired" data-bs-toggle="tooltip" onclick="alert(\'Plan expired\')">';
                $buttons .= '<i class="bi bi-truck"></i></button>';
            } else {
                $buttons .= '<a href="'.route('freight_bookings.create').'?lorry_receipt_id='.$lorryReceipt->id.'" class="btn-action btn-action-success" title="Create Freight Booking" data-bs-toggle="tooltip">';
                $buttons .= '<i class="bi bi-truck"></i></a>';
            }
        }

        // Edit button
        $editUrl = route('lorry_receipts.edit', $lorryReceipt->id);
        if ($lorryReceipt->invoice_generated == 0 && Auth::user()->userRole->name === 'admin') {
            $confirmMsg = $freightBooking ? ' onclick="return confirm(\'A freight booking already exists. Continue?\')"' : '';
            $buttons .= '<a href="'.$editUrl.'" class="btn-action btn-edit" title="Edit" data-bs-toggle="tooltip"'.$confirmMsg.'>';
            $buttons .= '<i class="bi bi-pencil"></i></a>';
        }

        if ($lorryReceipt->delivery_status != 'Delivered' && Auth::user()->userRole->name === 'admin') {
            $deleteUrl = route('lorry_receipts.destroy', $lorryReceipt->id);
            $buttons .= '<form action="'.$deleteUrl.'" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure?\')">';
            $buttons .= csrf_field().method_field('DELETE');
            $buttons .= '<button type="submit" class="btn-action btn-action-danger" title="Delete" data-bs-toggle="tooltip">';
            $buttons .= '<i class="bi bi-trash"></i></button></form>';
        }

        $buttons .= '</div>';

        return $buttons;
    }

    public function create()
    {
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('lorry_receipts.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        // $trucks = Truck::all();
        $trucks = Truck::with(['transporter', 'driver'])
            ->whereHas('transporter', function ($query) {
                $query->where('company_id', Auth::user()->company_id);
            })
            ->get(); // Load both transporter and driver relationships

        $agencies = Transporter::where('is_owner', 0)->where('company_id', Auth::user()->company_id)->get();
        $consignees = Consignee::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();

        return view('lorry_receipts.create', compact('trucks', 'consignees', 'goodsTypes', 'agencies'));
    }

    public function createLinked()
    {
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('lorry_receipts.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        $companies = \App\Models\Company::all();
        // $trucks = Truck::all();
        $trucks = Truck::with(['transporter', 'driver'])
            ->whereHas('transporter', function ($query) {
                $query->where('company_id', Auth::user()->company_id);
            })
            ->get(); // Load both transporter and driver relationships

        $transporters = Transporter::all();
        $truckOwners = Transporter::where('is_owner', 1)->get();
        $agencies = Transporter::where('is_owner', 0)->where('company_id', Auth::user()->company_id)->get();
        $consignees = Consignee::where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $statuses = ['Pending', 'Delivered', 'Cancelled', 'In-Progress'];
        $tdsPercentages = [
            'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
            'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
            'owner' => env('TDS_PERCENTAGE_OWNER', 1),
        ];

        return view('lorry_receipts.create-linked', compact('companies', 'trucks', 'transporters', 'consignees', 'goodsTypes', 'statuses', 'truckOwners', 'agencies', 'tdsPercentages'));
    }

    public function bulkPayment(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'memo_id' => 'required|string',
                'lr_ids' => 'required|array',
                'lr_ids.*' => 'integer|exists:lorry_receipts,id',
            ]);

            // Get the lorry receipts that already have memo_id
            $existingMemoIds = LorryReceipt::whereIn('id', $validated['lr_ids'])

                ->where('company_id', Auth::user()->company_id)
                ->whereNotNull('memo_id')
                ->pluck('lr_number')
                ->toArray();

            if (! empty($existingMemoIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following LR numbers already have memo_id: '.implode(', ', $existingMemoIds),
                ], 422);
            }

            // Check freight_booking_id for selected lorry receipts
            $lorryReceipts = LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->where('company_id', Auth::user()->company_id)
                ->get();

            $noFreightBooking = $lorryReceipts->filter(function ($lr) {
                return ! $lr->freight_booking_id;
            })->pluck('lr_number')->toArray();

            if (! empty($noFreightBooking)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following LR numbers do not have a freight booking: '.implode(', ', $noFreightBooking),
                ], 422);
            }

            // ✅ Step: Ensure all selected LRs belong to same customer
            $customerIds = $lorryReceipts->pluck('customer_id')->unique();

            if ($customerIds->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected LRs belong to different customers. Please select LRs from the same customer only.',
                ], 422);
            }

            $customerId = $customerIds->first();

            // ✅ Step: Ensure memo_id is not used by another customer
            $memoUsedByOtherCustomer = LorryReceipt::where('memo_id', $validated['memo_id'])
                ->where('company_id', Auth::user()->company_id)
                ->where('customer_id', '!=', $customerId)
                ->exists();

            if ($memoUsedByOtherCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'This memo ID is already used by another customer. Please choose a unique one.',
                ], 422);
            }

            // Get freight booking IDs
            $freightBookingIds = $lorryReceipts->pluck('freight_booking_id')->toArray();

            Log::info('BULK PAYMENT: Starting updates', [
                'freight_booking_ids' => $freightBookingIds,
                'lr_ids' => $validated['lr_ids'],
                'memo_id' => $validated['memo_id'],
            ]);

            // Update is_delivered for freight bookings individually to trigger auditing
            $freightBookings = \App\Models\FreightBooking::whereIn('id', $freightBookingIds)->get();
            foreach ($freightBookings as $fb) {
                $fb->is_delivered = 1;
                $fb->save();
            }
            Log::info('BULK PAYMENT: Updated freight bookings is_delivered = 1');

            // Update memo_id and delivery_status individually to trigger auditing
            $lorryReceipts = LorryReceipt::whereIn('id', $validated['lr_ids'])->get();
            foreach ($lorryReceipts as $lr) {
                $lr->memo_id = $validated['memo_id'];
                $lr->delivery_status = 'Delivered';
                $lr->save();
            }
            Log::info('BULK PAYMENT: Updated memo_id and delivery_status', ['memo_id' => $validated['memo_id']]);
            Log::info('BULK PAYMENT: Updated delivery_status to Delivered');

            // Verify the updates
            $updatedLRs = LorryReceipt::whereIn('id', $validated['lr_ids'])->get();
            foreach ($updatedLRs as $lr) {
                Log::info('BULK PAYMENT: Final LR state', [
                    'lr_id' => $lr->id,
                    'lr_number' => $lr->lr_number,
                    'memo_id' => $lr->memo_id,
                    'delivery_status' => $lr->delivery_status,
                ]);
            }

            // Log the request data for debugging
            Log::info('Bulk Payment Request:', [
                'memo_id' => $validated['memo_id'],
                'lr_ids' => $validated['lr_ids'],
                'freight_booking_ids' => $freightBookingIds,
            ]);

            // memo_id already updated above, so just log the count
            $updatedCount = count($validated['lr_ids']);

            Log::info('Bulk Payment Update Result:', [
                'updated_count' => $updatedCount,
                'ids' => $validated['lr_ids'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Memo ID updated successfully for selected lorry receipts',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: '.json_encode($e->errors()),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Bulk Payment Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Memo ID: '.$e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // dd($request->all());
        // If request contains multiple LRs payload, handle batch creation with lr_linked_id
        if ($request->has('lrs') && is_array($request->input('lrs'))) {
            $sharedRules = [
                'gr_date' => 'required|date',
                'truck_id' => 'nullable|exists:trucks,id',
                'transporter_type' => 'nullable|in:owner,agency',
                'transporter_id' => 'nullable|exists:transporters,id',
                'reference_by' => 'nullable|exists:transporters,id',
                'company_id' => 'required|exists:companies,id',
            ];

            $perItemRules = [
                'lrs' => 'nullable|array|min:1',
                'lrs.*.lr_number' => [
                    'nullable',
                    'string',
                    'max:100',
                    'distinct',  // ✅ Only checks within request array
                ],
                'lrs.*.gr_number' => [
                    'nullable',
                    'string',
                    'max:100',
                    'distinct',  // ✅ Only checks within request array
                ],
                'lrs.*.invoice_number' => [
                    'nullable',
                    'string',
                    'max:256',
                    'distinct',  // ✅ Only checks within request array
                ],
                'lrs.*.customer_id' => 'nullable|exists:consignees,id',
                'lrs.*.consignor_id' => 'nullable|exists:consignees,id',
                'lrs.*.consignor_contact_number' => 'nullable|string|max:20',
                'lrs.*.consignee_id' => 'nullable|exists:consignees,id',
                'lrs.*.consignee_contact_number' => 'nullable|string|max:20',
                'lrs.*.source_city' => 'nullable|string|max:100',
                'lrs.*.source_state' => 'nullable|string|max:100',
                'lrs.*.destination_city' => 'nullable|string|max:100',
                'lrs.*.destination_state' => 'nullable|string|max:100',
                'lrs.*.unloading_station' => 'nullable|string|max:100',
                'lrs.*.lane' => 'nullable|string|max:100',
                'lrs.*.good_type' => 'nullable|string|max:100',
                'lrs.*.weight' => 'nullable|numeric|min:0',
                'lrs.*.no_of_units' => 'nullable|numeric|min:1|max:100000',
                'lrs.*.fr_rate' => 'nullable|numeric|min:1|max:20000',
                'lrs.*.amount' => 'nullable|numeric|min:0',
                'lrs.*.damage_amount' => 'nullable|numeric',
                'lrs.*.shortage_amount' => 'nullable|numeric',
            ];
            $this->validateLorryReceiptsUniqueness($request->lrs, Auth::user()->company_id);

            $validated = $request->validate(array_merge($sharedRules, $perItemRules));

            // Start DB transaction
            \DB::beginTransaction();
            try {
                // Next linked_lr_id (increment previous)
                // $nextLinkedId = (\App\Models\LorryReceipt::max('linked_lr_id') ?? 0) + 1;
                $nextLinkedId = (\App\Models\LorryReceipt::where('company_id', Auth::user()->company_id)->max('linked_lr_id') ?? 0) + 1;

                $created = [];
                foreach ($validated['lrs'] as $item) {
                    // Prevent consignor == consignee per LR
                    if ($item['consignor_id'] === $item['consignee_id']) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'lrs' => ['Consignor and Consignee cannot be the same for LR '.($item['lr_number'] ?? '')],
                        ]);
                    }

                    $data = [
                        'lr_number' => $item['lr_number'],
                        'gr_number' => $item['gr_number'],
                        'invoice_number' => $item['invoice_number'],
                        'customer_id' => $item['customer_id'],
                        'consignor_id' => $item['consignor_id'],
                        'consignor_contact_number' => $item['consignor_contact_number'] ?? '1234567890',
                        'consignee_id' => $item['consignee_id'],
                        'consignee_contact_number' => $item['consignee_contact_number'] ?? '0987654321',
                        'source_city' => $item['source_city'] ?? 'Jaipur',
                        'source_state' => $item['source_state'] ?? 'Rajasthan',
                        'destination_city' => $item['destination_city'] ?? 'Delhi',
                        'destination_state' => $item['destination_state'] ?? 'Delhi',
                        'unloading_station' => $item['unloading_station'] ?? null,
                        'lane' => $item['lane'],
                        'good_type' => $item['good_type'],
                        'weight' => $item['weight'],
                        'no_of_units' => $item['no_of_units'] ?? null,
                        'fr_rate' => $item['fr_rate'],
                        'amount' => $item['amount'],
                        'damage_amount' => $item['damage_amount'] ?? null,
                        'shortage_amount' => $item['shortage_amount'] ?? null,
                        // Shared
                        'gr_date' => $validated['gr_date'],
                        'truck_id' => $validated['truck_id'] ?? null,
                        'transporter_type' => $validated['transporter_type'] ?? null,
                        'transporter_id' => $validated['transporter_id'] ?? null,
                        'reference_by' => $validated['reference_by'] ?? null,
                        'company_id' => Auth::user()->company_id,
                        'delivery_status' => 'Pending',
                        'linked_lr_id' => $nextLinkedId,
                    ];

                    // if gr number is null than trim lr number from left side till 0 when 0 finishes stop trim method and take that value
                    if ($data['gr_number'] == null) {
                        // Remove "LR" prefix, then trim leading zeros
                        $data['gr_number'] = ltrim(str_ireplace('LR', '', $data['lr_number']), '0');
                    }
                    $created[] = \App\Models\LorryReceipt::create($data);
                }

                \DB::commit();

                return redirect()->route('lorry_receipts.index')->with('success', 'Created '.count($created).' linked Lorry Receipts (Linked ID: '.$nextLinkedId.').');
            } catch (\Throwable $e) {
                \DB::rollBack();
                throw $e;
            }
        }

        // dd($request->all());
        $validated = $request->validate([
            'customer_id' => 'required|exists:consignees,id',
            'lr_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lorry_receipts', 'lr_number')->where(function ($query) {
                    return $query->where('company_id', Auth::user()->company_id);
                }),
            ],
            'gr_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('lorry_receipts', 'gr_number')->where(function ($query) {
                    return $query->where('company_id', Auth::user()->company_id);
                }),
            ],
            'invoice_number' => [
                'nullable',
                'string',
                'max:256',
                Rule::unique('lorry_receipts', 'invoice_number')->where(function ($query) {
                    return $query->where('company_id', Auth::user()->company_id);
                }),
            ],
            'memo_id' => 'nullable|integer',
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'reference_by' => 'nullable|exists:transporters,id',
            'consignor_id' => 'required|exists:consignees,id',
            'consignor_contact_number' => 'nullable|string|max:20',
            'consignee_id' => 'required|exists:consignees,id',
            'consignee_contact_number' => 'nullable|string|max:20',
            'gr_date' => 'required|date',
            'company_id' => 'required|exists:companies,id',

            'delivery_status' => 'nullable|in:Pending,Delivered,Cancelled,In-Progress',
            'source_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'unloading_station' => 'nullable|string|max:100',
            'lane' => 'required|string|max:100',
            'good_type' => 'required|string|max:100',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'amount' => 'required|numeric|min:0',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'transporter_type' => 'nullable|in:owner,agency',
        ]);
        // dd($validated);

        // Check if consignor and consignee are the same
        if ($validated['consignor_id'] === $validated['consignee_id']) {
            return redirect()->back()->withErrors([
                'consignor_id' => 'Consignor and Consignee cannot be the same.',
                'consignee_id' => 'Consignor and Consignee cannot be the same.',
            ])->withInput();
        }

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;
        // dd($validated);

        // Set default delivery_status
        $validated['delivery_status'] = 'Pending';
        // dd($validated);

        // Check if lr_number already exists
        if ($validated['gr_number'] == null) {
            // Remove "LR" prefix, then trim leading zeros
            $validated['gr_number'] = ltrim(str_ireplace('LR', '', $validated['lr_number']), '0');
        }
        if (\App\Models\LorryReceipt::where('lr_number', $validated['lr_number'])
            ->where('company_id', Auth::user()->company_id)
            ->exists()
        ) {
            return redirect()->back()->withErrors(['lr_number' => 'LR Number already exists. Please use a unique LR Number.']);
        }

        // Note: Amount field is auto-calculated but user can override the value
        // No validation check needed - store whatever amount the user enters

        // Add company_id to validated data
        $validated['company_id'] = Auth::user()->company_id;
        // dd($validated);

        $lorryReceipt = \App\Models\LorryReceipt::create($validated);
        // dd($validated);

        // Check if user clicked "Save & Create FR"
        if ($request->has('redirect_to_fr')) {
            return redirect()->route('freight_bookings.create', ['lorry_receipt_id' => $lorryReceipt->id])
                ->with('success', 'Lorry Receipt saved! Now create Freight Booking.');
        }

        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt created successfully!');
    }

    /**
     * ✅ VALIDATE LORRY RECEIPTS COMPANY-WISE UNIQUENESS
     */
    private function validateLorryReceiptsUniqueness($lrs, $companyId)
    {
        $errors = [];

        // Extract all numbers from request
        $lrNumbers = array_filter(array_column($lrs, 'lr_number'));
        $grNumbers = array_filter(array_column($lrs, 'gr_number'));
        $invoiceNumbers = array_filter(array_column($lrs, 'invoice_number'));

        // ✅ CHECK 1: LR_NUMBER (Company-wise)
        if (! empty($lrNumbers)) {
            $duplicateLrs = \App\Models\LorryReceipt::where('company_id', $companyId)
                ->whereNull('deleted_at')  // ✅ Soft delete aware
                ->whereIn('lr_number', $lrNumbers)
                ->pluck('lr_number')
                ->toArray();

            if (! empty($duplicateLrs)) {
                foreach ($lrs as $index => $lr) {
                    if (! empty($lr['lr_number']) && in_array($lr['lr_number'], $duplicateLrs)) {
                        $errors["lrs.{$index}.lr_number"] =
                            "LR Number '{$lr['lr_number']}' already exists in your company.";
                    }
                }
            }
        }

        // ✅ CHECK 2: GR_NUMBER (Company-wise)
        if (! empty($grNumbers)) {
            $duplicateGrs = \App\Models\LorryReceipt::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereIn('gr_number', $grNumbers)
                ->pluck('gr_number')
                ->toArray();

            if (! empty($duplicateGrs)) {
                foreach ($lrs as $index => $lr) {
                    if (! empty($lr['gr_number']) && in_array($lr['gr_number'], $duplicateGrs)) {
                        $errors["lrs.{$index}.gr_number"] =
                            "GR Number '{$lr['gr_number']}' already exists in your company.";
                    }
                }
            }
        }

        // ✅ CHECK 3: INVOICE_NUMBER (Company-wise)
        if (! empty($invoiceNumbers)) {
            $duplicateInvoices = \App\Models\LorryReceipt::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereIn('invoice_number', $invoiceNumbers)
                ->pluck('invoice_number')
                ->toArray();

            if (! empty($duplicateInvoices)) {
                foreach ($lrs as $index => $lr) {
                    if (! empty($lr['invoice_number']) && in_array($lr['invoice_number'], $duplicateInvoices)) {
                        $errors["lrs.{$index}.invoice_number"] =
                            "Invoice Number '{$lr['invoice_number']}' already exists in your company.";
                    }
                }
            }
        }

        // Throw validation exception if errors exist
        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    public function checkLrNumberUniqueness(Request $request)
    {
        $lrNumber = $request->input('lr_number');
        $excludeId = $request->input('exclude_id') ?? null;

        if (! $lrNumber) {
            return response()->json(['valid' => false, 'message' => 'LR Number is required']);
        }

        $exists = \App\Models\LorryReceipt::where('lr_number', $lrNumber)
            ->where('company_id', Auth::user()->company_id)
            ->where('id', '!=', $excludeId)
            ->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'LR Number already exists. Please use a unique LR Number.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'LR Number is available',
        ]);
    }

    public function checkInvoiceNumberUniqueness(Request $request)
    {
        $invoiceNumber = $request->input('invoice_number');
        $excludeId = $request->input('exclude_id') ?? null;

        if (! $invoiceNumber) {
            return response()->json(['valid' => false, 'message' => 'Invoice Number is required']);
        }

        $exists = \App\Models\LorryReceipt::where('invoice_number', $invoiceNumber)
            ->where('company_id', Auth::user()->company_id)
            ->where('id', '!=', $excludeId)
            ->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'Invoice Number already exists. Please use a unique Invoice Number.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Invoice Number is available',
        ]);
    }

    // public function checkGRNumberUniqueness(Request $request)
    // {
    //   $grNumber = $request->input('gr_number');
    //   $excludeId = $request->input('exclude_id') ?? null;

    //   if (!$grNumber) {
    //     return response()->json(['valid' => true, 'message' => '']);
    //   }

    //   $exists = \App\Models\LorryReceipt::where('gr_number', $grNumber)
    //     ->when($excludeId, function($query) use ($excludeId) {
    //       return $query->where('id', '!=', $excludeId);
    //     })
    //     ->exists();

    //   if ($exists) {
    //     return response()->json([
    //       'valid' => false,
    //       'message' => 'GR Number already exists. Please use a unique GR Number.'
    //     ]);
    //   }

    //   return response()->json([
    //     'valid' => true,
    //     'message' => 'GR Number is available'
    //   ]);
    // }
    public function checkGRNumberUniqueness(Request $request)
    {

        $request->validate([
            'gr_number' => 'required|string',
        ]);

        $exists = LorryReceipt::where('gr_number', $request->gr_number)
            ->where('company_id', Auth::user()->company_id)
            ->exists();

        return response()->json(['available' => ! $exists]);
    }

    public function edit(\App\Models\LorryReceipt $lorry_receipt)
    {
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('lorry_receipts.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        // Eager load all necessary relationships
        $lorry_receipt = $lorry_receipt->load(['truck.transporter', 'truck.driver', 'customer', 'transporter', 'consignor', 'consignee']);

        $companies = \App\Models\Company::all();
        $trucks = Truck::with(['transporter', 'driver'])
            ->whereHas('transporter', function ($query) {
                $query->withTrashed()->where('company_id', Auth::user()->company_id);
            })
            ->get(); // Load both transporter and driver relationships

        $transporters = Transporter::withTrashed()->where('company_id', Auth::user()->company_id)->get();
        $truckOwners = Transporter::withTrashed()->where('company_id', Auth::user()->company_id)->where('is_owner', 1)->get();
        $agencies = Transporter::withTrashed()->where('company_id', Auth::user()->company_id)->where('is_owner', 0)->get();
        $consignees = Consignee::withTrashed()->where('company_id', Auth::user()->company_id)->orderBy('name')->get();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $statuses = ['Pending', 'Delivered', 'Cancelled', 'In-Progress'];
        $tdsPercentages = [
            'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
            'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
            'owner' => env('TDS_PERCENTAGE_OWNER', 1),
        ];
        $statuses = ['Pending', 'Delivered', 'Cancelled', 'In-Progress'];

        return view('lorry_receipts.edit', [
            'lorry_receipt' => $lorry_receipt,
            'trucks' => $trucks,
            'transporters' => $transporters,
            'statuses' => $statuses,
            'customers' => $consignees,
            'consignors' => $consignees, // Using consignees data for consignors dropdown
            'consignees' => $consignees,
            'goodsTypes' => $goodsTypes,
            'truckOwners' => $truckOwners,
            'agencies' => $agencies,
            'tdsPercentages' => $tdsPercentages,
        ]);
    }

    public function update(Request $request, \App\Models\LorryReceipt $lorry_receipt)
    {
        // dd($request->all());
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('lorry_receipts.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        if ($lorry_receipt->invoice_generated == 1) {
            return redirect()->route('lorry_receipts.index')->with('error', 'Cannot edit Lorry Receipt as invoice has already been generated.');
        }
        if ($lorry_receipt->delivery_status == 'Delivered') {
            // only allow updating memo_id if already delivered
            $validated = $request->validate([
                'memo_id' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) use ($request, $lorry_receipt) {
                        if ($value) {
                            $exists = \App\Models\LorryReceipt::where('memo_id', $value)
                                ->where('id', '!=', $lorry_receipt->id) // ignore current LR
                                ->first();

                            if ($exists && $exists->customer_id != $request->customer_id) {
                                $fail("Memo ID $value is already used by another customer.");
                            }
                        }
                    },
                ],
            ]);

            $lorry_receipt->update($validated);

            return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt updated successfully! Only Memo ID could be updated as the LR is already delivered.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:consignees,id',
            'lr_number' => ['required', 'string', 'max:100'],
            'gr_number' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:256'],

            'memo_id' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request, $lorry_receipt) {
                    if ($value) {
                        $exists = \App\Models\LorryReceipt::where('memo_id', $value)
                            ->where('id', '!=', $lorry_receipt->id) // ignore current LR
                            ->first();

                        if ($exists && $exists->customer_id != $request->customer_id) {
                            $fail("Memo ID $value is already used by another customer.");
                        }
                    }
                },
            ],
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'reference_by' => 'nullable|exists:transporters,id',
            'consignor_id' => 'required|exists:consignees,id',
            'consignor_contact_number' => 'nullable|string|max:20',
            'consignee_id' => 'required|exists:consignees,id',
            'consignee_contact_number' => 'nullable|string|max:20',
            'gr_date' => 'required|date',
            'company_id' => 'required|exists:companies,id',

            'delivery_status' => 'nullable|in:Pending,Delivered,Cancelled,In-Progress',
            'source_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'unloading_station' => 'nullable|string|max:100',
            'lane' => 'required|string|max:100',
            'good_type' => 'required|string|max:100',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'amount' => 'required|numeric|min:0',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'transporter_type' => 'nullable|in:owner,agency',

        ]);
        $this->validateLorryReceiptUniquenessUpdate($validated, $lorry_receipt->id, Auth::user()->company_id);
        // if mamo_id is null and freight booking id exist for that lr than delivery status to be in-progress

        // Check if consignor and consignee are the same
        if ($validated['consignor_id'] === $validated['consignee_id']) {
            return redirect()->back()->withErrors([
                'consignor_id' => 'Consignor and Consignee cannot be the same.',
                'consignee_id' => 'Consignor and Consignee cannot be the same.',
            ])->withInput();
        }
        if ($validated['gr_number'] == null) {
            // Remove "LR" prefix, then trim leading zeros
            $validated['gr_number'] = ltrim(str_ireplace('LR', '', $validated['lr_number']), '0');
        }

        // dd($validated['gr_number']);

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;

        // Preserve existing delivery_status - don't override it
        // Remove delivery_status from validated data to keep existing value
        // unset($validated['delivery_status']);

        // Check if lr_number already exists
        // if (\App\Models\LorryReceipt::where('lr_number', $validated['lr_number'])->exists()) {
        //     return redirect()->back()->withErrors(['lr_number' => 'LR Number already exists. Please use a unique LR Number.']);
        // }
        // Calculate expected amount from weight and freight rate
        //  $expectedAmount = ($validated['weight'] * $validated['fr_rate'])->round(2);
        $expectedAmount = round($validated['weight'] * $validated['fr_rate'] - ($validated['shortage_amount'] + $validated['damage_amount']), 2);

        // Check if entered amount matches calculated amount
        if ($validated['amount'] != $expectedAmount) {
            return redirect()->back()->withErrors([
                'amount' => 'The entered amount does not match the calculated amount. '.
                    'Expected amount is: '.$expectedAmount.
                    ' (Weight: '.$validated['weight'].' × FR Rate: '.$validated['fr_rate'].')',
            ])->withInput();
        }

        // Add company_id to validated data
        $validated['company_id'] = Auth::user()->company_id;

        // Store old values before update to detect changes
        $oldGoodType = $lorry_receipt->good_type;
        $oldTransporterId = $lorry_receipt->transporter_id;
        $oldLinkedLrId = $lorry_receipt->linked_lr_id;
        $oldDeliveryStatus = $lorry_receipt->delivery_status;
        $oldWeight = $lorry_receipt->weight;

        $lorry_receipt->update($validated);

        // Sync changes to related FreightBookings
        $freightBookings = \App\Models\FreightBooking::where('lr_id', $lorry_receipt->id)->get();

        foreach ($freightBookings as $freightBooking) {
            $updateData = [];

            // Sync good_type from LR to FB
            if (isset($validated['good_type']) && $validated['good_type'] != $oldGoodType) {
                $updateData['good_type'] = $validated['good_type'];
            }

            // Sync linked_lr_id from LR to FB
            if (isset($validated['linked_lr_id']) && $validated['linked_lr_id'] != $oldLinkedLrId) {
                $updateData['linked_lr_id'] = $validated['linked_lr_id'];
            }

            // Sync transporter info if transporter_id changed
            if (isset($validated['transporter_id']) && $validated['transporter_id'] != $oldTransporterId) {
                $transporter = \App\Models\Transporter::find($validated['transporter_id']);
                if ($transporter) {
                    $updateData['transporter_id'] = $transporter->id;
                    $updateData['transporter_name'] = $transporter->name;
                    $updateData['transporter_tds_percentage'] = $transporter->tds_percentage;
                }
            }

            // Update delivery status in FB based on LR delivery_status
            if (isset($validated['delivery_status']) && $validated['delivery_status'] != $oldDeliveryStatus) {
                if ($validated['delivery_status'] === 'Delivered') {
                    $updateData['is_delivered'] = 1;
                } elseif ($validated['delivery_status'] === 'Cancelled') {
                    $updateData['is_cancelled'] = 1;
                } elseif ($validated['delivery_status'] === 'Pending' || $validated['delivery_status'] === 'In-Progress') {
                    // Reset delivery flags if status goes back to pending/in-progress
                    $updateData['is_delivered'] = 0;
                    $updateData['is_cancelled'] = 0;
                }
            }

            // Handle weight changes - recalculate tr_amount using existing tr_fr_rate
            $weightChanged = isset($validated['weight']) && $validated['weight'] != $oldWeight;

            if ($weightChanged) {
                // Refresh to get updated weight value after the update
                $lorry_receipt->refresh();
                $newWeight = $lorry_receipt->weight;

                // Recalculate tr_amount using existing tr_fr_rate (do not change tr_fr_rate)
                $newTrAmount = round($freightBooking->tr_fr_rate * $newWeight, 2);
                $updateData['tr_amount'] = $newTrAmount;
            }

            // Recalculate balance_amount if tr_amount changed
            if (isset($updateData['tr_amount'])) {
                $trAmount = $updateData['tr_amount'];
                $damageAmount = $freightBooking->damage_amount ?? 0;
                $shortageAmount = $freightBooking->shortage_amount ?? 0;

                // Recalculate balance: tr_amount - advance_amount - tds_amount - damage_amount - shortage_amount + detention + deviation - misc_expense
                $newBalance = $trAmount
                    - $freightBooking->advance_amount
                    - ($freightBooking->tds_amount ?? 0)
                    - $damageAmount
                    - $shortageAmount
                    + ($freightBooking->detention ?? 0)
                    + ($freightBooking->deviation ?? 0)
                    - ($freightBooking->misc_expense ?? 0);

                $updateData['balance_amount'] = $newBalance;
            }

            // Update freight booking if there are changes
            if (! empty($updateData)) {
                $freightBooking->update($updateData);

                Log::info('Freight Booking synced from Lorry Receipt update', [
                    'freight_booking_id' => $freightBooking->id,
                    'lr_id' => $lorry_receipt->id,
                    'updated_fields' => array_keys($updateData),
                ]);
            }
        }

        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt updated successfully!');
    }

    private function validateLorryReceiptUniquenessUpdate($data, $excludeId, $companyId)
    {
        $errors = [];

        // ✅ CHECK 1: LR_NUMBER
        if (! empty($data['lr_number'])) {
            $exists = \App\Models\LorryReceipt::where('company_id', $companyId)
                ->where('lr_number', $data['lr_number'])
                ->where('id', '!=', $excludeId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $errors['lr_number'] = "LR Number '{$data['lr_number']}' already exists in your company.";
            }
        }

        // ✅ CHECK 2: GR_NUMBER
        if (! empty($data['gr_number'])) {
            $exists = \App\Models\LorryReceipt::where('company_id', $companyId)
                ->where('gr_number', $data['gr_number'])
                ->where('id', '!=', $excludeId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $errors['gr_number'] = "GR Number '{$data['gr_number']}' already exists in your company.";
            }
        }

        // ✅ CHECK 3: INVOICE_NUMBER
        if (! empty($data['invoice_number'])) {
            $exists = \App\Models\LorryReceipt::where('company_id', $companyId)
                ->where('invoice_number', $data['invoice_number'])
                ->where('id', '!=', $excludeId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $errors['invoice_number'] = "Invoice Number '{$data['invoice_number']}' already exists in your company.";
            }
        }

        // ✅ CHECK 4: MEMO_ID
        if (! empty($data['memo_id'])) {
            $exists = \App\Models\LorryReceipt::where('company_id', $companyId)
                ->where('memo_id', $data['memo_id'])
                ->where('id', '!=', $excludeId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $errors['memo_id'] = "Memo ID '{$data['memo_id']}' already exists in your company.";
            }
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    public function destroy(\App\Models\LorryReceipt $lorry_receipt)
    {
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('lorry_receipts.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        // Use transaction to ensure data consistency
        \DB::beginTransaction();

        try {
            // This will trigger the deleting event in the model
            // which will handle the related FreightBookings and VoucherPayments
            $lorry_receipt->delete();

            \DB::commit();

            return redirect()->route('lorry_receipts.index')
                ->with('success', 'Lorry receipt and all associated data deleted successfully!');
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Error deleting lorry receipt: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Error deleting lorry receipt. Please try again.');
        }
    }

    public function details($id)
    {
        $lorry_receipt = \App\Models\LorryReceipt::with([
            'truck',
            'transporter',
            'consignor',
            'consignee',
            'customer',
            'reference',
            'creator',
            'updater',
        ])->findOrFail($id);

        $response = $lorry_receipt->toArray();

        // If this LR has linked_lr_id, fetch and include linked LRs data
        if ($lorry_receipt->linked_lr_id) {
            $linkedLRs = LorryReceipt::with(['consignor', 'consignee', 'customer', 'transporter', 'truck', 'reference'])
                ->where('company_id', Auth::user()->company_id)
                ->where('linked_lr_id', $lorry_receipt->linked_lr_id)
                ->get();

            if ($linkedLRs->isNotEmpty()) {
                // Find common fields across all LRs
                $common = [
                    'transporter' => $linkedLRs->pluck('transporter.name')->unique()->count() === 1
                        ? $linkedLRs->first()->transporter->name ?? null
                        : null,
                    'truck_number' => $linkedLRs->pluck('truck.truck_number')->unique()->count() === 1
                        ? $linkedLRs->first()->truck->truck_number ?? null
                        : null,
                    'gr_date' => $linkedLRs->pluck('gr_date')->unique()->count() === 1
                        ? $linkedLRs->first()->gr_date
                        : null,
                    'reference_by' => $linkedLRs->pluck('reference.name')->unique()->count() === 1
                        ? $linkedLRs->first()->reference->name ?? null
                        : null,
                ];

                $linkedDetails = $linkedLRs->map(function ($lr) {
                    return [
                        'id' => $lr->id,
                        'lr_number' => $lr->lr_number,
                        'gr_date' => $lr->gr_date,
                        'status' => $lr->delivery_status,
                        'customer' => $lr->customer ? $lr->customer->name : null,
                        'consignor' => $lr->consignor ? $lr->consignor->name : null,
                        'consignee' => $lr->consignee ? $lr->consignee->name : null,
                        'good_type' => $lr->good_type,
                        'weight' => $lr->weight,
                        'amount' => $lr->amount,
                        'freight_booking_id' => $lr->freight_booking_id,
                        'linked_lr_id' => $lr->linked_lr_id,
                    ];
                });

                $response['linked_lrs_data'] = [
                    'common' => $common,
                    'lrs' => $linkedDetails,
                ];
            }
        }

        return response()->json($response);
    }

    /**
     * Process bulk voucher payments for multiple lorry receipts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkVoucherPayment(Request $request)
    {
        try {
            // Log the incoming request for debugging
            Log::info('Bulk voucher payment request received:', [
                'request_data' => $request->all(),
            ]);

            // Validate the request structure
            $validated = $request->validate([
                'payments' => 'required|array',
                'payments.*.lorry_receipt_id' => 'required|integer|exists:lorry_receipts,id',
                'payments.*.freight_booking_id' => 'required|integer|exists:freight_bookings,id',
                'payments.*.amount' => 'required|numeric|min:0.01',
                'payments.*.voucher_number' => 'required|string',
            ]);

            $payments = $validated['payments'];
            $successCount = 0;
            $errors = [];

            // Process each payment
            foreach ($payments as $index => $payment) {
                try {
                    // Get the lorry receipt and check company ownership
                    $lorryReceipt = \App\Models\LorryReceipt::findOrFail($payment['lorry_receipt_id']);

                    // Security check: Ensure user can only process payments for their company
                    if (
                        Auth::user()->userRole->name !== 'super_admin' &&
                        $lorryReceipt->company_id !== Auth::user()->company_id
                    ) {
                        $errors[] = "Security violation: LR #{$lorryReceipt->lr_number} does not belong to your company";

                        continue;
                    }

                    // Get the freight booking
                    $freightBooking = \App\Models\FreightBooking::findOrFail($payment['freight_booking_id']);

                    // Verify freight booking belongs to the lorry receipt
                    if ($freightBooking->lorry_receipt_id != $lorryReceipt->id) {
                        $errors[] = "Freight booking #{$payment['freight_booking_id']} does not belong to LR #{$lorryReceipt->lr_number}";

                        continue;
                    }

                    // Validate payment amount doesn't exceed remaining balance
                    $remainingBalance = $freightBooking->tr_amount - $freightBooking->advance_amount;
                    if ($payment['amount'] > $remainingBalance) {
                        $errors[] = "Payment amount ₹{$payment['amount']} exceeds remaining balance ₹{$remainingBalance} for LR #{$lorryReceipt->lr_number}";

                        continue;
                    }

                    // Create voucher payment record
                    $voucherPayment = \App\Models\VoucherPayment::create([
                        'freight_booking_id' => $freightBooking->id,
                        'voucher_number' => $payment['voucher_number'],
                        'payment_type' => 'voucher', // Default payment type
                        'payment_mode' => 'cash',    // Default payment mode
                        'amount' => $payment['amount'],
                        'payment_status' => 'delivered',
                        'company_id' => $lorryReceipt->company_id,
                    ]);

                    // Update freight booking advance and balance amounts
                    $freightBooking->advance_amount += $payment['amount'];
                    $freightBooking->balance_amount = $freightBooking->tr_amount - $freightBooking->advance_amount;
                    $freightBooking->save();

                    Log::info('Voucher payment processed successfully', [
                        'lr_id' => $lorryReceipt->id,
                        'lr_number' => $lorryReceipt->lr_number,
                        'freight_booking_id' => $freightBooking->id,
                        'amount' => $payment['amount'],
                        'new_advance_amount' => $freightBooking->advance_amount,
                        'new_balance_amount' => $freightBooking->balance_amount,
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    Log::error("Error processing payment for LR #{$payment['lorry_receipt_id']}", [
                        'error' => $e->getMessage(),
                        'payment' => $payment,
                    ]);
                    $errors[] = "Error processing payment for LR #{$payment['lorry_receipt_id']}: {$e->getMessage()}";
                }
            }

            // Return appropriate response
            if ($successCount > 0 && empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully processed {$successCount} voucher payments",
                ]);
            } elseif ($successCount > 0 && ! empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "Processed {$successCount} payments with {count($errors)} errors",
                    'errors' => $errors,
                ], 207); // 207 Multi-Status
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process voucher payments',
                    'errors' => $errors,
                ], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Bulk voucher payment validation error:', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Bulk voucher payment error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing voucher payments: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getLorryReceiptsForCustomer($customerId)
    {
        $lorryReceipts = LorryReceipt::where('customer_id', $customerId)
            ->where('company_id', Auth::user()->company_id)
            ->get(['id', 'lr_number']);

        return response()->json($lorryReceipts);
    }

    public function getReferencesForTransporterTruck($transporterId, $truckId)
    {
        $lrs = LorryReceipt::where('transporter_id', $transporterId)
            ->where('truck_id', $truckId)
            ->where('company_id', Auth::user()->company_id)
            ->get(['reference_by']);

        $references = $lrs->pluck('reference_by')->unique()->filter();
        $references = Transporter::whereIn('id', $references)->get(['id', 'name']);

        return response()->json($references);
    }
}
