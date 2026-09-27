<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\GoodType;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Services\LorryReceiptCalculationService;
use App\Services\Notifications\LorryReceiptNotificationService;
use App\Support\ListingQueryString;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Country;
use App\Models\TruckType;

class LorryReceiptController extends Controller
{
    public function __construct(
        private readonly LorryReceiptNotificationService $lrNotificationService,
        private readonly LorryReceiptCalculationService $lrCalculationService,
    ) {}

    private function redirectToLorryReceiptsIndex(?Request $request = null, ?string $flashKey = null, ?string $flashMessage = null): RedirectResponse
    {
        $request ??= request();

        $redirect = redirect()->route(
            'lorry_receipts.index',
            ListingQueryString::params($request, ListingQueryString::LORRY_RECEIPT_KEYS)
        );

        if ($flashKey !== null && $flashMessage !== null) {
            return $redirect->with($flashKey, $flashMessage);
        }

        return $redirect;
    }

    /**
     * Check if user's subscription plan is expired
     */
    private function isPlanExpired(): bool
    {
        $company = Auth::user()->company;
        $company?->loadMissing('subscription');

        return $company ? ! $company->subscriptionCanWrite() : false;
    }

    /**
     * Redirect if plan is expired
     */
    private function redirectIfPlanExpired(string $route = 'lorry_receipts.index'): ?RedirectResponse
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

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->getLorryReceiptsData($request);
        }

        $companyId = $this->getCurrentCompanyId();

        $selectedCustomer = $request->filled('customer_id')
            ? Consignee::where('company_id', $companyId)->find($request->customer_id)
            : null;

        $selectedTransporter = $request->filled('transporter_id')
            ? Transporter::where('company_id', $companyId)->with('transporterMaster')->find($request->transporter_id)
            : null;

        $selectedTruck = $request->filled('truck_id')
            ? Truck::where(function ($query) use ($companyId) {
                if ($companyId) {
                    $query->whereHas('transporter', function ($transporterQuery) use ($companyId) {
                        $transporterQuery->where('company_id', $companyId);
                    })->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
                }
            })->find($request->truck_id)
            : null;

        return view('lorry_receipts.index', compact('selectedCustomer', 'selectedTransporter', 'selectedTruck'));
    }

    private function getLorryReceiptsData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $user->loadMissing('userRole');
            $canActAsAdmin = $user->canActAsAdmin();

            $company = $user->company;
            $company?->loadMissing('subscription');
            $planExpired = $company ? ! $company->subscriptionCanWrite() : false;

            // Join consignees + truck once with aliases so DataTables never hydrates duplicate Consignee
            // models per row (customer / consignor / consignee are the same table).
            $query = LorryReceipt::query()
                ->from('lorry_receipts')
                ->leftJoin('consignees as lr_dt_customer', 'lr_dt_customer.id', '=', 'lorry_receipts.customer_id')
                ->leftJoin('consignees as lr_dt_consignor', 'lr_dt_consignor.id', '=', 'lorry_receipts.consignor_id')
                ->leftJoin('consignees as lr_dt_consignee', 'lr_dt_consignee.id', '=', 'lorry_receipts.consignee_id')
                ->leftJoin('trucks as lr_dt_truck', 'lr_dt_truck.id', '=', 'lorry_receipts.truck_id')
                ->select([
                    'lorry_receipts.*',
                    'lr_dt_customer.name as dt_customer_name',
                    'lr_dt_consignor.name as dt_consignor_name',
                    'lr_dt_consignee.name as dt_consignee_name',
                    'lr_dt_truck.truck_number as dt_truck_number',
                ])
                ->with([
                    'freightBooking',
                ]);

            if ($user->company_id) {
                $query->where('lorry_receipts.company_id', $user->company_id);
            }
            $query->orderBy('lorry_receipts.gr_date', 'desc')->orderBy('lorry_receipts.linked_lr_id', 'desc');
            $query->when($request->filled('gr_date_from') && $request->filled('gr_date_to'), function ($query) use ($request) {
                $query->whereBetween('lorry_receipts.gr_date', [$request->gr_date_from, $request->gr_date_to]);
            });
            $query->when($request->filled('lr_number'), function ($query) use ($request) {
                $query->where('lorry_receipts.lr_number', 'like', '%'.trim($request->lr_number).'%');
            });
            $query->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('lorry_receipts.customer_id', $request->customer_id);
            });
            $query->when($request->filled('transporter_id'), function ($query) use ($request) {
                $query->where('lorry_receipts.transporter_id', $request->transporter_id);
            });
            $query->when($request->filled('truck_id'), function ($query) use ($request) {
                $query->where('lorry_receipts.truck_id', $request->truck_id);
            });

            return DataTables::of($query)
                ->orderColumn('customer_name', 'lr_dt_customer.name $1')
                ->orderColumn('consignor_name', 'lr_dt_consignor.name $1')
                ->orderColumn('consignee_name', 'lr_dt_consignee.name $1')
                ->orderColumn('truck_number', 'lr_dt_truck.truck_number $1')
                ->filterColumn('customer_name', function ($query, $keyword): void {
                    if ($keyword === '' || $keyword === null) {
                        return;
                    }
                    $query->where('lr_dt_customer.name', 'like', '%'.$keyword.'%');
                })
                ->filterColumn('consignor_name', function ($query, $keyword): void {
                    if ($keyword === '' || $keyword === null) {
                        return;
                    }
                    $query->where('lr_dt_consignor.name', 'like', '%'.$keyword.'%');
                })
                ->filterColumn('consignee_name', function ($query, $keyword): void {
                    if ($keyword === '' || $keyword === null) {
                        return;
                    }
                    $query->where('lr_dt_consignee.name', 'like', '%'.$keyword.'%');
                })
                ->filterColumn('truck_number', function ($query, $keyword): void {
                    if ($keyword === '' || $keyword === null) {
                        return;
                    }
                    $query->where('lr_dt_truck.truck_number', 'like', '%'.$keyword.'%');
                })
                ->filterColumn('lr_number', function ($query, $keyword): void {
                    if ($keyword === '' || $keyword === null) {
                        return;
                    }
                    $query->where('lorry_receipts.lr_number', 'like', '%'.$keyword.'%');
                })
                ->orderColumn('gr_date', 'lorry_receipts.gr_date $1')
                ->orderColumn('lr_number', 'lorry_receipts.lr_number $1')
                ->orderColumn('weight', 'lorry_receipts.weight $1')
                ->orderColumn('fr_rate', 'lorry_receipts.fr_rate $1')
                ->orderColumn('amount', 'lorry_receipts.amount $1')
                ->orderColumn('delivery_status', 'lorry_receipts.delivery_status $1')
                ->addColumn('sr_no', function ($row) {
                    static $counter = 0;

                    return ++$counter;
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->dt_customer_name ?: '-';
                })
                ->addColumn('consignor_name', function ($row) {
                    return $row->dt_consignor_name ?: '-';
                })
                ->addColumn('consignee_name', function ($row) {
                    return $row->dt_consignee_name ?: '-';
                })
                ->addColumn('truck_number', function ($row) {
                    return $row->dt_truck_number ?: '-';
                })
                ->addColumn('gr_date_formatted', function ($row) {
                    return $row->gr_date ? date('d-m-Y', strtotime($row->gr_date)) : '-';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return $row->amount ? ''.number_format($row->amount, 2) : '0.00';
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

                ->addColumn('actions', function ($row) use ($company, $canActAsAdmin, $request) {
                    $listingQuerySuffix = ListingQueryString::suffix($request, ListingQueryString::LORRY_RECEIPT_KEYS);

                    return $this->getActionButtons($row, $company, $canActAsAdmin, $listingQuerySuffix);
                })
                ->rawColumns(['sr_no', 'lr_number', 'customer_name', 'consignor_name', 'consignee_name', 'truck_number', 'gr_date_formatted', 'amount_formatted', 'status_badge', 'actions', 'row_class'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('DataTables error: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getActionButtons(LorryReceipt $lorryReceipt, Company $company, bool $canActAsAdmin, string $listingQuerySuffix = ''): string
    {
        $buttons = '<div class="app-index-table-actions">';

        $freight_booking_id = $lorryReceipt->freight_booking_id;
        $freightBooking = $lorryReceipt->freightBooking;
        $planExpired = $company ? ! $company->subscriptionCanWrite() : false;

        if ($freight_booking_id) {
            $buttons .= '<a href="'.route('freight_bookings.edit', $freight_booking_id).$listingQuerySuffix.'" class="btn btn-sm btn-icon btn-action-complete" title="Edit Good Hire (Lorry Hire)" data-bs-toggle="tooltip">';
            $buttons .= '<i class="bi bi-truck"></i></a>';
        } else {
            if ($company->lrCreditsRemaining() <= 0) {
                $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-action-add" title="Insufficient Credits" data-bs-toggle="tooltip" disabled onclick="alert(\'Insufficient credits\')">';
                $buttons .= '<i class="bi bi-truck"></i></button>';
            } elseif ($planExpired) {
                $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-action-add" title="Plan Expired" data-bs-toggle="tooltip" disabled onclick="alert(\'Plan expired\')">';
                $buttons .= '<i class="bi bi-truck"></i></button>';
            } else {
                $buttons .= '<a href="'.route('freight_bookings.create').'?lorry_receipt_id='.$lorryReceipt->id.'" class="btn btn-sm btn-icon btn-action-add" title="Create Good Hire (Lorry Hire)" data-bs-toggle="tooltip">';
                $buttons .= '<i class="bi bi-truck"></i></a>';
            }
        }

        // LR Copy button
        $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-action-bilty lr-copy-btn" title="LR Copy" data-bs-toggle="modal" data-bs-target="#lrCopyModal" data-lr-id="'.$lorryReceipt->id.'">';
        $buttons .= '<i class="bi bi-file-earmark-text"></i></button>';

        // Edit button
        $editUrl = route('lorry_receipts.edit', $lorryReceipt->id).$listingQuerySuffix;
        if ($lorryReceipt->invoice_generated == 0 && $canActAsAdmin) {
            $confirmMsg = $freightBooking ? ' onclick="return confirm(\'A good hire (lorry hire) already exists. Continue?\')"' : '';
            $buttons .= '<a href="'.$editUrl.'" class="btn btn-sm btn-icon btn-action-edit" title="Edit" data-bs-toggle="tooltip"'.$confirmMsg.'>';
            $buttons .= '<i class="bi bi-pencil"></i></a>';
        }
if ($lorryReceipt->delivery_status === 'Pending' && $canActAsAdmin) {
    $deleteUrl = route('lorry_receipts.destroy', $lorryReceipt->id);

    $buttons .= '<form action="'.$deleteUrl.'" method="POST" class="delete-lr-form">';
    $buttons .= csrf_field().method_field('DELETE');

    $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-action-delete delete-lr-btn"
        title="Delete"
        data-bs-toggle="tooltip"
        data-id="'.e($lorryReceipt->id).'"
        data-lr-number="'.e($lorryReceipt->lr_number).'"
        data-truck-number="'.e($lorryReceipt->truck->truck_number ?? 'N/A').'"
        data-customer="'.e($lorryReceipt->customer->name ?? 'N/A').'"
        data-consignor="'.e($lorryReceipt->consignor->name ?? 'N/A').'"
        data-consignee="'.e($lorryReceipt->consignee->name ?? 'N/A').'">
        <i class="bi bi-trash"></i>
    </button>';

    $buttons .= '</form>';
}

$buttons .= '</div>';

return $buttons;
}
    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPlanExpired()) {
            return $redirect;
        }

        $companyId = $this->getCurrentCompanyId();
        $countries = Country::orderBy('name')->get();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $truckTypes = TruckType::orderBy('name')->get();

        // Only fetch selected IDs (if any) to avoid loading large dropdown datasets.
        $selectedConsigneeIds = collect([
            old('customer_id'),
            old('consignor_id'),
            old('consignee_id'),
        ])->filter()->unique()->values();

        $consignees = $selectedConsigneeIds->isNotEmpty()
            ? Consignee::withTrashed()
                ->with('city')
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
            ? Transporter::withTrashed()
                ->with('transporterMaster')
                ->where('is_owner', 0)
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedReferenceById)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $selectedTransporterId = old('transporter_id');
        $payeeTransporter = $selectedTransporterId
            ? Transporter::withTrashed()
                ->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('id', $selectedTransporterId)
                ->first()
            : null;

        return view('lorry_receipts.create', compact(
            'trucks',
            'consignees',
            'goodsTypes',
            'agencies',
            'payeeTransporter',
            'countries',
            'truckTypes'
        ));
    }

    public function createLinked(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPlanExpired()) {
            return $redirect;
        }

        $companyId = $this->getCurrentCompanyId();
        $countries = Country::orderBy('name')->get();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $truckTypes = TruckType::orderBy('name')->get();

        // Only fetch selected IDs (if any) to avoid loading large dropdown datasets.
        $selectedConsigneeIds = collect([
            old('customer_id'),
            old('consignor_id'),
            old('consignee_id'),
        ])->filter()->unique()->values();

        $consignees = $selectedConsigneeIds->isNotEmpty()
            ? Consignee::withTrashed()
                ->with('city')
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
            ? Transporter::withTrashed()
                ->with('transporterMaster')
                ->where('is_owner', 0)
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedReferenceById)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $selectedTransporterId = old('transporter_id');
        $payeeTransporter = $selectedTransporterId
            ? Transporter::withTrashed()
                ->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('id', $selectedTransporterId)
                ->first()
            : null;

        return view('lorry_receipts.create-linked', compact(
            'trucks',
            'consignees',
            'goodsTypes',
            'agencies',
            'payeeTransporter',
            'countries',
            'truckTypes'
        ));
    }

    /**
     * Bulk update memo_id and delivery status for multiple lorry receipts
     */
    public function bulkPayment(Request $request): JsonResponse
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
                FreightBooking::whereIn('id', $freightBookingIds)
                    ->update(['is_delivered' => 1]);

                // Update lorry receipts
                foreach ($lorryReceipts as $lr) {
                    $oldDeliveryStatus = $lr->delivery_status;
                    $lr->memo_id = $validated['memo_id'];
                    if (empty($lr->memo_created_at)) {
                      $lr->memo_created_at = now();
                   }
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
        } catch (ValidationException $e) {
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

    public function store(Request $request): RedirectResponse
    {

        $companyId = $this->getCurrentCompanyId();
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
                'lrs' => 'required|array|min:2',
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
                'lrs.*.loading_station' => 'nullable|string|max:100',
                'lrs.*.unloading_station' => 'nullable|string|max:100',
                'lrs.*.lane' => 'nullable|string|max:100',
                'lrs.*.good_type' => 'nullable|string|max:100',
                'lrs.*.goods_value' => 'nullable|numeric|min:0',
                'lrs.*.weight' => 'nullable|numeric|min:0',
                'lrs.*.no_of_units' => 'nullable|numeric|min:1|max:100000',
                'lrs.*.fr_rate' => 'nullable|numeric|min:1|max:20000',
                'lrs.*.is_fixed_amount' => 'nullable|boolean',
                'lrs.*.amount' => 'nullable|numeric|min:0',
            ];
            $this->validateLorryReceiptsUniqueness($request->lrs, $companyId);

            $validated = $request->validate(array_merge($sharedRules, $perItemRules), [
                'lrs.required' => 'A linked group needs at least 2 lorry receipts.',
                'lrs.min' => 'A linked group needs at least 2 lorry receipts.',
            ]);
            $this->forceAgencyForOrphanTruck($validated, $companyId);

            // Start DB transaction
            DB::beginTransaction();
            try {
                // Next linked_lr_id (increment previous)
                // $nextLinkedId = (\App\Models\LorryReceipt::max('linked_lr_id') ?? 0) + 1;
                $nextLinkedId = (LorryReceipt::where('company_id', $companyId)->max('linked_lr_id') ?? 0) + 1;

                $created = [];
                foreach ($validated['lrs'] as $item) {
                    // Prevent consignor == consignee per LR
                    if ($item['consignor_id'] === $item['consignee_id']) {
                        throw ValidationException::withMessages([
                            'lrs' => ['Consignor and Consignee cannot be the same for LR '.($item['lr_number'] ?? '')],
                        ]);
                    }

                    $isFixed = (bool) ($item['is_fixed_amount'] ?? false);
                    $itemWeight = isset($item['weight']) && $item['weight'] !== null ? (float) $item['weight'] : 0.0;
                    $itemFrRate = isset($item['fr_rate']) && $item['fr_rate'] !== null ? (float) $item['fr_rate'] : 0.0;
                    $amountInput = isset($item['amount']) && $item['amount'] !== null ? (float) $item['amount'] : 0.0;

                    $amounts = $this->lrCalculationService->resolveForStore(
                        $isFixed,
                        $itemWeight,
                        $itemFrRate,
                        $amountInput
                    );

                    $sourceCity = $item['source_city'] ?? null;
                    $sourceState = $item['source_state'] ?? null;
                    if (empty($sourceCity) && ! empty($item['consignor_id'])) {
                        $consignorParty = Consignee::with(['city', 'state'])->find($item['consignor_id']);
                        $sourceCity = $consignorParty?->city?->name ?? 'N/A';
                        $sourceState = $consignorParty?->state?->name ?? 'N/A';
                    }

                    $destCity = $item['destination_city'] ?? null;
                    $destState = $item['destination_state'] ?? null;
                    if (empty($destCity) && ! empty($item['consignee_id'])) {
                        $consigneeParty = Consignee::with(['city', 'state'])->find($item['consignee_id']);
                        $destCity = $consigneeParty?->city?->name ?? 'N/A';
                        $destState = $consigneeParty?->state?->name ?? 'N/A';
                    }

                    $data = [
                        'lr_number' => $item['lr_number'],
                        'gr_number' => $item['gr_number'],
                        'invoice_number' => $item['invoice_number'],
                        'customer_id' => $item['customer_id'],
                        'consignor_id' => $item['consignor_id'],
                        'consignor_contact_number' => $item['consignor_contact_number'] ?? null,
                        'consignee_id' => $item['consignee_id'],
                        'consignee_contact_number' => $item['consignee_contact_number'] ?? null,
                        'source_city' => $sourceCity ?? 'N/A',
                        'source_state' => $sourceState ?? 'N/A',
                        'destination_city' => $destCity ?? 'N/A',
                        'destination_state' => $destState ?? 'N/A',
                        'loading_station' => $item['loading_station'] ?? null,
                        'unloading_station' => $item['unloading_station'] ?? null,
                        'lane' => $item['lane'] ?? null,
                        'good_type' => $item['good_type'] ?? null,
                        'goods_value' => $item['goods_value'] ?? null,
                        'weight' => $item['weight'],
                        'unloading_weight' => $itemWeight,
                        'no_of_units' => $item['no_of_units'] ?? null,
                        'fr_rate' => $item['fr_rate'],
                        'is_fixed_amount' => $isFixed,
                        'amount' => $amounts['amount'],
                        'actual_amount' => $amounts['actual_amount'],
                        'damage_amount' => 0,
                        'shortage_amount' => 0,
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
                    $created[] = LorryReceipt::create($data);
                }

                DB::commit();

                return $this->redirectToLorryReceiptsIndex($request, 'success', 'Created '.count($created).' linked Good Receipts (Bilty) (Linked ID: '.$nextLinkedId.').');
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }

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
            ],
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'reference_by' => 'nullable|exists:transporters,id',
            'consignor_id' => 'required|exists:consignees,id',
            'consignor_contact_number' => 'nullable|string|max:20',
            'consignee_id' => 'required|exists:consignees,id',
            'consignee_contact_number' => 'nullable|string|max:20',
            'gr_date' => 'required|date',

            'source_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'loading_station' => 'nullable|string|max:100',
            'unloading_station' => 'nullable|string|max:100',
            'lane' => 'required|string|max:100',
            'good_type' => 'required|string|max:100',
            'goods_value' => 'nullable|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'is_fixed_amount' => 'nullable|boolean',
            'amount' => 'required|numeric|min:0',
            'transporter_type' => 'nullable|in:owner,agency',
        ]);

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

        // Set default delivery_status
        $validated['delivery_status'] = 'Pending';
        $validated['company_id'] = $companyId;
        $validated['damage_amount'] = 0;
        $validated['shortage_amount'] = 0;

        // Check if lr_number already exists
        if ($validated['gr_number'] == null) {
            // Remove "LR" prefix, then trim leading zeros
            $validated['gr_number'] = ltrim(str_ireplace('LR', '', $validated['lr_number']), '0');
        }
        if (LorryReceipt::where('lr_number', $validated['lr_number'])
            ->where('company_id', $companyId)
            ->exists()
        ) {
            return redirect()->back()->withErrors(['lr_number' => 'LR Number already exists.']);
        }

        // Set unloading_weight = weight initially (for loose weight mode)
        if (! isset($validated['unloading_weight']) || $validated['unloading_weight'] === null) {
            $validated['unloading_weight'] = $validated['weight'];
        }

        $isFixed = (bool) ($validated['is_fixed_amount'] ?? false);

        $resolved = $this->lrCalculationService->resolveForStore(
            $isFixed,
            (float) $validated['weight'],
            (float) $validated['fr_rate'],
            (float) $validated['amount'],
        );
        $validated['actual_amount'] = $resolved['actual_amount'];
        $validated['amount'] = $resolved['amount'];

        $lorryReceipt = LorryReceipt::create($validated);

        // Check if user clicked "Save & Create FR"
        if ($request->has('redirect_to_fr')) {
            return redirect()->route('freight_bookings.create', ['lorry_receipt_id' => $lorryReceipt->id])
                ->with('success', 'Good Receipt (Bilty) saved! Now create Good Hire (Lorry Hire).');
        }

        return $this->redirectToLorryReceiptsIndex($request, 'success', 'Good Receipt (Bilty) created successfully!');
    }

    /**
     * Validate uniqueness of LR numbers and GR numbers.
     *
     * @throws ValidationException
     */
    private function validateLorryReceiptsUniqueness(array $lrs, int $companyId, array $excludeIds = []): void
    {
        $errors = [];
        $lrNumbers = array_filter(array_column($lrs, 'lr_number'));
        $grNumbers = array_filter(array_column($lrs, 'gr_number'));
        $excludeIds = array_values(array_filter(array_map('intval', $excludeIds)));

        // Check LR_NUMBER uniqueness (Company-wise)
        if (! empty($lrNumbers)) {
            $duplicateLrs = LorryReceipt::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
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
                ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
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

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function editLinkedLorryReceipts(LorryReceipt $lorry_receipt): View|RedirectResponse
    {
        $companyId = $this->getCurrentCompanyId();

        $linkedLorryReceipts = LorryReceipt::query()
            ->with(['truck.transporter.transporterMaster', 'truck.driver', 'customer.city', 'transporter.transporterMaster', 'consignor.city', 'consignee.city', 'reference.transporterMaster'])
            ->where('company_id', $companyId)
            ->where('linked_lr_id', $lorry_receipt->linked_lr_id)
            ->orderBy('id')
            ->get();

        if ($linkedLorryReceipts->isEmpty()) {
            return $this->redirectToLorryReceiptsIndex(null, 'error', 'Linked good receipts (bilty) not found.');
        }

        if ($linkedLorryReceipts->contains(fn (LorryReceipt $lr): bool => (int) $lr->invoice_generated === 1)) {
            return $this->redirectToLorryReceiptsIndex(null, 'error', 'Cannot edit linked good receipts (bilty) because an invoice has already been generated for one or more items.');
        }

        if ($linkedLorryReceipts->contains(fn (LorryReceipt $lr): bool => $lr->delivery_status === 'Delivered')) {
            return $this->redirectToLorryReceiptsIndex(null, 'error', 'Cannot edit linked good receipts (bilty) in bulk when one or more items are already delivered.');
        }

        $sharedLorryReceipt = $linkedLorryReceipts->first();
        $countries = Country::orderBy('name')->get();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $truckTypes = TruckType::orderBy('name')->get();

        $selectedConsigneeIds = $linkedLorryReceipts
            ->flatMap(fn (LorryReceipt $lr): array => [$lr->customer_id, $lr->consignor_id, $lr->consignee_id])
            ->merge(collect(old('lrs', []))->flatMap(fn (array $item): array => [
                $item['customer_id'] ?? null,
                $item['consignor_id'] ?? null,
                $item['consignee_id'] ?? null,
            ]))
            ->filter()
            ->unique()
            ->values();

        $consignees = $selectedConsigneeIds->isNotEmpty()
            ? Consignee::withTrashed()
                ->with('city')
                ->where('company_id', $companyId)
                ->whereIn('id', $selectedConsigneeIds)
                ->orderBy('name')
                ->get()
            : collect();

        $selectedTruckId = old('truck_id', $sharedLorryReceipt->truck_id);
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

        $selectedReferenceById = old('reference_by', $sharedLorryReceipt->reference_by);
        $agencies = $selectedReferenceById
            ? Transporter::withTrashed()
                ->with('transporterMaster')
                ->where('is_owner', 0)
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedReferenceById)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $selectedTransporterId = old('transporter_id', $sharedLorryReceipt->transporter_id);
        $payeeTransporter = $selectedTransporterId
            ? Transporter::withTrashed()
                ->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('id', $selectedTransporterId)
                ->first()
            : null;

        $canChangeTransporter = $this->canChangeTransporterForLorryReceipt($sharedLorryReceipt);
        $tdsPercentages = [
            'default' => config('payments.tds_percentages.default', 20),
            'agency' => config('payments.tds_percentages.agency', 2),
            'owner' => config('payments.tds_percentages.owner', 1),
        ];

        $linkedLrsPayload = $linkedLorryReceipts->map(function (LorryReceipt $lr): array {
            return [
                'id' => $lr->id,
                'lr_number' => $lr->lr_number,
                'gr_number' => $lr->gr_number,
                'invoice_number' => $lr->invoice_number,
                'customer_id' => $lr->customer_id,
                'customer_name' => $lr->customer?->displayNameWithCity(),
                'consignor_id' => $lr->consignor_id,
                'consignor_name' => $lr->consignor?->displayNameWithCity(),
                'consignor_contact_number' => $lr->consignor_contact_number,
                'consignee_id' => $lr->consignee_id,
                'consignee_name' => $lr->consignee?->displayNameWithCity(),
                'consignee_contact_number' => $lr->consignee_contact_number,
                'source_city' => $lr->source_city,
                'source_state' => $lr->source_state,
                'destination_city' => $lr->destination_city,
                'destination_state' => $lr->destination_state,
                'loading_station' => $lr->loading_station,
                'unloading_station' => $lr->unloading_station,
                'good_type' => $lr->good_type,
                'no_of_units' => $lr->no_of_units,
                'goods_value' => $lr->goods_value,
                'weight' => $lr->weight,
                'fr_rate' => $lr->fr_rate,
                'is_fixed_amount' => $lr->is_fixed_amount,
                'amount' => $lr->amount,
            ];
        })->values();

        return view('lorry_receipts.edit-linked', [
            'lorry_receipt' => $lorry_receipt,
            'linkedLorryReceipts' => $linkedLorryReceipts,
            'linkedLrsPayload' => $linkedLrsPayload,
            'linkedLrId' => $lorry_receipt->linked_lr_id,
            'sharedLorryReceipt' => $sharedLorryReceipt,
            'trucks' => $trucks,
            'consignees' => $consignees,
            'goodsTypes' => $goodsTypes,
            'agencies' => $agencies,
            'payeeTransporter' => $payeeTransporter,
            'canChangeTransporter' => $canChangeTransporter,
            'tdsPercentages' => $tdsPercentages,
            'countries' => $countries,
            'truckTypes' => $truckTypes,
        ]);
    }

    private function updateLinkedLorryReceipts(Request $request, LorryReceipt $lorry_receipt, ?int $companyId): RedirectResponse
    {
        if (! $lorry_receipt->linked_lr_id) {
            return $this->redirectToLorryReceiptsIndex($request, 'error', 'This good receipt (bilty) is not part of a linked group.');
        }

        $linkedLorryReceipts = LorryReceipt::query()
            ->where('company_id', $companyId)
            ->where('linked_lr_id', $lorry_receipt->linked_lr_id)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        if ($linkedLorryReceipts->isEmpty()) {
            return $this->redirectToLorryReceiptsIndex($request, 'error', 'Linked good receipts (bilty) not found.');
        }

        if ($linkedLorryReceipts->contains(fn (LorryReceipt $lr): bool => (int) $lr->invoice_generated === 1)) {
            return $this->redirectToLorryReceiptsIndex($request, 'error', 'Cannot edit linked good receipts (bilty) because an invoice has already been generated for one or more items.');
        }

        if ($linkedLorryReceipts->contains(fn (LorryReceipt $lr): bool => $lr->delivery_status === 'Delivered')) {
            return $this->redirectToLorryReceiptsIndex($request, 'error', 'Cannot edit linked good receipts (bilty) in bulk when one or more items are already delivered.');
        }

        $sharedRules = [
            'gr_date' => 'required|date',
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_type' => 'nullable|in:owner,agency',
            'transporter_id' => 'nullable|exists:transporters,id',
            'reference_by' => 'nullable|exists:transporters,id',
            'company_id' => 'required|exists:companies,id',
        ];

        $perItemRules = [
            'lrs' => 'required|array|min:1',
            'lrs.*.id' => 'required|integer|exists:lorry_receipts,id',
            'lrs.*.lr_number' => ['required', 'string', 'max:100', 'distinct'],
            'lrs.*.gr_number' => ['nullable', 'string', 'max:100', 'distinct'],
            'lrs.*.invoice_number' => ['nullable', 'string', 'max:256'],
            'lrs.*.customer_id' => 'required|exists:consignees,id',
            'lrs.*.consignor_id' => 'required|exists:consignees,id',
            'lrs.*.consignor_contact_number' => 'nullable|string|max:20',
            'lrs.*.consignee_id' => 'required|exists:consignees,id',
            'lrs.*.consignee_contact_number' => 'nullable|string|max:20',
            'lrs.*.source_city' => 'required|string|max:100',
            'lrs.*.source_state' => 'required|string|max:100',
            'lrs.*.destination_city' => 'required|string|max:100',
            'lrs.*.destination_state' => 'required|string|max:100',
            'lrs.*.loading_station' => 'nullable|string|max:100',
            'lrs.*.unloading_station' => 'nullable|string|max:100',
            'lrs.*.lane' => 'required|string|max:100',
            'lrs.*.good_type' => 'required|string|max:100',
            'lrs.*.goods_value' => 'nullable|numeric|min:0',
            'lrs.*.weight' => 'required|numeric|min:0',
            'lrs.*.no_of_units' => 'nullable|numeric|min:1|max:100000',
            'lrs.*.fr_rate' => 'required|numeric|min:1|max:20000',
            'lrs.*.is_fixed_amount' => 'nullable|boolean',
            'lrs.*.amount' => 'required|numeric|min:0',
        ];

        $excludeIds = collect($request->input('lrs', []))
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $this->validateLorryReceiptsUniqueness($request->input('lrs', []), (int) $companyId, $excludeIds);

        $validated = $request->validate(array_merge($sharedRules, $perItemRules));
        $this->forceAgencyForOrphanTruck($validated, (int) $companyId);

        foreach ($validated['lrs'] as $item) {
            $existing = $linkedLorryReceipts->get((int) $item['id']);

            if ($existing === null || (int) $existing->linked_lr_id !== (int) $lorry_receipt->linked_lr_id) {
                throw ValidationException::withMessages([
                    'lrs' => ['One or more linked good receipts (bilty) do not belong to this linked group.'],
                ]);
            }

            if ($item['consignor_id'] === $item['consignee_id']) {
                throw ValidationException::withMessages([
                    'lrs' => ['Consignor and Consignee cannot be the same for LR '.($item['lr_number'] ?? '')],
                ]);
            }
        }

        DB::beginTransaction();

        try {
            foreach ($validated['lrs'] as $item) {
                /** @var LorryReceipt $existing */
                $existing = $linkedLorryReceipts->get((int) $item['id']);

                $isFixed = (bool) ($item['is_fixed_amount'] ?? false);
                $itemWeight = (float) $item['weight'];
                $itemFrRate = (float) $item['fr_rate'];
                $amountInput = (float) $item['amount'];
                // Before delivery, loading and unloading weight must stay the same.
                $itemUnloadingWeight = $itemWeight;

                $resolved = $this->lrCalculationService->resolveForUpdate(
                    $isFixed,
                    $itemWeight,
                    $itemUnloadingWeight,
                    $itemFrRate,
                    $amountInput,
                    (float) ($existing->shortage_amount ?? 0),
                    (float) ($existing->damage_amount ?? 0),
                );

                if ($item['gr_number'] === null || $item['gr_number'] === '') {
                    $item['gr_number'] = ltrim(str_ireplace('LR', '', $item['lr_number']), '0');
                }

                $updateData = [
                    'lr_number' => $item['lr_number'],
                    'gr_number' => $item['gr_number'],
                    'invoice_number' => $item['invoice_number'],
                    'customer_id' => $item['customer_id'],
                    'consignor_id' => $item['consignor_id'],
                    'consignor_contact_number' => $item['consignor_contact_number'] ?? null,
                    'consignee_id' => $item['consignee_id'],
                    'consignee_contact_number' => $item['consignee_contact_number'] ?? null,
                    'source_city' => $item['source_city'],
                    'source_state' => $item['source_state'],
                    'destination_city' => $item['destination_city'],
                    'destination_state' => $item['destination_state'],
                    'loading_station' => $item['loading_station'] ?? null,
                    'unloading_station' => $item['unloading_station'] ?? null,
                    'lane' => $item['lane'],
                    'good_type' => $item['good_type'],
                    'goods_value' => $item['goods_value'] ?? null,
                    'weight' => $itemWeight,
                    'unloading_weight' => $itemUnloadingWeight,
                    'no_of_units' => $item['no_of_units'] ?? null,
                    'fr_rate' => $itemFrRate,
                    'is_fixed_amount' => $isFixed,
                    'amount' => $resolved['amount'],
                    'actual_amount' => $resolved['actual_amount'],
                    'gr_date' => $validated['gr_date'],
                    'truck_id' => $validated['truck_id'] ?? null,
                    'transporter_type' => $validated['transporter_type'] ?? null,
                    'transporter_id' => $validated['transporter_id'] ?? null,
                    'reference_by' => $validated['reference_by'] ?? null,
                    'company_id' => $companyId,
                ];

                $freightBooking = FreightBooking::find($existing->freight_booking_id);
                if ($freightBooking) {
                    if (! $freightBooking->canChangeTdsAmount()
                        && isset($updateData['transporter_id'])
                        && (int) $updateData['transporter_id'] !== (int) $existing->transporter_id) {
                        throw ValidationException::withMessages([
                            'transporter_id' => 'Transporter cannot be changed because TDS has already been submitted for one or more linked good hires (lorry hires).',
                        ]);
                    }

                    $this->lrCalculationService->syncFreightBookingsForUpdatedLorryReceipt(
                        $existing,
                        $freightBooking,
                        array_merge($updateData, ['transporter_id' => $updateData['transporter_id']])
                    );
                }

                $existing->update($updateData);
            }

            DB::commit();

            return $this->redirectToLorryReceiptsIndex(
                $request,
                'success',
                'Updated '.count($validated['lrs']).' linked Good Receipts (Bilty) (Linked ID: '.$lorry_receipt->linked_lr_id.').'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function canChangeTransporterForLorryReceipt(LorryReceipt $lorryReceipt): bool
    {
        $grDateToCheck = $lorryReceipt->gr_date;

        if (! $grDateToCheck) {
            $freightBooking = FreightBooking::query()->where('lr_id', $lorryReceipt->id)->first();
            $grDateToCheck = $freightBooking?->lr?->gr_date;
        }

        if (! $grDateToCheck) {
            return true;
        }

        $grDate = Carbon::parse($grDateToCheck);
        $submissionDay = config('payments.tds_submission_day', 3);
        $submissionDate = Carbon::create($grDate->year, $grDate->month, 1)
            ->addMonth()
            ->day($submissionDay)
            ->startOfDay();

        return ! Carbon::now()->startOfDay()->gte($submissionDate);
    }

    public function checkLrNumberUniqueness(Request $request): JsonResponse
    {
        $request->validate([
            'lr_number' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = LorryReceipt::where('lr_number', $request->lr_number)
            ->where('company_id', $this->getCurrentCompanyId())
            ->when($request->exclude_id, fn ($query) => $query->where('id', '!=', $request->exclude_id))
            ->exists();

        return response()->json([
            'valid' => ! $exists,
            'message' => $exists
                ? 'LR Number already exists.'
                : 'LR Number is available',
        ]);
    }

    public function checkInvoiceNumberUniqueness(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_number' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        // Invoice numbers are intentionally not unique on lorry receipts.
        return response()->json([
            'valid' => true,
            'message' => 'Invoice Number is available',
        ]);
    }

    public function checkGRNumberUniqueness(Request $request): JsonResponse
    {
        $request->validate([
            'gr_number' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = LorryReceipt::where('gr_number', $request->gr_number)
            ->where('company_id', $this->getCurrentCompanyId())
            ->when($request->exclude_id, fn ($query) => $query->where('id', '!=', $request->exclude_id))
            ->exists();

       
        return response()->json([
    'available' => ! $exists,
    'message' => $exists
        ? 'GR number already exists'
        : 'GR number is available',
]);
    }

    public function edit(LorryReceipt $lorry_receipt): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPlanExpired()) {
            return $redirect;
        }

        if ($lorry_receipt->linked_lr_id) {
            return $this->editLinkedLorryReceipts($lorry_receipt);
        }

        $companyId = $this->getCurrentCompanyId();
        $lorry_receipt->load(['truck.transporter.transporterMaster', 'truck.driver', 'customer.city', 'transporter.transporterMaster', 'consignor.city', 'consignee.city']);

        // Fetch only the selected records needed for initial dropdown rendering.
        $selectedTruckId = old('truck_id', $lorry_receipt->truck_id);
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

        $selectedTransporterId = old('transporter_id', $lorry_receipt->transporter_id);
        $transporters = $selectedTransporterId
            ? Transporter::withTrashed()->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedTransporterId)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $effectiveTransporterType = old('transporter_type', $lorry_receipt->transporter_type);
        $truckOwners = ($effectiveTransporterType === 'owner' && $selectedTransporterId)
            ? Transporter::withTrashed()->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('transporters.id', $selectedTransporterId)
                ->where('is_owner', 1)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $referenceById = old('reference_by', $lorry_receipt->reference_by);
        $agencies = $referenceById
            ? Transporter::withTrashed()->with('transporterMaster')
                ->where('company_id', $companyId)
                ->where('transporters.id', $referenceById)
                ->where('is_owner', 0)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
            : collect();

        $selectedConsigneeIds = collect([
            old('customer_id', $lorry_receipt->customer_id),
            old('consignor_id', $lorry_receipt->consignor_id),
            old('consignee_id', $lorry_receipt->consignee_id),
        ])->filter()->unique()->values();

        $consignees = $selectedConsigneeIds->isNotEmpty()
            ? Consignee::withTrashed()
                ->with('city')
                ->where('company_id', $companyId)
                ->whereIn('id', $selectedConsigneeIds)
                ->orderBy('name')
                ->get()
            : collect();
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $countries = Country::orderBy('name')->get();
        $truckTypes = TruckType::orderBy('name')->get();
        $statuses = ['Pending', 'Delivered', 'Cancelled', 'In-Progress'];
        $tdsPercentages = [
            'default' => config('payments.tds_percentages.default', 20),
            'agency' => config('payments.tds_percentages.agency', 2),
            'owner' => config('payments.tds_percentages.owner', 1),
        ];

        // Check if transporter can be changed (TDS not submitted)
        // Transporter cannot be changed if TDS is submitted, regardless of payment status
        $canChangeTransporter = true;
        $freightBookings = FreightBooking::where('lr_id', $lorry_receipt->id)->get();

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
            $grDate = Carbon::parse($grDateToCheck);
            $grMonth = $grDate->month;
            $grYear = $grDate->year;

            $submissionDay = config('payments.tds_submission_day', 3);

            $submissionDate = Carbon::create($grYear, $grMonth, 1)
                ->addMonth()
                ->day($submissionDay)
                ->startOfDay();

            $today = Carbon::now()->startOfDay();
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
            'countries' => $countries,
            'truckTypes' => $truckTypes,
            'truckOwners' => $truckOwners,
            'agencies' => $agencies,
            'tdsPercentages' => $tdsPercentages,
        ]);
    }

    public function update(Request $request, LorryReceipt $lorry_receipt): RedirectResponse
    {
        $companyId = $this->getCurrentCompanyId();
        $company = Auth::user()->company;
        $company?->loadMissing('subscription');
        if (! $company?->subscriptionCanWrite()) {
            return $this->redirectToLorryReceiptsIndex($request, 'error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        if ($request->has('lrs') && is_array($request->input('lrs'))) {
            return $this->updateLinkedLorryReceipts($request, $lorry_receipt, $companyId);
        }

        if ($lorry_receipt->invoice_generated == 1) {
            return $this->redirectToLorryReceiptsIndex($request, 'error', 'Cannot edit Good Receipt (Bilty) as invoice has already been generated.');
        }
        if ($lorry_receipt->delivery_status == 'Delivered') {
            // only allow updating memo_id if already delivered
            $validated = $request->validate([
                'memo_id' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) use ($request, $lorry_receipt) {
                        if ($value) {
                            $exists = LorryReceipt::where('memo_id', $value)
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

            return $this->redirectToLorryReceiptsIndex($request, 'success', 'Good Receipt (Bilty) updated successfully! Only Memo ID could be updated as the LR is already delivered.');
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
                        $exists = LorryReceipt::where('memo_id', $value)
                            ->where('id', '!=', $lorry_receipt->id) // ignore current LR
                            ->first();

                        if ($exists && $exists->customer_id != $request->customer_id) {
                            $fail("Memo ID $value is already used by another customer.");
                        }

                        if ($exists && $exists->invoice_generated == 1) {
                            $fail("Memo ID $value have a invoice generated.");
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
            'source_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'loading_station' => 'nullable|string|max:100',
            'unloading_station' => 'nullable|string|max:100',
            'lane' => 'required|string|max:100',
            'good_type' => 'required|string|max:100',
            'goods_value' => 'nullable|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'is_fixed_amount' => 'nullable|boolean',
            'actual_amount' => 'required|numeric|min:0',
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

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;
        $this->forceAgencyForOrphanTruck($validated, $companyId);

        $isFixed = (bool) ($validated['is_fixed_amount'] ?? false);
        // Before delivery, loading and unloading weight must stay the same.
        $validated['unloading_weight'] = $validated['weight'];
        $validated['shortage_amount'] = $lorry_receipt->shortage_amount;
        $validated['damage_amount'] = $lorry_receipt->damage_amount;
        $resolved = $this->lrCalculationService->resolveForUpdate(
            $isFixed,
            (float) $validated['weight'],
            (float) $validated['unloading_weight'],
            (float) $validated['fr_rate'],
            (float) $validated['actual_amount'],

            $validated['shortage_amount'],
            $validated['damage_amount']
        );
        $validated['actual_amount'] = $resolved['actual_amount'];
        $validated['amount'] = $resolved['amount'];

        // Add company_id to validated data
        $validated['company_id'] = $companyId;

        $freightBooking = $lorry_receipt->freight_booking_id
            ? FreightBooking::find($lorry_receipt->freight_booking_id)
            : FreightBooking::where('lr_id', $lorry_receipt->id)->first();
        if ($freightBooking) {
            if (! $freightBooking->canChangeTdsAmount() && isset($validated['transporter_id']) && $validated['transporter_id'] != $lorry_receipt->transporter_id) {
                return redirect()->back()->withErrors([
                    'transporter_id' => 'Transporter cannot be changed because TDS has already been submitted for this good hire (lorry hire).',
                ])->withInput();
            }
            $this->lrCalculationService->syncFreightBookingsForUpdatedLorryReceipt(
                $lorry_receipt,
                $freightBooking,
                $validated
            );
        }

        $lorry_receipt->update($validated);

        if (isset($validated['delivery_status']) && $validated['delivery_status'] !== $oldDeliveryStatus) {
            $lorry_receipt->loadMissing(['company', 'truck', 'transporter.transporterMaster', 'customer']);
            $this->lrNotificationService->notifyStatusChange($lorry_receipt, $validated['delivery_status'], auth()->user());
        }

        return $this->redirectToLorryReceiptsIndex($request, 'success', 'Good Receipt (Bilty) updated successfully!');
    }

    /**
     * Validate uniqueness of LR fields for update
     *
     * @throws ValidationException
     */
    private function validateLorryReceiptUniquenessUpdate(array $data, ?int $excludeId, int $companyId): void
    {
        $errors = [];

        // ✅ CHECK 1: LR_NUMBER
        if (! empty($data['lr_number'])) {
            $exists = LorryReceipt::where('company_id', $companyId)
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
            $exists = LorryReceipt::where('company_id', $companyId)
                ->where('gr_number', $data['gr_number'])
                ->where('id', '!=', $excludeId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $errors['gr_number'] = "GR Number '{$data['gr_number']}' already exists in your company.";
            }
        }

        // ✅ CHECK 3: MEMO_ID
        if (! empty($data['memo_id'])) {
            $exists = LorryReceipt::where('company_id', $companyId)
                ->where('memo_id', $data['memo_id'])
                ->where('id', '!=', $excludeId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $errors['memo_id'] = "Memo ID '{$data['memo_id']}' already exists in your company.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

   public function destroy(
    Request $request,
    LorryReceipt $lorry_receipt
): RedirectResponse {
    if ($redirect = $this->redirectIfPlanExpired()) {
        return $redirect;
    }

    $request->validate([
        'delete_remark' => ['required', 'string', 'max:1000'],
    ]);

    $deleteRemark = $request->input('delete_remark');

    $lorryReceiptsToDelete = $this->resolveLorryReceiptsForDeletion($lorry_receipt);

    if ($lorryReceiptsToDelete === null) {
        return redirect()->back()
            ->with(
                'error',
                'Good receipt (bilty) can only be deleted when delivery status is Pending.'
            );
    }

    try {
        $deletedBy = Auth::id();

        DB::transaction(function () use (
            $lorryReceiptsToDelete,
            $deleteRemark,
            $deletedBy
        ) {
         foreach ($lorryReceiptsToDelete as $lr) {
        $lr->deleted_by = $deletedBy;
        $lr->deleted_remark = $deleteRemark;
        $lr->save();
        $lr->delete();
        }
        });

        $message = $lorryReceiptsToDelete->count() > 1
            ? $lorryReceiptsToDelete->count()
                . ' linked good receipts (bilty) and all associated data deleted successfully!'
            : 'Good receipt (bilty) and all associated data deleted successfully!';

        return $this->redirectToLorryReceiptsIndex(
            request(),
            'success',
            $message
        );

    } catch (\Exception $e) {
        Log::error(
            'Error deleting lorry receipt: ' . $e->getMessage()
        );

        return redirect()->back()
            ->with(
                'error',
                'Error deleting good receipt (bilty). Please try again.'
            );
    }
}
    
    private function resolveLorryReceiptsForDeletion(LorryReceipt $lorry_receipt): ?Collection
    {
        $companyId = $this->getCurrentCompanyId();

        if ($lorry_receipt->linked_lr_id) {
            $group = LorryReceipt::where('company_id', $companyId)
                ->where('linked_lr_id', $lorry_receipt->linked_lr_id)
                ->get();

            if ($group->contains(fn (LorryReceipt $lr) => $lr->delivery_status !== 'Pending')) {
                return null;
            }

            return $group;
        }

        if ($lorry_receipt->delivery_status !== 'Pending') {
            return null;
        }

        return collect([$lorry_receipt]);
    }

    public function details(int $id): JsonResponse
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

    public function getLorryReceiptsForCustomer(int $customerId): JsonResponse
    {
        $lorryReceipts = LorryReceipt::where('customer_id', $customerId)
            ->where('company_id', Auth::user()->company_id)
            ->get(['id', 'lr_number']);

        return response()->json($lorryReceipts);
    }

    public function getByLinkedId(int $linkedId): JsonResponse
    {
        try {

            $lrs = LorryReceipt::with(['truck', 'freightBookings', 'transporter'])
                ->where('company_id', Auth::user()->company_id)
                ->where('linked_lr_id', $linkedId)
                ->get()
                ->map(function ($lr) {
                    $lastFreight = $lr->freightBookings->last();
                    $transporter = $lr->transporter;

                    return [
                        'id' => $lr->id,
                        'lr_number' => $lr->lr_number,
                        'truck_number' => $lr->truck?->truck_number ?? '',
                        'freight_booking_id' => $lr->freight_booking_id,
                        'balance_amount' => $lastFreight ? (float) $lastFreight->balance_amount : 0,
                        'shortage_amount' => $lastFreight ? (float) ($lastFreight->shortage_amount ?? 0) : 0,
                        'damage_amount' => $lastFreight ? (float) ($lastFreight->damage_amount ?? 0) : 0,
                        'transporter_name' => $transporter?->name ?? '',
                        'transporter_mobile' => $transporter?->mobile_number ?? '',
                        'transporter_short_name' => $transporter?->short_name ?? '',
                        'transporter_beneficiary_id' => $transporter?->beneficiary_id ?? '',
                        'transporter_tds_percentage' => $transporter?->tds_percentage ?? '',
                        'transporter_advance_amount' => $transporter?->advance_amount ?? 0,
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
