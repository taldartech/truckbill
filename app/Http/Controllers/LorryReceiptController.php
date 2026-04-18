<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\GoodType;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Services\Notifications\LorryReceiptNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class LorryReceiptController extends Controller
{
    public function __construct(
        private readonly LorryReceiptNotificationService $lrNotificationService
    ) {}

    /**
     * Check if user's subscription plan is expired
     */
    private function isPlanExpired(): bool
    {
        $company = Auth::user()->company;
        if (! $company->plan_end_date) {
            return false;
        }

        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();

        return $today->gt($planEndDate);
    }

    /**
     * Redirect if plan is expired
     */
    private function redirectIfPlanExpired(string $route = 'lorry_receipts.index'): ?\Illuminate\Http\RedirectResponse
    {
        if ($this->isPlanExpired()) {
            return redirect()->route($route)
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        return null;
    }

    /**
     * Abort 403 if super_admin has no company context in session.
     */
    private function getCurrentCompanyId(): ?int
    {
        if (Auth::user()->userRole->name === 'super_admin') {
            return session('super_admin_company_id');
        }

        return Auth::user()->company_id;
    }

    private function applyCompanyTruckScope($query, int $companyId)
    {
        return $query->where(function ($truckQuery) use ($companyId) {
            $truckQuery->whereHas('transporter', function ($transporterQuery) use ($companyId) {
                $transporterQuery->where('company_id', $companyId);
            })->orWhere(function ($orphanQuery) use ($companyId) {
                $orphanQuery->whereNull('transporter_id')
                    ->where('company_id', $companyId);
            });
        });
    }

    private function forceAgencyForOrphanTruck(array &$validated, int $companyId): void
    {
        if (empty($validated['truck_id'])) {
            return;
        }

        $truck = $this->applyCompanyTruckScope(Truck::query(), $companyId)
            ->where('id', $validated['truck_id'])
            ->firstOrFail();

        if (! $truck->transporter_id) {
            $validated['transporter_type'] = 'agency';
        }
    }

    public function index(Request $request): \Illuminate\View\View|\Illuminate\Http\JsonResponse
    {
        if ($request->ajax()) {
            return $this->getLorryReceiptsData($request);
        }

        return view('lorry_receipts.index');
    }

    private function getLorryReceiptsData(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $query = LorryReceipt::with(['truck', 'transporter.transporterMaster', 'consignor', 'consignee', 'company', 'customer']);

            if (Auth::user()->company_id) {
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

    private function getActionButtons(LorryReceipt $lorryReceipt): string
    {
        $buttons = '<div class="d-flex gap-1 justify-content-center">';

        // Freight booking button
        $freight_booking_id = $lorryReceipt->freight_booking_id;
        $freightBooking = FreightBooking::find($freight_booking_id);
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();

        if ($freight_booking_id) {
            $buttons .= '<a href="'.route('freight_bookings.edit', $freight_booking_id).'" class="btn btn-sm btn-warning" title="Edit Good Hire (Lorry Hire)" data-bs-toggle="tooltip">';
            $buttons .= '<i class="bi bi-truck"></i></a>';
        } else {
            if ($company->lr_credits <= 0) {
                $buttons .= '<button class="btn btn-sm btn-secondary" title="Insufficient Credits" data-bs-toggle="tooltip" onclick="alert(\'Insufficient credits\')">';
                $buttons .= '<i class="bi bi-truck"></i></button>';
            } elseif ($company->plan_end_date && $today->gt($planEndDate)) {
                $buttons .= '<button class="btn btn-sm btn-secondary" title="Plan Expired" data-bs-toggle="tooltip" onclick="alert(\'Plan expired\')">';
                $buttons .= '<i class="bi bi-truck"></i></button>';
            } else {
                $buttons .= '<a href="'.route('freight_bookings.create').'?lorry_receipt_id='.$lorryReceipt->id.'" class="btn btn-sm btn-success" title="Create Good Hire (Lorry Hire)" data-bs-toggle="tooltip">';
                $buttons .= '<i class="bi bi-truck"></i></a>';
            }
        }

        // Edit button
        $editUrl = route('lorry_receipts.edit', $lorryReceipt->id);
        if ($lorryReceipt->invoice_generated == 0 && Auth::user()->canActAsAdmin()) {
            $confirmMsg = $freightBooking ? ' onclick="return confirm(\'A good hire (lorry hire) already exists. Continue?\')"' : '';
            $buttons .= '<a href="'.$editUrl.'" class="btn-action btn-edit" title="Edit" data-bs-toggle="tooltip"'.$confirmMsg.'>';
            $buttons .= '<i class="bi bi-pencil"></i></a>';
        }

        if ($lorryReceipt->delivery_status != 'Delivered' && Auth::user()->canActAsAdmin()) {
            $deleteUrl = route('lorry_receipts.destroy', $lorryReceipt->id);
            $buttons .= '<form action="'.$deleteUrl.'" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure?\')">';
            $buttons .= csrf_field().method_field('DELETE');
            $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete" data-bs-toggle="tooltip">';
            $buttons .= '<i class="bi bi-trash"></i></button></form>';
        }

        // LR Copy button
        $buttons .= '<button type="button" class="btn btn-sm btn-info lr-copy-btn" title="LR Copy" data-bs-toggle="modal" data-bs-target="#lrCopyModal" data-lr-id="'.$lorryReceipt->id.'">';
        $buttons .= '<i class="bi bi-file-earmark-text"></i></button>';

        $buttons .= '</div>';

        return $buttons;
    }

    public function create(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        if ($redirect = $this->redirectIfPlanExpired()) {
            return $redirect;
        }

        $companyId = $this->getCurrentCompanyId();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();

        // Only fetch selected IDs (if any) to avoid loading large dropdown datasets.
        $selectedConsigneeIds = collect([
            old('customer_id'),
            old('consignor_id'),
            old('consignee_id'),
        ])->filter()->unique()->values();

        $consignees = $selectedConsigneeIds->isNotEmpty()
            ? Consignee::withTrashed()
                ->where('company_id', $companyId)
                ->whereIn('id', $selectedConsigneeIds)
                ->orderBy('name')
                ->get()
            : collect();

        $selectedTruckId = old('truck_id');
        $trucks = $selectedTruckId
            ? Truck::with(['transporter.transporterMaster', 'driver'])
                ->where('id', $selectedTruckId)
                ->where(function ($query) use ($companyId) {
                    $query->whereHas('transporter', function ($transporterQuery) use ($companyId) {
                        $transporterQuery->where('company_id', $companyId);
                    })->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
                })
                ->get()
            : collect();

        $selectedReferenceById = old('reference_by');
        $agencies = $selectedReferenceById
            ? Transporter::with('transporterMaster')
                ->where('is_owner', 0)
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedReferenceById)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        return view('lorry_receipts.create', compact('trucks', 'consignees', 'goodsTypes', 'agencies'));
    }

    public function createLinked(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        if ($redirect = $this->redirectIfPlanExpired()) {
            return $redirect;
        }

        $companyId = $this->getCurrentCompanyId();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();

        // Only fetch selected IDs (if any) to avoid loading large dropdown datasets.
        $selectedConsigneeIds = collect([
            old('customer_id'),
            old('consignor_id'),
            old('consignee_id'),
        ])->filter()->unique()->values();

        $consignees = $selectedConsigneeIds->isNotEmpty()
            ? Consignee::withTrashed()
                ->where('company_id', $companyId)
                ->whereIn('id', $selectedConsigneeIds)
                ->orderBy('name')
                ->get()
            : collect();

        $selectedTruckId = old('truck_id');
        $trucks = $selectedTruckId
            ? Truck::with(['transporter.transporterMaster', 'driver'])
                ->where('id', $selectedTruckId)
                ->where(function ($query) use ($companyId) {
                    $query->whereHas('transporter', function ($transporterQuery) use ($companyId) {
                        $transporterQuery->where('company_id', $companyId);
                    })->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
                })
                ->get()
            : collect();

        $selectedReferenceById = old('reference_by');
        $agencies = $selectedReferenceById
            ? Transporter::with('transporterMaster')
                ->where('is_owner', 0)
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedReferenceById)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        return view('lorry_receipts.create-linked', compact('trucks', 'consignees', 'goodsTypes', 'agencies'));
    }

    /**
     * Bulk update memo_id and delivery status for multiple lorry receipts
     */
    public function bulkPayment(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'memo_id' => 'required|string',
                'lr_ids' => 'required|array',
                'lr_ids.*' => 'integer|exists:lorry_receipts,id',
            ]);

            $companyId = Auth::user()->company_id;
            $lorryReceipts = LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->where('company_id', $companyId)
                ->get();

            // Validate all LRs belong to same company
            if ($lorryReceipts->count() !== count($validated['lr_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected LRs do not belong to your company.',
                ], 422);
            }

            // Check for existing memo_ids
            $existingMemoIds = $lorryReceipts->whereNotNull('memo_id')
                ->pluck('lr_number')
                ->toArray();

            if (! empty($existingMemoIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following LR numbers already have memo_id: '.implode(', ', $existingMemoIds),
                ], 422);
            }

            // Check freight_booking_id exists
            $noFreightBooking = $lorryReceipts->whereNull('freight_booking_id')
                ->pluck('lr_number')
                ->toArray();

            if (! empty($noFreightBooking)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following LR numbers do not have a good hire (lorry hire): '.implode(', ', $noFreightBooking),
                ], 422);
            }

            // Ensure all selected LRs belong to same customer
            $customerIds = $lorryReceipts->pluck('customer_id')->unique();

            if ($customerIds->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected LRs belong to different customers. Please select LRs from the same customer only.',
                ], 422);
            }

            $customerId = $customerIds->first();

            // Ensure memo_id is not used by another customer
            $memoUsedByOtherCustomer = LorryReceipt::where('memo_id', $validated['memo_id'])
                ->where('company_id', $companyId)
                ->where('customer_id', '!=', $customerId)
                ->exists();

            if ($memoUsedByOtherCustomer) {
                return response()->json([
                    'success' => false,
                    'message' => 'This memo ID is already used by another customer. Please choose a unique one.',
                ], 422);
            }

            // Process updates in transaction
            DB::transaction(function () use ($validated, $lorryReceipts) {
                $freightBookingIds = $lorryReceipts->pluck('freight_booking_id')->toArray();

                // Update freight bookings
                \App\Models\FreightBooking::whereIn('id', $freightBookingIds)
                    ->update(['is_delivered' => 1]);

                // Update lorry receipts
                foreach ($lorryReceipts as $lr) {
                    $oldDeliveryStatus = $lr->delivery_status;
                    $lr->memo_id = $validated['memo_id'];
                    $lr->delivery_status = 'Delivered';
                    $lr->save();

                    if ($oldDeliveryStatus !== 'Delivered') {
                        $lr->loadMissing(['company', 'truck', 'transporter.transporterMaster', 'customer']);
                        $this->lrNotificationService->notifyStatusChange($lr, 'Delivered', auth()->user());
                    }
                }

            });

            return response()->json([
                'success' => true,
                'message' => 'Memo ID updated successfully for selected good receipts (bilty)',
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

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {

        $companyId = $this->getCurrentCompanyId();
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
                'lrs.*.goods_value' => 'nullable|numeric|min:0',
                'lrs.*.weight' => 'nullable|numeric|min:0',
                'lrs.*.no_of_units' => 'nullable|numeric|min:1|max:100000',
                'lrs.*.fr_rate' => 'nullable|numeric|min:1|max:20000',
                'lrs.*.is_fixed_amount' => 'nullable|boolean',
                'lrs.*.amount' => 'nullable|numeric|min:0',
                'lrs.*.damage_amount' => 'nullable|numeric',
                'lrs.*.shortage_amount' => 'nullable|numeric',
            ];
            $this->validateLorryReceiptsUniqueness($request->lrs, $companyId);

            $validated = $request->validate(array_merge($sharedRules, $perItemRules));
            $this->forceAgencyForOrphanTruck($validated, $companyId);

            // Start DB transaction
            DB::beginTransaction();
            try {
                // Next linked_lr_id (increment previous)
                // $nextLinkedId = (\App\Models\LorryReceipt::max('linked_lr_id') ?? 0) + 1;
                $nextLinkedId = (\App\Models\LorryReceipt::where('company_id', $companyId)->max('linked_lr_id') ?? 0) + 1;

                $created = [];
                foreach ($validated['lrs'] as $item) {
                    // Prevent consignor == consignee per LR
                    if ($item['consignor_id'] === $item['consignee_id']) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'lrs' => ['Consignor and Consignee cannot be the same for LR '.($item['lr_number'] ?? '')],
                        ]);
                    }

                    $isFixed = (bool) ($item['is_fixed_amount'] ?? false);

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
                        'goods_value' => $item['goods_value'] ?? null,
                        'weight' => $item['weight'],
                        'no_of_units' => $item['no_of_units'] ?? null,
                        'fr_rate' => $item['fr_rate'],
                        'is_fixed_amount' => $isFixed,
                        'amount' => $item['amount'],
                        'actual_amount' => $item['amount'],
                        'damage_amount' => $item['damage_amount'] ?? null,
                        'shortage_amount' => $item['shortage_amount'] ?? null,
                        // Shared
                        'gr_date' => $validated['gr_date'],
                        'truck_id' => $validated['truck_id'] ?? null,
                        'transporter_type' => $validated['transporter_type'] ?? null,
                        'transporter_id' => $validated['transporter_id'] ?? null,
                        'reference_by' => $validated['reference_by'] ?? null,
                        'company_id' => $companyId,
                        'delivery_status' => 'Pending',
                        'linked_lr_id' => $nextLinkedId,
                    ];

                    // Fallback: derive GR number from LR number when not supplied.
                    if ($data['gr_number'] == null) {
                        // Remove "LR" prefix, then trim leading zeros
                        $data['gr_number'] = ltrim(str_ireplace('LR', '', $data['lr_number']), '0');
                    }
                    $created[] = \App\Models\LorryReceipt::create($data);
                }

                DB::commit();

                return redirect()->route('lorry_receipts.index')->with('success', 'Created '.count($created).' linked Good Receipts (Bilty) (Linked ID: '.$nextLinkedId.').');
            } catch (\Throwable $e) {
                DB::rollBack();
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
                    return $query->where('company_id', $this->getCurrentCompanyId());
                }),
            ],
            'gr_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('lorry_receipts', 'gr_number')->where(function ($query) {
                    return $query->where('company_id', $this->getCurrentCompanyId());
                }),
            ],
            'invoice_number' => [
                'nullable',
                'string',
                'max:256',
                Rule::unique('lorry_receipts', 'invoice_number')->where(function ($query) {
                    return $query->where('company_id', $this->getCurrentCompanyId());
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
            'goods_value' => 'nullable|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'is_fixed_amount' => 'nullable|boolean',
            'amount' => 'required|numeric|min:0',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'transporter_type' => 'nullable|in:owner,agency',
        ]);
        // dd($validated);

        // Prevent selecting the same party for consignor and consignee.
        if ($validated['consignor_id'] === $validated['consignee_id']) {
            return redirect()->back()->withErrors([
                'consignor_id' => 'Consignor and Consignee cannot be the same.',
                'consignee_id' => 'Consignor and Consignee cannot be the same.',
            ])->withInput();
        }

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;
        $this->forceAgencyForOrphanTruck($validated, $companyId);
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
            ->where('company_id', $companyId)
            ->exists()
        ) {
            return redirect()->back()->withErrors(['lr_number' => 'LR Number already exists. Please use a unique LR Number.']);
        }

        // Set unloading_weight = weight initially (for loose weight mode)
        if (! isset($validated['unloading_weight']) || $validated['unloading_weight'] === null) {
            $validated['unloading_weight'] = $validated['weight'];
        }

        $isFixed = (bool) ($validated['is_fixed_amount'] ?? false);

        if ($isFixed) {
            $validated['actual_amount'] = $validated['amount'];
            $shortageAmount = $validated['shortage_amount'] ?? 0;
            $damageAmount = $validated['damage_amount'] ?? 0;
            $validated['amount'] = max(0, round($validated['actual_amount'] - $shortageAmount - $damageAmount, 2));
        } else {
            // Calculate amount and actual_amount using loose weight (min of loading/unloading weight)
            $loadingWeight = $validated['weight'];
            $unloadingWeight = $validated['unloading_weight'];

            // Only recalculate actual_amount if loading_weight != unloading_weight
            // If weights are equal, use the manually entered actual_amount from form if provided, otherwise calculate it
            if (abs($loadingWeight - $unloadingWeight) > 0.01) {
                // Weights are different - always recalculate actual_amount
                $effectiveWeight = min($loadingWeight, $unloadingWeight);
                $validated['actual_amount'] = round($effectiveWeight * $validated['fr_rate'], 2);
            } else {
                // Weights are equal - use manually entered actual_amount from form if provided, otherwise calculate it
                if (! isset($validated['actual_amount']) || $validated['actual_amount'] === null) {
                    $effectiveWeight = min($loadingWeight, $unloadingWeight);
                    $validated['actual_amount'] = round($effectiveWeight * $validated['fr_rate'], 2);
                }
            }

            // Calculate amount: actual_amount - shortage - damage (if provided)
            $shortageAmount = $validated['shortage_amount'] ?? 0;
            $damageAmount = $validated['damage_amount'] ?? 0;
            $validated['amount'] = max(0, round($validated['actual_amount'] - $shortageAmount - $damageAmount, 2));
        }

        // Add company_id to validated data
        $validated['company_id'] = $companyId;
        // dd($validated);

        $lorryReceipt = \App\Models\LorryReceipt::create($validated);
        // dd($validated);

        // Check if user clicked "Save & Create FR"
        if ($request->has('redirect_to_fr')) {
            return redirect()->route('freight_bookings.create', ['lorry_receipt_id' => $lorryReceipt->id])
                ->with('success', 'Good Receipt (Bilty) saved! Now create Good Hire (Lorry Hire).');
        }

        return redirect()->route('lorry_receipts.index')->with('success', 'Good Receipt (Bilty) created successfully!');
    }

    /**
     * ✅ VALIDATE LORRY RECEIPTS COMPANY-WISE UNIQUENESS
     */
    /**
     * Validate uniqueness of LR numbers, GR numbers, and invoice numbers
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateLorryReceiptsUniqueness(array $lrs, int $companyId): void
    {
        $errors = [];
        $lrNumbers = array_filter(array_column($lrs, 'lr_number'));
        $grNumbers = array_filter(array_column($lrs, 'gr_number'));
        $invoiceNumbers = array_filter(array_column($lrs, 'invoice_number'));

        // Check LR_NUMBER uniqueness (Company-wise)
        if (! empty($lrNumbers)) {
            $duplicateLrs = LorryReceipt::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereIn('lr_number', $lrNumbers)
                ->pluck('lr_number')
                ->toArray();

            if (! empty($duplicateLrs)) {
                foreach ($lrs as $index => $lr) {
                    if (! empty($lr['lr_number']) && in_array($lr['lr_number'], $duplicateLrs)) {
                        $errors["lrs.{$index}.lr_number"] = "LR Number '{$lr['lr_number']}' already exists in your company.";
                    }
                }
            }
        }

        // Check GR_NUMBER uniqueness (Company-wise)
        if (! empty($grNumbers)) {
            $duplicateGrs = LorryReceipt::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereIn('gr_number', $grNumbers)
                ->pluck('gr_number')
                ->toArray();

            if (! empty($duplicateGrs)) {
                foreach ($lrs as $index => $lr) {
                    if (! empty($lr['gr_number']) && in_array($lr['gr_number'], $duplicateGrs)) {
                        $errors["lrs.{$index}.gr_number"] = "GR Number '{$lr['gr_number']}' already exists in your company.";
                    }
                }
            }
        }

        // Check INVOICE_NUMBER uniqueness (Company-wise)
        if (! empty($invoiceNumbers)) {
            $duplicateInvoices = LorryReceipt::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereIn('invoice_number', $invoiceNumbers)
                ->pluck('invoice_number')
                ->toArray();

            if (! empty($duplicateInvoices)) {
                foreach ($lrs as $index => $lr) {
                    if (! empty($lr['invoice_number']) && in_array($lr['invoice_number'], $duplicateInvoices)) {
                        $errors["lrs.{$index}.invoice_number"] = "Invoice Number '{$lr['invoice_number']}' already exists in your company.";
                    }
                }
            }
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    public function checkLrNumberUniqueness(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'lr_number' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = LorryReceipt::where('lr_number', $request->lr_number)
            ->where('company_id', Auth::user()->company_id)
            ->when($request->exclude_id, fn ($query) => $query->where('id', '!=', $request->exclude_id))
            ->exists();

        return response()->json([
            'valid' => ! $exists,
            'message' => $exists
                ? 'LR Number already exists. Please use a unique LR Number.'
                : 'LR Number is available',
        ]);
    }

    public function checkInvoiceNumberUniqueness(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'invoice_number' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = LorryReceipt::where('invoice_number', $request->invoice_number)
            ->where('company_id', Auth::user()->company_id)
            ->when($request->exclude_id, fn ($query) => $query->where('id', '!=', $request->exclude_id))
            ->exists();

        return response()->json([
            'valid' => ! $exists,
            'message' => $exists
                ? 'Invoice Number already exists. Please use a unique Invoice Number.'
                : 'Invoice Number is available',
        ]);
    }

    public function checkGRNumberUniqueness(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'gr_number' => 'required|string',
        ]);

        $exists = LorryReceipt::where('gr_number', $request->gr_number)
            ->where('company_id', Auth::user()->company_id)
            ->exists();

        return response()->json(['available' => ! $exists]);
    }

    public function edit(LorryReceipt $lorry_receipt): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        if ($redirect = $this->redirectIfPlanExpired()) {
            return $redirect;
        }

        $companyId = $this->getCurrentCompanyId();
        $lorry_receipt->load(['truck.transporter.transporterMaster', 'truck.driver', 'customer', 'transporter.transporterMaster', 'consignor', 'consignee']);

        // Fetch only the selected records needed for initial dropdown rendering.
        $selectedTruckId = $lorry_receipt->truck_id;
        $trucks = $selectedTruckId
            ? Truck::with(['transporter.transporterMaster', 'driver'])
                ->where('id', $selectedTruckId)
                ->where(function ($query) use ($companyId) {
                    $query->whereHas('transporter', function ($transporterQuery) use ($companyId) {
                        $transporterQuery->withTrashed()->where('company_id', $companyId);
                    })->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
                })
                ->get()
            : collect();

        $selectedTransporterId = $lorry_receipt->transporter_id;
        $transporters = $selectedTransporterId
            ? Transporter::withTrashed()->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedTransporterId)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $truckOwners = ($lorry_receipt->transporter_type === 'owner' && $selectedTransporterId)
            ? Transporter::withTrashed()->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedTransporterId)
                ->where('is_owner', 1)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $agencies = ($lorry_receipt->reference_by)
            ? Transporter::withTrashed()->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('transporters.id', $lorry_receipt->reference_by)
                ->where('is_owner', 0)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $selectedConsigneeIds = collect([
            $lorry_receipt->customer_id,
            $lorry_receipt->consignor_id,
            $lorry_receipt->consignee_id,
        ])->filter()->unique()->values();

        $consignees = $selectedConsigneeIds->isNotEmpty()
            ? Consignee::withTrashed()
                ->where('company_id', $companyId)
                ->whereIn('id', $selectedConsigneeIds)
                ->orderBy('name')
                ->get()
            : collect();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $statuses = ['Pending', 'Delivered', 'Cancelled', 'In-Progress'];
        $tdsPercentages = [
            'default' => config('payments.tds_percentages.default', 20),
            'agency' => config('payments.tds_percentages.agency', 2),
            'owner' => config('payments.tds_percentages.owner', 1),
        ];

        // Check if transporter can be changed (TDS not submitted)
        // Transporter cannot be changed if TDS is submitted, regardless of payment status
        $canChangeTransporter = true;
        $freightBookings = \App\Models\FreightBooking::where('lr_id', $lorry_receipt->id)->get();

        $grDateToCheck = null;
        if ($lorry_receipt->gr_date) {
            $grDateToCheck = $lorry_receipt->gr_date;
        } elseif (! $freightBookings->isEmpty()) {
            foreach ($freightBookings as $freightBooking) {
                if ($freightBooking->lr && $freightBooking->lr->gr_date) {
                    $grDateToCheck = $freightBooking->lr->gr_date;
                    break;
                }
            }
        }

        if ($grDateToCheck) {
            $grDate = \Carbon\Carbon::parse($grDateToCheck);
            $grMonth = $grDate->month;
            $grYear = $grDate->year;

            $submissionDay = config('payments.tds_submission_day', 3);

            $submissionDate = \Carbon\Carbon::create($grYear, $grMonth, 1)
                ->addMonth()
                ->day($submissionDay)
                ->startOfDay();

            $today = \Carbon\Carbon::now()->startOfDay();
            if ($today->gte($submissionDate)) {
                $canChangeTransporter = false;
            }
        }

        return view('lorry_receipts.edit', [
            'lorry_receipt' => $lorry_receipt,
            'trucks' => $trucks,
            'transporters' => $transporters,
            'canChangeTransporter' => $canChangeTransporter,
            'statuses' => $statuses,
            'customers' => $consignees,
            'consignors' => $consignees,
            'consignees' => $consignees,
            'goodsTypes' => $goodsTypes,
            'truckOwners' => $truckOwners,
            'agencies' => $agencies,
            'tdsPercentages' => $tdsPercentages,
        ]);
    }

    public function update(Request $request, LorryReceipt $lorry_receipt): \Illuminate\Http\RedirectResponse
    {
        $companyId = $this->getCurrentCompanyId();
        // dd($request->all());
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('lorry_receipts.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        if ($lorry_receipt->invoice_generated == 1) {
            return redirect()->route('lorry_receipts.index')->with('error', 'Cannot edit Good Receipt (Bilty) as invoice has already been generated.');
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

            return redirect()->route('lorry_receipts.index')->with('success', 'Good Receipt (Bilty) updated successfully! Only Memo ID could be updated as the LR is already delivered.');
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
            'goods_value' => 'nullable|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'is_fixed_amount' => 'nullable|boolean',
            'actual_amount' => 'required|numeric|min:0',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'transporter_type' => 'nullable|in:owner,agency',

        ]);
        $this->validateLorryReceiptUniquenessUpdate($validated, $lorry_receipt->id, $companyId);
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
        $this->forceAgencyForOrphanTruck($validated, $companyId);

        // Preserve existing delivery_status - don't override it
        // Remove delivery_status from validated data to keep existing value
        // unset($validated['delivery_status']);

        // Set unloading_weight = weight initially if not set (for loose weight mode)
        if (! isset($validated['unloading_weight']) || $validated['unloading_weight'] === null) {
            $validated['unloading_weight'] = $validated['weight'];
        }

        $isFixed = (bool) ($validated['is_fixed_amount'] ?? false);

        if ($isFixed) {
            $shortageAmount = $validated['shortage_amount'] ?? 0;
            $damageAmount = $validated['damage_amount'] ?? 0;
            $validated['amount'] = max(0, round($validated['actual_amount'] - $shortageAmount - $damageAmount, 2));
        } else {
            // Calculate amount and actual_amount using loose weight (min of loading/unloading weight)
            $loadingWeight = $validated['weight'];
            $unloadingWeight = $validated['unloading_weight'];

            // Only recalculate actual_amount if loading_weight != unloading_weight
            // If weights are equal, use the manually entered actual_amount from form if provided, otherwise calculate it
            if (abs($loadingWeight - $unloadingWeight) > 0.01) {
                // Weights are different - always recalculate actual_amount
                $effectiveWeight = min($loadingWeight, $unloadingWeight);
                $validated['actual_amount'] = round($effectiveWeight * $validated['fr_rate'], 2);
            } else {
                // Weights are equal - use manually entered actual_amount from form if provided, otherwise calculate it
                if (! isset($validated['actual_amount']) || $validated['actual_amount'] === null) {
                    $effectiveWeight = min($loadingWeight, $unloadingWeight);
                    $validated['actual_amount'] = round($effectiveWeight * $validated['fr_rate'], 2);
                }
            }

            // Calculate amount: actual_amount - shortage - damage
            $shortageAmount = $validated['shortage_amount'] ?? 0;
            $damageAmount = $validated['damage_amount'] ?? 0;
            $validated['amount'] = max(0, round($validated['actual_amount'] - $shortageAmount - $damageAmount, 2));
        }

        // Add company_id to validated data
        $validated['company_id'] = $companyId;

        // Store old values before update to detect changes
        $oldGoodType = $lorry_receipt->good_type;
        $oldTransporterId = $lorry_receipt->transporter_id;
        $oldLinkedLrId = $lorry_receipt->linked_lr_id;
        $oldDeliveryStatus = $lorry_receipt->delivery_status;
        $oldWeight = $lorry_receipt->weight;

        // Check if transporter is being changed and if TDS is submitted
        if (isset($validated['transporter_id']) && $validated['transporter_id'] != $oldTransporterId) {
            $freightBookings = \App\Models\FreightBooking::where('lr_id', $lorry_receipt->id)->get();

            foreach ($freightBookings as $freightBooking) {
                if (! $freightBooking->canChangeTdsAmount()) {
                    return redirect()->back()->withErrors([
                        'transporter_id' => 'Transporter cannot be changed because TDS has already been submitted for this good hire (lorry hire).',
                    ])->withInput();
                }
            }
        }

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

                    // Recalculate tds_amount based on loading_weight (not loose weight) and transporter TDS percentage
                    // Check if TDS can be changed before updating
                    if ($freightBooking->canChangeTdsAmount()) {
                        $loadingWeight = $lorry_receipt->weight;
                        $tdsBaseAmount = round($freightBooking->tr_fr_rate * $loadingWeight, 2);
                        $newTdsAmount = round($tdsBaseAmount * ($transporter->tds_percentage / 100), 2);
                        $updateData['tds_amount'] = $newTdsAmount;
                    }
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

            // Handle weight changes - recalculate tr_amount and tds_amount
            $weightChanged = isset($validated['weight']) && $validated['weight'] != $oldWeight;
            $unloadingWeightChanged = isset($validated['unloading_weight']) &&
                ($lorry_receipt->unloading_weight != $validated['unloading_weight']);

            if ($weightChanged || $unloadingWeightChanged) {
                // Refresh to get updated weight value after the update
                $lorry_receipt->refresh();
                $loadingWeight = $lorry_receipt->weight;
                $unloadingWeight = $lorry_receipt->unloading_weight ?? $loadingWeight;

                // Only recalculate tr_amount if loading_weight != unloading_weight
                // If weights are equal, keep the existing tr_amount (user may have manually edited it)
                if (abs($loadingWeight - $unloadingWeight) > 0.01) {
                    // Weights are different - always recalculate tr_amount
                    $effectiveWeight = min($loadingWeight, $unloadingWeight);
                    $newTrAmount = round($freightBooking->tr_fr_rate * $effectiveWeight, 2);
                    $updateData['tr_amount'] = $newTrAmount;
                }
                // If weights are equal, don't update tr_amount - use existing value (may be manually edited)

                // Recalculate TDS based on loading_weight (not loose weight) if loading weight changed
                if ($weightChanged && $freightBooking->canChangeTdsAmount()) {
                    $tdsBaseAmount = round($freightBooking->tr_fr_rate * $loadingWeight, 2);
                    $tdsPercentage = $freightBooking->transporter_tds_percentage ?? 0;
                    $newTdsAmount = round($tdsBaseAmount * ($tdsPercentage / 100), 2);
                    $updateData['tds_amount'] = $newTdsAmount;
                }
            }

            // Recalculate balance_amount if tr_amount changed
            if (isset($updateData['tr_amount'])) {
                $trAmount = $updateData['tr_amount'];
                $damageAmount = $freightBooking->damage_amount ?? 0;
                $shortageAmount = $freightBooking->shortage_amount ?? 0;

                // Use new tds_amount if it's being updated, otherwise use existing
                $tdsAmount = isset($updateData['tds_amount']) ? $updateData['tds_amount'] : ($freightBooking->tds_amount ?? 0);

                // Recalculate balance: tr_amount - advance_amount - tds_amount - damage_amount - shortage_amount + detention + deviation - misc_expense
                $newBalance = $trAmount
                    - $freightBooking->advance_amount
                    - $tdsAmount
                    - $damageAmount
                    - $shortageAmount
                    + ($freightBooking->detention ?? 0)
                    + ($freightBooking->deviation ?? 0)
                    - ($freightBooking->misc_expense ?? 0);

                $updateData['balance_amount'] = $newBalance;
            }

            // Recalculate balance_amount if tds_amount changed (but tr_amount didn't)
            if (isset($updateData['tds_amount']) && ! isset($updateData['tr_amount'])) {
                $trAmount = $freightBooking->tr_amount;
                $damageAmount = $freightBooking->damage_amount ?? 0;
                $shortageAmount = $freightBooking->shortage_amount ?? 0;
                $newTdsAmount = $updateData['tds_amount'];

                // Recalculate balance: tr_amount - advance_amount - tds_amount - damage_amount - shortage_amount + detention + deviation - misc_expense
                $newBalance = $trAmount
                    - $freightBooking->advance_amount
                    - $newTdsAmount
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

            }
        }

        if (isset($validated['delivery_status']) && $validated['delivery_status'] !== $oldDeliveryStatus) {
            $lorry_receipt->loadMissing(['company', 'truck', 'transporter.transporterMaster', 'customer']);
            $this->lrNotificationService->notifyStatusChange($lorry_receipt, $validated['delivery_status'], auth()->user());
        }

        return redirect()->route('lorry_receipts.index')->with('success', 'Good Receipt (Bilty) updated successfully!');
    }

    /**
     * Validate uniqueness of LR fields for update
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateLorryReceiptUniquenessUpdate(array $data, ?int $excludeId, int $companyId): void
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

    public function destroy(LorryReceipt $lorry_receipt): \Illuminate\Http\RedirectResponse
    {
        if ($redirect = $this->redirectIfPlanExpired()) {
            return $redirect;
        }

        try {
            DB::transaction(function () use ($lorry_receipt) {
                // This will trigger the deleting event in the model
                // which will handle the related FreightBookings and VoucherPayments
                $lorry_receipt->delete();
            });

            return redirect()->route('lorry_receipts.index')
                ->with('success', 'Good receipt (bilty) and all associated data deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting lorry receipt: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Error deleting good receipt (bilty). Please try again.');
        }
    }

    public function details(int $id): \Illuminate\Http\JsonResponse
    {
        $lorryReceipt = LorryReceipt::with([
            'truck',
            'transporter',
            'consignor',
            'consignee',
            'customer',
            'reference',
            'creator',
            'updater',
        ])->findOrFail($id);

        $response = $lorryReceipt->toArray();

        // If this LR has linked_lr_id, fetch and include linked LRs data
        if ($lorryReceipt->linked_lr_id) {
            $linkedLRs = LorryReceipt::with(['consignor', 'consignee', 'customer', 'transporter.transporterMaster', 'truck', 'reference.transporterMaster'])
                ->where('company_id', Auth::user()->company_id)
                ->where('linked_lr_id', $lorryReceipt->linked_lr_id)
                ->get();

            if ($linkedLRs->isNotEmpty()) {
                $response['linked_lrs_data'] = $this->buildLinkedLRsData($linkedLRs);
            }
        }

        return response()->json($response);
    }

    /**
     * Build linked LRs data structure
     */
    private function buildLinkedLRsData($linkedLRs): array
    {
        $common = [
            'transporter' => $this->getCommonValue($linkedLRs, 'transporter.name'),
            'truck_number' => $this->getCommonValue($linkedLRs, 'truck.truck_number'),
            'gr_date' => $this->getCommonValue($linkedLRs, 'gr_date'),
            'reference_by' => $this->getCommonValue($linkedLRs, 'reference.name'),
        ];

        $linkedDetails = $linkedLRs->map(function ($lr) {
            return [
                'id' => $lr->id,
                'lr_number' => $lr->lr_number,
                'gr_date' => $lr->gr_date,
                'status' => $lr->delivery_status,
                'customer' => $lr->customer?->name,
                'consignor' => $lr->consignor?->name,
                'consignee' => $lr->consignee?->name,
                'good_type' => $lr->good_type,
                'weight' => $lr->weight,
                'amount' => $lr->amount,
                'freight_booking_id' => $lr->freight_booking_id,
                'linked_lr_id' => $lr->linked_lr_id,
            ];
        });

        return [
            'common' => $common,
            'lrs' => $linkedDetails,
        ];
    }

    /**
     * Get common value if all items have the same value, otherwise null
     */
    private function getCommonValue($collection, string $key): mixed
    {
        $values = $collection->pluck($key)->unique();

        return $values->count() === 1 ? $values->first() : null;
    }

    public function getLorryReceiptsForCustomer(int $customerId): \Illuminate\Http\JsonResponse
    {
        $lorryReceipts = LorryReceipt::where('customer_id', $customerId)
            ->where('company_id', Auth::user()->company_id)
            ->get(['id', 'lr_number']);

        return response()->json($lorryReceipts);
    }

    public function getByLinkedId(int $linkedId): \Illuminate\Http\JsonResponse
    {
        try {

            $lrs = LorryReceipt::with(['truck', 'freightBookings'])
                ->where('company_id', Auth::user()->company_id)
                ->where('linked_lr_id', $linkedId)
                ->get()
                ->map(function ($lr) {
                    $lastFreight = $lr->freightBookings->last();

                    return [
                        'id' => $lr->id,
                        'lr_number' => $lr->lr_number,
                        'truck_number' => $lr->truck?->truck_number ?? '',
                        'freight_booking_id' => $lr->freight_booking_id,
                        'balance_amount' => $lastFreight ? (float) $lastFreight->balance_amount : 0,
                    ];
                })
                ->sortBy('balance_amount')
                ->values();

            return response()->json($lrs);
        } catch (\Exception $e) {
            Log::error('Error in getByLinkedId: '.$e->getMessage());

            return response()->json([
                'error' => 'An error occurred while fetching linked LRs',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
