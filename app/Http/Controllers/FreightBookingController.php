<?php

namespace App\Http\Controllers;

use App\Models\AdvancePayment;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\VoucherPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use App\Models\Pump;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Company;

class FreightBookingController extends Controller
{
    public function index(Request $request)
    {

        $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getFreightBookingsData($request);
        }

        $baseQuery = FreightBooking::with(['lr.truck', 'linkedFreightBooking', 'company', 'transporter.transporterMaster']);

        // Company filter
        if (auth()->user()->userRole->name !== 'super_admin') {
            $baseQuery->where('freight_bookings.company_id', auth()->user()->company_id);
        }

        // Default date range: last 15 days
        $endDate = $request->input('end_date', date('Y-m-d', strtotime('+7 days')));
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-7 days')));

        // Apply date range filter if provided
        if ($request->filled('start_date')) {
            $startDate = $request->start_date;
        }
        if ($request->filled('end_date')) {
            $endDate = $request->end_date;
        }

        // Ensure date range doesn't exceed 3 months
        $maxEndDate = (new \DateTime($startDate))->modify('+3 months')->format('Y-m-d');
        if ($endDate > $maxEndDate) {
            $endDate = $maxEndDate;
        }

        // Filter by date range for LR -> gr_date
        $baseQuery->whereHas('lr', function ($q) use ($startDate, $endDate) {
            $q->whereDate('gr_date', '>=', $startDate)
                ->whereDate('gr_date', '<=', $endDate);
        });

        // Transporter filter
        if ($request->filled('transporter_id')) {
            $baseQuery->where(function ($q) use ($request) {
                $q->where('transporter_id', $request->transporter_id)
                    ->orWhere('transporter_name', 'LIKE', "%{$request->transporter_id}%");
            });
        }

        // Truck owner filter
        if ($request->filled('truck_owner_id')) {
            $baseQuery->whereHas('lr.truck', function ($q) use ($request) {
                $q->where('transporter_id', $request->truck_owner_id);
            });
        }


        // Pending memo filter (only show bookings whose LR has no memo_id)
        if ($request->filled('pending_memo')) {
            $baseQuery->whereHas('lr', function ($q) {
                $q->where('memo_id', null);
            });
            $baseQuery->where('is_cancelled', 0);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $searchQuery = clone $baseQuery;
            $searchQuery->where(function ($q) use ($search) {
                $q->where('voucher_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('lr', function ($lrQuery) use ($search) {
                        $lrQuery->where('lr_number', 'LIKE', "%{$search}%")
                            ->orWhere('source_city', 'LIKE', "%{$search}%")
                            ->orWhere('destination_city', 'LIKE', "%{$search}%")
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery->where('name', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('truck', function ($truckQuery) use ($search) {
                                $truckQuery->where('truck_number', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('transporter.transporterMaster', function ($transporterQuery) use ($search) {
                                $transporterQuery->where('name', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('reference.transporterMaster', function ($referenceQuery) use ($search) {
                                $referenceQuery->where('name', 'LIKE', "%{$search}%");
                            });
                    });
            });

            // IDs of direct matches
            $searchResultIds = (clone $searchQuery)->pluck('id')->toArray();

            // Step 2: Extract linked_lr_id from search results
            $linkedIds = (clone $searchQuery)
                ->whereNotNull('linked_lr_id')
                ->pluck('linked_lr_id')
                ->unique()
                ->toArray();

            // Step 3: Merge search results + all linked FRs
            $baseQuery->where(function ($q) use ($searchResultIds, $linkedIds) {

                if (!empty($searchResultIds)) {
                    $q->whereIn('freight_bookings.id', $searchResultIds);
                }

                if (!empty($linkedIds)) {
                    $q->orWhereIn('freight_bookings.linked_lr_id', $linkedIds);
                }
            });
        }

        /* ------------------------------------
     | Grouped Ordering (UNCHANGED)
     |------------------------------------ */
        $bookings = $baseQuery
            ->select('freight_bookings.*')
            ->leftJoin(
                DB::raw('(
                SELECT linked_lr_id, MAX(id) as max_id
                FROM freight_bookings
                WHERE linked_lr_id IS NOT NULL
                GROUP BY linked_lr_id
            ) as fb_group'),
                function ($join) {
                    $join->on('freight_bookings.linked_lr_id', '=', 'fb_group.linked_lr_id');
                }
            )
            ->orderByDesc(DB::raw('COALESCE(fb_group.max_id, freight_bookings.id)'))
            ->orderByDesc('freight_bookings.id')
            ->get();

        /* ------------------------------------
     | Dropdown / Filter Data
     |------------------------------------ */
        $transporters = Transporter::with('transporterMaster')
            ->where('is_owner', 0)
            ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->orderBy('transporter_masters.name')
            ->select('transporters.*')
            ->get();
        $truckOwners = Transporter::with('transporterMaster')
            ->where('is_owner', 1)
            ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->orderBy('transporter_masters.name')
            ->select('transporters.*')
            ->get();
        $lrs          = LorryReceipt::all();
        $paymentTypes = \App\Models\PaymentType::all();
        $paymentModes = \App\Models\PaymentMode::all();
        $customers    = \App\Models\Consignee::all();

        return view('freight_bookings.index', compact(
            'bookings',
            'lrs',
            'paymentTypes',
            'paymentModes',
            'transporters',
            'truckOwners',
            'startDate',
            'endDate',
            'pumps',
            'customers'
        ));
    }

    public function getFreightBookingsData(Request $request)
    {
        try {
            \Log::info('FreightBookings DataTables request started');

            $query = FreightBooking::with(['lr.truck', 'linkedFreightBooking', 'company', 'transporter.transporterMaster'])
                ->select('freight_bookings.*');

            // Only apply company_id filter for non-super_admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('freight_bookings.company_id', auth()->user()->company_id);
            }

            \Log::info('Query built, processing DataTables');

            $dataTable = DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    // Hide checkbox if freight booking is delivered and balance is zero
                    if ($row->is_delivered && ($row->balance_amount <= 0)) {
                        return '<span class="text-muted" title="No checkbox available: Delivered with zero balance"><i class="bi bi-dash-circle"></i></span>';
                    }

                    return sprintf(
                        '<input type="checkbox" class="row-checkbox" value="%s" data-lr="%s" data-truck="%s" data-balance="%s" data-voucher_number="%s" data-is-delivered="%s">',
                        $row->id,
                        $row->lr ? $row->lr->lr_number : '',
                        $row->lr && $row->lr->truck ? $row->lr->truck->truck_number : '',
                        $row->balance_amount ?? 0,
                        $row->voucher_number ?? '',
                        $row->is_delivered ? '1' : '0'
                    );
                })
                ->addColumn('fr_number', function ($row) {
                    return $row->id ? $row->id : '-';
                })
                ->addColumn('lr_number', function ($row) {
                    return $row->lr ? $row->lr->lr_number : '-';
                })
                ->addColumn('source_city', function ($row) {
                    return $row->lr && $row->lr->source_city ? $row->lr->source_city : '-';
                })
                ->addColumn('destination_city', function ($row) {
                    return $row->lr && $row->lr->destination_city ? $row->lr->destination_city : '-';
                })
                ->addColumn('transporter', function ($row) {
                    $transporterName = $row->transporter_name ?? ($row->transporter ? $row->transporter->name : '-');
                    $badge = '';

                    if ($row->transporter && $row->transporter->is_owner) {
                        $badge = '<span class="badge bg-success ms-1" title="Truck Owner"><i class="bi bi-truck"></i></span>';
                    } else {
                        $badge = '<span class="badge bg-info ms-1" title="Agency"><i class="bi bi-building"></i></span>';
                    }

                    return $transporterName . ' ' . $badge;
                })
                ->addColumn('truck_number', function ($row) {
                    return $row->lr && $row->lr->truck ? $row->lr->truck->truck_number : '-';
                })

                ->addColumn('tr_rate_formatted', function ($row) {
                    return $row->tr_fr_rate ? '₹' . number_format($row->tr_fr_rate, 2) : '₹0.00';
                })
                // ->addColumn('tr_amount_formatted', function ($row) {
                //     return $row->tr_amount ? '₹' . number_format($row->tr_amount, 2) : '₹0.00';
                // })
                ->addColumn('advance_formatted', function ($row) {
                    return $row->advance_amount ? '₹' . number_format($row->advance_amount, 2) : '₹0.00';
                })
                ->addColumn('balance_formatted', function ($row) {

                    return $row->balance_amount ? '₹' . number_format($row->balance_amount, 2) : '₹0.00';
                })
                ->addColumn('gr_date_formatted', function ($row) {
                    return $row->lr && $row->lr->gr_date ? date('d-m-Y', strtotime($row->lr->gr_date)) : '-';
                })
                ->addColumn('truck_number', function ($row) {
                    return $row->lr && $row->lr->truck ? $row->lr->truck->truck_number : '-';
                })
                // ->addColumn('is_delivered_badge', function ($row) {
                //     if ($row->is_delivered == '1') {
                //         return '<span class="badge bg-success">Yes</span>';
                //     } else {
                //         return '<span class="badge bg-warning">No</span>';
                //     }
                // })
                // ->addColumn('is_cancelled_badge', function ($row) {
                //     if ($row->is_cancelled == '1') {
                //         return '<span class="badge bg-danger">Yes</span>';
                //     } else {
                //         return '<span class="badge bg-secondary">No</span>';
                //     }
                // })
                ->addColumn('actions', function ($row) {
                    return $this->getActionButtons($row);
                })
                ->filterColumn('lr_number', function ($query, $keyword) {
                    $query->whereHas('lr', function ($q) use ($keyword) {
                        $q->where('lr_number', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['checkbox', 'actions', 'transporter']);

            \Log::info('DataTables processing complete');
            return $dataTable->make(true);
        } catch (\Exception $e) {
            \Log::error('DataTables error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], 200);
        }
    }

    private function getActionButtons($freightBooking)
    {
        $actions = '<div class="btn-group" role="group">';

        // // View button
        // $actions .= '<a href="' . route('freight_bookings.show', $freightBooking->id) . '" class="btn btn-sm btn-outline-info" title="View">';
        // $actions .= '<i class="bi bi-eye"></i>';
        // $actions .= '</a>';

        // Edit button
        if (auth()->check() && auth()->user()->userRole->name === 'admin') {
            $actions .= '<a href="' . route('freight_bookings.edit', $freightBooking->id) . '" class="btn btn-sm btn-outline-primary" title="Edit">';
            $actions .= '<i class="bi bi-pencil"></i>';
            $actions .= '</a>';
        }

        // Individual Voucher Payment button
        if ($freightBooking->voucher_number && $freightBooking->balance_amount > 0) {
            $actions .= '<button type="button" class="btn btn-sm btn-outline-success individual-payment-btn" ';
            $actions .= 'data-id="' . $freightBooking->id . '" ';
            $actions .= 'data-lr="' . ($freightBooking->lr ? $freightBooking->lr->lr_number : 'N/A') . '" ';
            $actions .= 'data-voucher_number="' . $freightBooking->voucher_number . '" ';
            $actions .= 'data-balance="' . $freightBooking->balance_amount . '" ';
            $actions .= 'data-truck="' . ($freightBooking->lr && $freightBooking->lr->truck ? $freightBooking->lr->truck->truck_number : 'N/A') . '" ';
            $actions .= 'title="Make Voucher Payment">';
            $actions .= '<i class="bi bi-credit-card"></i>';
            $actions .= '</button>';
        }

        // Delete button
        if (auth()->check() && auth()->user()->userRole->name === 'admin') {
            $actions .= '<form method="POST" action="' . route('freight_bookings.destroy', $freightBooking->id) . '" style="display: inline;">';
            $actions .= csrf_field();
            $actions .= method_field('DELETE');
            $actions .= '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm(\'Are you sure you want to delete this freight booking?\')">';
            $actions .= '<i class="bi bi-trash"></i>';
            $actions .= '</button>';
            $actions .= '</form>';
        }
        $actions .= '</div>';

        return $actions;
    }

    public function getFreightBookingDetails($id)
    {
        $freightBooking = FreightBooking::with([
            'lr' => function ($query) {
                $query->with(['transporter.transporterMaster', 'truck', 'consignor', 'consignee']);
            }
        ])->find($id);

        if (!$freightBooking) {
            return response()->json(['error' => 'Freight Booking not found'], 404);
        }

        return response()->json([
            'freight_booking' => $freightBooking,
            'lr' => $freightBooking->lr
        ]);
    }

    public function create(Request $request)
    {
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        //check for lr credits
        if ($company->lr_credits <= 0) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'You do not have enough credits to create a Freight Booking.');
        }
        $companies = \App\Models\Company::all();
        $lrs = LorryReceipt::all();
        $linkedBookings = FreightBooking::all();
        $goodsTypes = \App\Models\GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $lorry_receipt_id = $request->lorry_receipt_id;

        // Get TDS percentages from config
        $tdsPercentages = [
            'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
            'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
            'owner' => env('TDS_PERCENTAGE_OWNER', 1)
        ];

        $lorry_receipt = \App\Models\LorryReceipt::with([
            'transporter.transporterMaster',
            'truck:id,truck_number',
            'customer:id,name',
            'consignor:id,name',
            'consignee:id,name',
            'reference.transporterMaster'
        ])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'reference_by', 'customer_id', 'consignor_id', 'consignee_id', 'good_type', 'source_city', 'destination_city', 'source_state', 'destination_state', 'weight', 'no_of_units', 'amount', 'fr_rate')
            ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
                $query->where('id', $lorry_receipt_id);
            })
            ->get();

        // If lorry_receipt_id is provided, fetch LR number and related data
        if ($request->has('lorry_receipt_id')) {
            $lr = \App\Models\LorryReceipt::find($request->lorry_receipt_id);
            if ($lr) {
                // Fetch transporter and truck details directly from their models
                $transporter = \App\Models\Transporter::find($lr->transporter_id);
                $truck = \App\Models\Truck::find($lr->truck_id);
                // Prepare values for TDS calculation
                $isOwner          = $transporter ? $transporter->is_owner : 0;
                $declarationGiven = $transporter ? $transporter->declaration_given : 0;
                $panActive        = $transporter ? $transporter->pan_active : 0;
                $panNumber        = $transporter ? $transporter->pan_number : '';
                $trAmount         = $lr->freight_amount ?? 0; // adjust this field if amount is stored differently

                // =============================
                // TDS CALCULATION LOGIC
                // =============================
                $tdsPercentage = 0;

                // CASE 1: is_owner = 0, declaration_given = 1 → 0% TDS
                if (($isOwner == 1 && $declarationGiven == 1) && ($panActive == 1 || $panActive == 0)) {
                    $tdsPercentage = 0;
                }
                // // CASE 2: is_owner = 0, declaration_given = 0, pan_active = 0 → 20% TDS
                elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 0) {
                    $tdsPercentage = 20;
                }
                // CASE 3: is_owner = 0, declaration_given = 0, pan_active = 1 → check PAN letter
                elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 1) {
                    if (strlen($panNumber) >= 4) {
                        $fourthChar = strtoupper($panNumber[3]);
                        if ($fourthChar == 'P' || $fourthChar == 'H') {
                            $tdsPercentage = 1;
                        } else {
                            $tdsPercentage = 2;
                        }
                    } else {
                        $tdsPercentage = 2; // fallback if PAN invalid
                    }
                }
                // // CASE 4: is_owner = 1, pan_active = 0 → 20% TDS
                elseif ($isOwner == 0 && $panActive == 0) {
                    $tdsPercentage = 20;
                }
                // CASE 5: is_owner = 1, pan_active = 1 → 2% TDS
                elseif ($isOwner == 0 && $panActive == 1) {
                    $fourthChar = strtoupper($panNumber[3]);
                    if ($fourthChar === 'C' || $fourthChar === 'F' || $fourthChar === 'A' || $fourthChar === 'B' || $fourthChar === 'J' || $fourthChar === 'L' || $fourthChar === 'T') {
                        $tdsPercentage = 2;
                    } else {
                        $tdsPercentage = 1;
                    }
                }

                // $tdsAmount = $trAmount * ($tdsPercentage / 100);

                return view('freight_bookings.create', [
                    'companies' => $companies,
                    'lrs' => $lrs,
                    'linkedBookings' => $linkedBookings,
                    'goodsTypes' => $goodsTypes,
                    'lorry_receipt' => $lorry_receipt,
                    'weight' => $lr->weight,

                    'transporter' => $transporter ?? null,
                    'tdsPercentages' => $tdsPercentages,
                    'tdsPercentage'   => $tdsPercentage, // send to view
                    // 'tdsAmount'       => $tdsAmount,     // send to view
                    'preFilled' => [
                        'lr_number' => $lr->lr_number,
                        'lr_id' => $lr->id,
                        'transporter_id' => $lr->transporter_id,
                        'transporter_name' => $transporter ? $transporter->name : '',
                        'truck_id' => $lr->truck_id,
                        'truck_number' => $truck ? $truck->truck_number : '',
                        'pan_verified' => $transporter ? $transporter->pan_active : 0,
                        'pan_number' => $transporter ? $transporter->pan_number : '',
                        'is_owner' => $transporter ? $transporter->is_owner : 0,
                        'declaration_given' => $transporter ? $transporter->declaration_given : 0,
                        'goods_type' => $lr->good_type,


                    ]
                ]);
            }
        }

        return view('freight_bookings.create', [
            'companies' => $companies,
            'lrs' => $lrs,
            'linkedBookings' => $linkedBookings,
            'goodsTypes' => $goodsTypes,
            'lorry_receipt' => $lorry_receipt,
            'transporter' => null,
            'tdsPercentages' => $tdsPercentages,
            'preFilled' => []
        ]);
    }


    /**
     * Confirm the freight booking details before final submission
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function confirm(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric|min:1|max:20000',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'nullable|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'eway_bill_number' => 'nullable|string',
            'eway_bill_expiry' => 'nullable|date',
            'good_type' => 'nullable|string',
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Get the validated form data
        $formData = $validator->validated();

        // Get the lorry receipt with its relationships
        $lorry_receipt = LorryReceipt::with(['transporter.transporterMaster', 'truck', 'customer', 'consignor', 'consignee', 'reference.transporterMaster'])
            ->findOrFail($formData['lr_id']);

        // Return the confirmation view with the form data and lorry receipt
        return view('freight_bookings.confirm', compact('formData', 'lorry_receipt'));
    }

    /**
     * Store a newly created freight booking in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if the form has been confirmed
        if (!$request->has('confirmed')) {
            return redirect()->route('freight_bookings.create')
                ->with('error', 'Please confirm the freight booking details before submission.');
        }

        $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'nullable|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'eway_bill_number' => 'nullable|string',
            'eway_bill_expiry' => 'nullable|date',
            'good_type' => 'nullable|string',

            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'company_id' => 'required|exists:companies,id',


        ]);

        // Get the lorry receipt to get its LR number
        $lorryReceipt = LorryReceipt::find($data['lr_id']);
        if (!$lorryReceipt) {
            return redirect()->back()->withErrors(['lr_id' => 'Invalid lorry receipt']);
        }

        if ($lorryReceipt->freight_booking_id) {
            return redirect()->route('freight_bookings.index')->with('error', 'This Lorry Receipt is already linked to a Freight Booking.');
        }

        // Create freight booking with lorry receipt's LR number
        // Get the next voucher number
        $nextVoucherNumber = FreightBooking::max('voucher_number') + 1;

        // Get transporter information from lorry receipt
        $transporterInfo = [];
        if ($lorryReceipt->transporter) {
            $transporterInfo = [
                'transporter_id' => $lorryReceipt->transporter_id,
                'transporter_name' => $lorryReceipt->transporter->name,
                'transporter_tds_percentage' => $lorryReceipt->transporter->tds_percentage
            ];
        }

        $linkedLrId = $lorryReceipt->linked_lr_id ?? null;
        $linked_freight_booking_id = FreightBooking::where('lr_id', $lorryReceipt->id)->orderBy('id', 'desc')->first()->id ?? null;

        $freightBooking = FreightBooking::create(array_merge($data, [
            'lr_number' => $lorryReceipt->lr_number,
            'linked_freight_booking_id' => $linked_freight_booking_id,
            'voucher_number' => $nextVoucherNumber,
            'pan_verified' => (bool)$data['pan_verified'],
            'company_id' => $data['company_id'],
            'linked_lr_id' => $linkedLrId,
        ], $transporterInfo));

        // Update lorry receipt status and damage amount
        if ($lorryReceipt) {
            $deliveryStatus = 'Pending';
            if ($data['is_cancelled'] === '1') {
                $deliveryStatus = 'Cancelled';
            } elseif ($data['is_delivered'] === '1') {
                $deliveryStatus = 'Delivered';
            } else {
                $deliveryStatus = 'In-Progress';
            }
            $lorryReceipt->delivery_status = $deliveryStatus;
            $lorryReceipt->freight_booking_id = $freightBooking->id;
            $lorryReceipt->save();
        }

        if ($request->has('action') && $request->action === 'save_and_pay') {
            // Redirect to voucher payment creation page with freight booking ID
            return redirect()->route('voucher_payments.create', ['freight_booking_id' => $freightBooking->id])
                ->with('success', 'Freight Booking created successfully. Ready to make payment.');
        }
        if ($request->has('action') && $request->action === 'save_and_view') {
            return redirect()->route('freight_bookings.show', $freightBooking->id)
                ->with('success', 'Freight Booking created successfully. Ready to make payment.');
        }
        //decrease lr credits by one in company table
        $company = Company::find($data['company_id']);
        $company->lr_credits = $company->lr_credits - 1;
        $company->save();

        return redirect()->route('freight_bookings.index')->with('success', 'Freight Booking created successfully.');
    }

    public function storeCombinedPayment(Request $request)
    {
        try {
            $request->validate([
                'combined_amount' => 'required|numeric|min:1',
                'payment_type' => 'required|exists:payment_types,id',
                'payment_mode' => 'required|exists:payment_modes,id',
                'pump_id' => 'nullable|exists:pumps,id',
                'reference_number' => 'nullable|string|max:255',
                'payment_date' => 'nullable|date',
                'payment_remark' => 'nullable|string|max:1000',
                'booking_ids' => 'required|array',
                'payment_amounts' => 'required|array',
                'notes' => 'nullable|string|max:1000',
            ]);

            $lr_ids = FreightBooking::whereIn('id', $request->input('booking_ids'))
                ->pluck('lr_id')
                ->toArray();
            $lrs = LorryReceipt::whereIn('id', $lr_ids)->get()->select('lr_number')->toArray();
            $lrs = array_map(function ($item) {
                return $item['lr_number'];
            }, $lrs);

            $data = [];
            $data['pump_id'] = $request->input('pump_id');
            $data['payment_type_id'] = $request->input('payment_type');
            $data['payment_mode_id'] = $request->input('payment_mode');
            $data['payment_type'] = \App\Models\PaymentType::find($request->input('payment_type'))->name ?? 'N/A';
            $data['payment_mode'] = \App\Models\PaymentMode::find($request->input('payment_mode'))->name ?? 'N/A';
            $data['total_amount'] = $request->input('combined_amount');
            $data['date'] = $request->input('payment_date') ?? date('Y-m-d');
            $data['booking_ids'] = $request->input('booking_ids');
            $data['payment_amounts'] = $request->input('payment_amounts');
            $data['reference_number'] = $request->input('reference_number');
            $data['notes'] = $request->input('notes')  . "   Combined Payment: " . ($request->input('combined_amount'))
                . ". [LR IDs: " . implode(', ', $lrs) . "], Date: " . $data['date'];
            if (strtolower($data['payment_mode']) === 'cash' || strtolower($data['payment_mode']) === 'adjust in advance') {
                $paymentStatus = 'in-progress';
            } else if (
                strtolower($data['payment_mode']) === 'bank transfer' ||
                strtolower($data['payment_mode']) === 'fuel' ||
                strpos(strtolower($data['payment_mode']), 'pump') !== false
            ) {
                $paymentStatus = 'pending';
            }

            // Call voucherPayment which handles all validations, transactions, and locking
            $response = $this->VoucherPayment(new Request($data));
            $responseContent = $response->getContent();
            $response = json_decode($responseContent, true);

            // If voucherPayment returned an error or warning, return it as JSON response
            if ($response['success'] !== true) {
                return response()->json($response, $response['warning'] ? 422 : 422);
            }

            $voucherPayments = $response['processed_payments'] ?? [];

            // Wrap VoucherLinked creation in a transaction for data consistency
            // Note: voucherPayment() already committed its transaction, so individual payments are safe
            // This transaction ensures VoucherLinked and linking operations are atomic
            \DB::beginTransaction();

            try {
                $VoucherLinked = \App\Models\VoucherLinked::create([
                    'date' => $data['date'],
                    'voucher_ids' => json_encode($voucherPayments),
                    'pump_id' => $data['pump_id'],
                    'company_id' => auth()->user()->company_id,
                    'payment_type' => $data['payment_type'],
                    'payment_type_id' => $data['payment_type_id'],
                    'payment_mode' => $data['payment_mode'],
                    'payment_mode_id' => $data['payment_mode_id'],
                    'amount' => $data['total_amount'],
                    'reference_transaction_id' => $data['reference_number'],
                    'remark' => $data['notes'],
                    'payment_status' => $paymentStatus,
                    'freight_booking_id' => $request->input('booking_ids')[0] ?? null,
                ]);

                // Link voucher payments to VoucherLinked record
                foreach ($voucherPayments as $index => $voucherId) {
                    $voucher = \App\Models\VoucherPayment::find($voucherId);
                    if ($voucher) {
                        $voucher->linked_voucher_id = $VoucherLinked->id;
                        $voucher->save();
                    }
                }

                \DB::commit();

                \Log::info('Combined payment created successfully', [
                    'voucher_linked_id' => $VoucherLinked->id,
                    'voucher_payments' => $voucherPayments,
                    'total_amount' => $data['total_amount']
                ]);

                return response()->json($response);
            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error('Error creating VoucherLinked for combined payment', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'voucher_payments' => $voucherPayments
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Combined payment was processed but failed to create linked record. Please contact administrator. Error: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in storeCombinedPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing combined payment. Please try again.'
            ], 500);
        }
    }


    public function show($id)
    {

        try {
            $freightBooking = FreightBooking::with(['lr', 'linkedFreightBooking', 'company'])->findOrFail($id);

            // Check if user has access to this freight booking (company-based filtering)
            if (
                auth()->user()->userRole->name !== 'super_admin' &&
                $freightBooking->company_id !== auth()->user()->company_id
            ) {
                abort(403, 'Unauthorized access to freight booking.');
            }
            $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();

            // Load payment types and modes for the voucher payment modal
            $paymentTypes = \App\Models\PaymentType::all();
            $paymentModes = \App\Models\PaymentMode::all();

            $linkedFreightBookings = collect();
            $combinedTotalBalance = 0;

            if ($freightBooking->linked_lr_id) {
                $freightBookingIds = \App\Models\FreightBooking::where('linked_lr_id', $freightBooking->linked_lr_id)
                    ->where('company_id', auth()->user()->company_id)
                    ->pluck('id')->toArray();
                $payments = \App\Models\VoucherLinked::with('pump')
                    ->whereIn('freight_booking_id', $freightBookingIds)
                    ->where('company_id', auth()->user()->company_id)
                    ->orderBy('created_at', 'desc')
                    ->get();
                $linkedFreightBookings = \App\Models\FreightBooking::where('linked_lr_id', $freightBooking->linked_lr_id)
                    ->where('company_id', auth()->user()->company_id)
                    ->with('lr.truck', 'lr.transporter.transporterMaster')
                    ->orderBy('id')
                    ->get();
                $combinedTotalBalance = $linkedFreightBookings->sum('balance_amount');
            } else {
                $payments = \App\Models\VoucherPayment::with('pump')
                    ->where('freight_booking_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return view('freight_bookings.show', compact('freightBooking', 'paymentTypes', 'paymentModes', 'pumps', 'payments', 'linkedFreightBookings', 'combinedTotalBalance'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Freight booking not found.');
        } catch (\Exception $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'An error occurred while loading the freight booking.');
        }
    }

    public function edit($id)
    {
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        try {
            $freightBooking = FreightBooking::findOrFail($id);

            $is_admin = Auth::user()->userRole->name === 'admin';
            // Check if user has access to this freight booking (company-based filtering)
            if (
                auth()->user()->userRole->name !== 'super_admin' &&
                $freightBooking->company_id !== auth()->user()->company_id
            ) {
                abort(403, 'Unauthorized access to freight booking.');
            }
            $companies = \App\Models\Company::all();
            // Get the LorryReceipt details
            $lorryReceipt = LorryReceipt::find($freightBooking->lr_id);
            // Get TDS percentages from config
            $tdsPercentages = [
                'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
                'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
                'owner' => env('TDS_PERCENTAGE_OWNER', 1)
            ];

            // Get the transporter details
            $transporter = \App\Models\Transporter::find($lorryReceipt->transporter_id);

            // Get the truck details
            $truck = \App\Models\Truck::find($lorryReceipt->truck_id);

            // Get all LRs
            $lrs = LorryReceipt::all();

            // Get goods types
            $goodsTypes = \App\Models\GoodType::where('user_id', Auth::user()->company->user_id)->get();
            $lorry_receipt = \App\Models\LorryReceipt::with(['transporter.transporterMaster', 'truck:id,truck_number'])
                ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'customer_id', 'consignor_id', 'consignee_id', 'good_type', 'reference_by')
                ->when($lorryReceipt, function ($query) use ($lorryReceipt) {
                    $query->where('id', $lorryReceipt->id);
                })
                ->get();


            // Get linked bookings
            $linkedBookings = FreightBooking::where('id', '!=', $freightBooking->id)->get();
            // If lorry_receipt_id is provided, fetch LR number and related data
            if ($lorryReceipt) {
                $lr = \App\Models\LorryReceipt::find($lorryReceipt->id);
                if ($lr) {
                    // Fetch transporter and truck details directly from their models
                    $transporter = \App\Models\Transporter::find($lr->transporter_id);
                    $truck = \App\Models\Truck::find($lr->truck_id);

                    $isOwner          = $transporter ? $transporter->is_owner : 0;
                    $declarationGiven = $transporter ? $transporter->declaration_given : 0;
                    $panActive        = $transporter ? $transporter->pan_active : 0;
                    $panNumber        = $transporter ? $transporter->pan_number : '';
                    $trAmount         = $lr->freight_amount ?? 0; // adjust this field if amount is stored differently

                    // =============================
                    // TDS CALCULATION LOGIC
                    // =============================
                    $tdsPercentage = 0;

                    // CASE 1: is_owner = 0, declaration_given = 1 → 0% TDS
                    if (($isOwner == 1 && $declarationGiven == 1) && ($panActive == 1 || $panActive == 0)) {
                        $tdsPercentage = 0;
                    }
                    // // CASE 2: is_owner = 0, declaration_given = 0, pan_active = 0 → 20% TDS
                    elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 0) {
                        $tdsPercentage = 20;
                    }
                    // CASE 3: is_owner = 0, declaration_given = 0, pan_active = 1 → check PAN letter
                    elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 1) {
                        if (strlen($panNumber) >= 4) {
                            $fourthChar = strtoupper($panNumber[3]);
                            if ($fourthChar == 'P' || $fourthChar == 'H') {
                                $tdsPercentage = 1;
                            } else {
                                $tdsPercentage = 2;
                            }
                        } else {
                            $tdsPercentage = 2; // fallback if PAN invalid
                        }
                    }
                    // // CASE 4: is_owner = 1, pan_active = 0 → 20% TDS
                    elseif ($isOwner == 0 && $panActive == 0) {
                        $tdsPercentage = 20;
                    }
                    // CASE 5: is_owner = 1, pan_active = 1 → 2% TDS
                    elseif ($isOwner == 0 && $panActive == 1) {
                        $fourthChar = strtoupper($panNumber[3]);
                        if ($fourthChar === 'C' || $fourthChar === 'F' || $fourthChar === 'A' || $fourthChar === 'B' || $fourthChar === 'J' || $fourthChar === 'L' || $fourthChar === 'T') {
                            $tdsPercentage = 2;
                        } else {
                            $tdsPercentage = 1;
                        }
                    }


                    return view('freight_bookings.edit', [
                        'freightBooking' => $freightBooking,
                        'companies' => $companies,
                        'lrs' => $lrs,
                        'linkedBookings' => $linkedBookings,
                        'goodsTypes' => $goodsTypes,
                        'lorry_receipt' => $lorry_receipt,
                        'weight' => $lr->weight,

                        'transporter' => $transporter ?? null,
                        'tdsPercentages' => $tdsPercentages,
                        'tdsPercentage'   => $tdsPercentage, // send to view
                        'canChangeTds' => $freightBooking->canChangeTdsAmount(),
                        'preFilled' => [
                            'lr_number' => $lr->lr_number,
                            'lr_id' => $lr->id,
                            'transporter_id' => $lr->transporter_id,
                            'transporter_name' => $transporter ? $transporter->name : '',
                            'truck_id' => $lr->truck_id,
                            'truck_number' => $truck ? $truck->truck_number : '',
                            'pan_verified' => $transporter ? $transporter->pan_active : 0,
                            'is_owner' => $transporter ? $transporter->is_owner : 0,
                            'goods_type' => $lr->good_type,
                            'memo_id' => $lr->memo_id
                        ],
                        'is_admin' => $is_admin
                    ]);
                }
            }

            return view('freight_bookings.edit', [
                'freightBooking' => $freightBooking,
                'lorry_receipt' => $lrs,
                'linkedBookings' => $linkedBookings,
                'goods_type' => $goodsTypes,
                'transporter' => $transporter,
                'truck' => $truck,
                'canChangeTds' => $freightBooking->canChangeTdsAmount(),
                'preFilled' => [
                    'lr_number' => $freightBooking->lr ? $freightBooking->lr->lr_number : '',
                    'lr_id' => $freightBooking->lr_id,
                    'transporter_id' => $freightBooking->lr ? $freightBooking->lr->transporter_id : '',
                    'transporter_name' => $transporter ? $transporter->name : '',
                    'truck_id' => $freightBooking->lr ? $freightBooking->lr->truck_id : '',
                    'truck_number' => $truck ? $truck->truck_number : '',
                    'pan_verified' => $transporter ? $transporter->pan_active : 0,
                    'is_owner' => $transporter ? $transporter->is_owner : 0,
                ],
                'is_admin' => $is_admin
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Freight booking not found.');
        } catch (\Exception $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'An error occurred while loading the freight booking for editing.');
        }
    }

    public function getLorryReceipt($id)
    {
        $lr = LorryReceipt::find($id);
        if (!$lr) {
            return response()->json(['error' => 'LR not found'], 404);
        }
        return response()->json($lr);
    }

    public function getTransporter($id)
    {
        $transporter = \App\Models\Transporter::with('transporterMaster')->find($id);
        if (!$transporter) {
            return response()->json(['error' => 'Transporter not found'], 404);
        }
        return response()->json($transporter);
    }

    public function getTruck($id)
    {
        $truck = \App\Models\Truck::with(['transporter.transporterMaster', 'driver'])->find($id);
        if (!$truck) {
            return response()->json(['error' => 'Truck not found'], 404);
        }
        return response()->json($truck);
    }

    public function update(Request $request, FreightBooking $freightBooking)
    {
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        // Prevent updates to cancelled freight bookings
        if ($freightBooking->is_cancelled) {
            return redirect()->back()->withErrors(['general' => 'Cannot update a cancelled freight booking. Please contact administrator if changes are needed.']);
        }

        // Add debug logging
        \Log::info('Freight booking update started', [
            'freight_booking_id' => $freightBooking->id,
            'request_data' => $request->all()
        ]);


        $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric|min:0|max:20000',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'required|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'good_type' => 'nullable|string',
            'detention' => 'nullable|numeric',
            'deviation' => 'nullable|numeric',
            'misc_expense' => 'nullable|numeric',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'unloading_weight' => 'nullable|numeric|min:0',
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'linked_freight_booking_id' => 'nullable|exists:freight_bookings,id',
            'eway_bill_number' => 'nullable|string',
            'eway_bill_expiry' => 'nullable|date',
        ]);


        // Get the lorry receipt to get its LR number
        $lorryReceipt = LorryReceipt::find($data['lr_id']);
        if (!$lorryReceipt) {
            return redirect()->back()->withErrors(['lr_id' => 'Invalid lorry receipt']);
        }
        // Use the new TR amount from form data instead of existing
        $newTrAmount = $data['tr_amount'];

        // Store old values before update for proper amount calculation
        $oldDamageAmount = $freightBooking->damage_amount ?? 0;
        $oldShortageAmount = $freightBooking->shortage_amount ?? 0;
        $newDamageAmount = $data['damage_amount'] ?? 0;
        $newShortageAmount = $data['shortage_amount'] ?? 0;

        // Store old loading_weight and rate to detect changes for TDS calculation
        $oldLoadingWeight = $lorryReceipt->weight ?? 0;
        $oldTrFrRate = $freightBooking->tr_fr_rate ?? 0;
        $newTrFrRate = $data['tr_fr_rate'] ?? 0;
        $isDelivered = ($lorryReceipt->delivery_status === 'Delivered') || (isset($data['is_delivered']) && $data['is_delivered'] === '1');

        // Recalculate balance on the server side to ensure consistency
        // This ensures balance is always correct when TDS, TR amount, or other fields change
        $calculatedBalance = $newTrAmount
            - $data['advance_amount']
            - ($data['tds_amount'] ?? 0)
            + ($data['detention'] ?? 0)
            + ($data['deviation'] ?? 0)
            - ($data['misc_expense'] ?? 0)
            - $newDamageAmount
            - $newShortageAmount;

        // Update balance_amount with calculated value
        $data['balance_amount'] = $calculatedBalance;

        $linkedLrId = $lorryReceipt->linked_lr_id ?? null;

        // Update freight booking with lorry receipt's LR number
        $freightBooking->update(array_merge($data, [
            'lr_number' => $lorryReceipt->lr_number,
            'linked_lr_id' => $linkedLrId
        ]));

        // Refresh freight booking to get updated values
        $freightBooking->refresh();

        // Update lorry receipt status
        if ($lorryReceipt) {
            // Refresh the lorry receipt to get latest data (in case memo_id was updated via AJAX)
            $lorryReceipt->refresh();

            \Log::info('FREIGHT BOOKING UPDATE: Starting LR status update', [
                'freight_booking_id' => $freightBooking->id,
                'lr_id' => $lorryReceipt->id,
                'lr_number' => $lorryReceipt->lr_number,
                'current_memo_id' => $lorryReceipt->memo_id,
                'current_delivery_status' => $lorryReceipt->delivery_status,
                'is_delivered_from_form' => $data['is_delivered'] ?? 'not_set',
                'is_cancelled_from_form' => $data['is_cancelled'] ?? 'not_set',
                'old_damage' => $oldDamageAmount,
                'old_shortage' => $oldShortageAmount,
                'new_damage' => $newDamageAmount,
                'new_shortage' => $newShortageAmount
            ]);

            $deliveryStatus = 'Pending';
            if (isset($data['is_cancelled']) && $data['is_cancelled'] === '1') {
                // When freight booking is cancelled, lorry receipt goes back to Pending
                $deliveryStatus = 'Pending';
                $lorryReceipt->freight_booking_id = null; // Unlink freight booking
                \Log::info('FREIGHT BOOKING UPDATE: Setting status to Pending (cancelled)');
            } elseif (isset($data['is_delivered']) && $data['is_delivered'] === '1') {
                // Only set to 'Delivered' if memo_id exists on the lorry receipt
                $deliveryStatus = 'Delivered';
            } else {
                // If not delivered and not cancelled, set to In-Progress
                $deliveryStatus = 'In-Progress';
                \Log::info('FREIGHT BOOKING UPDATE: Setting status to In-Progress (default)');
            }

            // Update damage and shortage amounts on lorry receipt
            $lorryReceipt->damage_amount = $newDamageAmount;
            $lorryReceipt->shortage_amount = $newShortageAmount;

            // Update unloading_weight if provided (for loose weight mode)
            if (isset($data['unloading_weight'])) {
                $lorryReceipt->unloading_weight = $data['unloading_weight'];

                $loadingWeight = $lorryReceipt->weight;
                $unloadingWeight = $data['unloading_weight'];

                // Only recalculate tr_amount if loading_weight != unloading_weight
                // If weights are equal, use the manually entered tr_amount from form
                if (abs($loadingWeight - $unloadingWeight) > 0.01) {
                    // Weights are different - always recalculate tr_amount
                    $effectiveWeight = min($loadingWeight, $unloadingWeight);
                    $newTrAmount = round($freightBooking->tr_fr_rate * $effectiveWeight, 2);
                } else {
                    // Weights are equal - use manually entered tr_amount from form
                    $newTrAmount = $data['tr_amount'];
                }

                // TDS calculation logic:
                // Before delivery: TDS is calculated from freight amount (TR amount)
                // After delivery: TDS from loading_weight * rate; only change if loading_weight or rate changed
                $loadingWeightChanged = abs($oldLoadingWeight - $loadingWeight) > 0.01;
                $rateChanged = abs($oldTrFrRate - $newTrFrRate) > 0.01;

                $shouldRecalculateTds = false;
                if ($isDelivered) {
                    // After delivery: only recalculate TDS if loading_weight or rate changed
                    $shouldRecalculateTds = $loadingWeightChanged || $rateChanged;
                } else {
                    // Before delivery: always recalculate TDS (from freight amount)
                    $shouldRecalculateTds = true;
                }

                if ($shouldRecalculateTds) {
                    $tdsPercentage = $freightBooking->transporter_tds_percentage ?? 0;
                    if (! $isDelivered) {
                        // Before delivery: TDS = freight amount (TR amount) * tds_percentage
                        $newTdsAmount = round($newTrAmount * ($tdsPercentage / 100), 2);
                    } else {
                        // After delivery: TDS = loading_weight * rate * tds_percentage
                        $tdsBaseAmount = round($newTrFrRate * $loadingWeight, 2);
                        $newTdsAmount = round($tdsBaseAmount * ($tdsPercentage / 100), 2);
                    }
                } else {
                    // Keep existing TDS amount (loading_weight and rate didn't change)
                    $newTdsAmount = $freightBooking->tds_amount ?? 0;
                }

                // Update tr_amount and tds_amount if they differ from calculated values
                $trAmountChanged = abs($freightBooking->tr_amount - $newTrAmount) > 0.01;
                $tdsAmountChanged = abs(($freightBooking->tds_amount ?? 0) - $newTdsAmount) > 0.01;

                // Check if TDS can be changed before updating
                $canChangeTds = $freightBooking->canChangeTdsAmount();

                if ($trAmountChanged || ($tdsAmountChanged && $canChangeTds)) {
                    $freightBooking->tr_amount = $newTrAmount;
                    // Only update TDS if it can be changed and should be recalculated
                    if ($tdsAmountChanged && $canChangeTds && $shouldRecalculateTds) {
                        $freightBooking->tds_amount = $newTdsAmount;
                    }

                    // Recalculate balance_amount using current TDS amount (may be old if TDS cannot be changed)
                    $currentTdsAmount = $freightBooking->tds_amount ?? 0;
                    $freightBooking->balance_amount = $newTrAmount
                        - $freightBooking->advance_amount
                        - $currentTdsAmount
                        - ($freightBooking->damage_amount ?? 0)
                        - ($freightBooking->shortage_amount ?? 0)
                        + ($freightBooking->detention ?? 0)
                        + ($freightBooking->deviation ?? 0)
                        - ($freightBooking->misc_expense ?? 0);

                    $freightBooking->save();
                }
            } else {
                // If unloading_weight is not being updated, check if rate or loading_weight changed
                // and recalculate TDS accordingly
                $loadingWeight = $lorryReceipt->weight ?? 0;
                $loadingWeightChanged = abs($oldLoadingWeight - $loadingWeight) > 0.01;
                $rateChanged = abs($oldTrFrRate - $newTrFrRate) > 0.01;

                $shouldRecalculateTds = false;
                if ($isDelivered) {
                    // After delivery: only recalculate TDS if loading_weight or rate changed
                    $shouldRecalculateTds = $loadingWeightChanged || $rateChanged;
                } else {
                    // Before delivery: always recalculate TDS from freight amount
                    $shouldRecalculateTds = $loadingWeightChanged || $rateChanged;
                }

                if ($shouldRecalculateTds) {
                    $tdsPercentage = $freightBooking->transporter_tds_percentage ?? 0;
                    if (! $isDelivered) {
                        // Before delivery: TDS = freight amount (TR amount) * tds_percentage
                        $newTdsAmount = round($freightBooking->tr_amount * ($tdsPercentage / 100), 2);
                    } else {
                        // After delivery: TDS = loading_weight * rate * tds_percentage
                        $tdsBaseAmount = round($newTrFrRate * $loadingWeight, 2);
                        $newTdsAmount = round($tdsBaseAmount * ($tdsPercentage / 100), 2);
                    }

                    $tdsAmountChanged = abs(($freightBooking->tds_amount ?? 0) - $newTdsAmount) > 0.01;
                    $canChangeTds = $freightBooking->canChangeTdsAmount();

                    if ($tdsAmountChanged && $canChangeTds) {
                        $freightBooking->tds_amount = $newTdsAmount;

                        // Recalculate balance_amount with new TDS
                        $freightBooking->balance_amount = $freightBooking->tr_amount
                            - $freightBooking->advance_amount
                            - $newTdsAmount
                            - ($freightBooking->damage_amount ?? 0)
                            - ($freightBooking->shortage_amount ?? 0)
                            + ($freightBooking->detention ?? 0)
                            + ($freightBooking->deviation ?? 0)
                            - ($freightBooking->misc_expense ?? 0);

                        $freightBooking->save();
                    }
                }
            }

            // Recalculate LR amount and actual_amount using loose weight
            $loadingWeight = $lorryReceipt->weight;
            $unloadingWeight = $lorryReceipt->unloading_weight ?? $loadingWeight;

            // Only recalculate actual_amount if loading_weight != unloading_weight
            // If weights are equal, use the manually entered actual_amount from LR form (if available)
            if (abs($loadingWeight - $unloadingWeight) > 0.01) {
                // Weights are different - always recalculate actual_amount
                $effectiveWeight = min($loadingWeight, $unloadingWeight);
                $lorryReceipt->actual_amount = round($effectiveWeight * $lorryReceipt->fr_rate, 2);
            } else {
                // Weights are equal - actual_amount should be set from LR edit form
                // If not set, calculate it (for backward compatibility)
                if (!isset($lorryReceipt->actual_amount) || $lorryReceipt->actual_amount === null) {
                    $lorryReceipt->actual_amount = round($loadingWeight * $lorryReceipt->fr_rate, 2);
                }
            }

            // Calculate amount: actual_amount - damage - shortage
            $lorryReceipt->amount = max(0, round($lorryReceipt->actual_amount - $newDamageAmount - $newShortageAmount, 2));

            $lorryReceipt->delivery_status = $deliveryStatus;
            $lorryReceipt->save();

            \Log::info('FREIGHT BOOKING UPDATE: Final LR state after save', [
                'lr_id' => $lorryReceipt->id,
                'lr_number' => $lorryReceipt->lr_number,
                'memo_id' => $lorryReceipt->memo_id,
                'delivery_status' => $lorryReceipt->delivery_status
            ]);
        }

        return redirect()->route('freight_bookings.index')->with('success', 'Freight Booking updated successfully.');
    }

    public function destroy(FreightBooking $freight_booking)
    {
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $freight_booking->delete();
        return redirect()->route('freight_bookings.index')->with('success', 'Freight Booking deleted.');
    }

    /**
     * Process voucher payment for selected freight bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function voucherPayment(Request $request)
    {
        try {
            // dd($request->all());
            \Log::info('Voucher payment request received', $request->all());

            // Validate the request
            $validated = $request->validate([
                'payment_type_id' => 'required|exists:payment_types,id',
                'payment_mode_id' => 'required|exists:payment_modes,id',
                'payment_type' => 'required|string',
                'payment_mode' => 'required|string',
                'booking_ids' => 'required|array',
                'booking_ids.*' => 'required|integer|exists:freight_bookings,id',
                'payment_amounts' => 'required|array',
                'payment_amounts.*' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'reference_number' => 'nullable|string',
                'notes' => 'nullable|string',
                'pump_id' => 'nullable|exists:pumps,id',
                'date' => 'nullable|date',
            ]);

            \Log::info('Validation passed', $validated);

            // Begin transaction
            \DB::beginTransaction();

            $processedPayments = [];

            // Process each booking payment
            foreach ($validated['booking_ids'] as $index => $bookingId) {
                $amount = $validated['payment_amounts'][$index] ?? 0;

                if ($amount > 0) {
                    // Use row-level locking to prevent race conditions
                    // This ensures only one payment can be processed at a time for this booking
                    $freightBooking = FreightBooking::lockForUpdate()->findOrFail($bookingId);

                    // Validate payment amount doesn't exceed balance
                    if ($amount > $freightBooking->balance_amount) {
                        \DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Payment amount ₹{$amount} cannot exceed balance amount ₹{$freightBooking->balance_amount} for booking ID {$bookingId}.",
                            'errors' => [
                                'payment_amounts.' . $index => ["Payment amount cannot exceed balance amount ₹{$freightBooking->balance_amount}"]
                            ]
                        ], 422);
                    }

                    // Calculate effective payable amount (considering all deductions)
                    $effectivePayableAmount = $freightBooking->tr_amount
                        - ($freightBooking->tds_amount ?? 0)
                        - ($freightBooking->damage_amount ?? 0)
                        - ($freightBooking->shortage_amount ?? 0)
                        + ($freightBooking->detention ?? 0)
                        + ($freightBooking->deviation ?? 0)
                        - ($freightBooking->misc_expense ?? 0);

                    // Validate that advance_amount + payment won't exceed effective payable amount
                    // Allow small tolerance (0.01) for floating point precision
                    $newAdvanceAmount = $freightBooking->advance_amount + $amount;
                    if ($newAdvanceAmount > $effectivePayableAmount + 0.01) {
                        \DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Payment amount ₹{$amount} would cause advance amount (₹{$newAdvanceAmount}) to exceed effective payable amount ₹{$effectivePayableAmount} for booking ID {$bookingId}.",
                            'errors' => [
                                'payment_amounts.' . $index => ["Total advance amount cannot exceed effective payable amount ₹{$effectivePayableAmount}"]
                            ]
                        ], 422);
                    }

                    // Check for duplicate payment on the same day
                    $paymentDate = $validated['date'] ?? now()->toDateString();
                    $duplicatePayment = \App\Models\VoucherPayment::where('freight_booking_id', $bookingId)
                        ->where('amount', $amount)
                        ->where('payment_type_id', $validated['payment_type_id'])
                        ->where('payment_mode_id', $validated['payment_mode_id'])
                        ->whereDate('date', $paymentDate)
                        ->where('company_id', auth()->user()->company_id)
                        ->first();

                    if ($duplicatePayment && !($request->has('confirm_duplicate') && $request->input('confirm_duplicate') == '1')) {
                        \DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'warning' => true,
                            'message' => "A similar payment already exists for this booking on " . date('d-m-Y', strtotime($paymentDate)) . ".",
                            'duplicate_details' => [
                                'id' => $duplicatePayment->id,
                                'amount' => number_format($duplicatePayment->amount, 2),
                                'payment_type' => $duplicatePayment->payment_type,
                                'payment_mode' => $duplicatePayment->payment_mode,
                                'created_at' => $duplicatePayment->created_at->format('d-m-Y H:i:s'),
                                'date' => date('d-m-Y', strtotime($paymentDate))
                            ],
                            'booking_index' => $index,
                            'errors' => [
                                'payment_amounts.' . $index => ["Duplicate payment detected. Please confirm if you want to proceed."]
                            ]
                        ], 422);
                    }

                    // Get payment type and mode from their IDs
                    $paymentType = \App\Models\PaymentType::find($validated['payment_type_id']);
                    $paymentMode = \App\Models\PaymentMode::find($validated['payment_mode_id']);

                    // Determine payment status based on payment mode
                    $paymentStatus = 'pending'; // Default status

                    // Set status based on payment mode
                    if (strtolower($paymentMode->name) === 'cash' || strtolower($paymentMode->name) === 'adjust in advance') {
                        $paymentStatus = 'in-progress';
                    } else if (
                        strtolower($paymentMode->name) === 'bank transfer' ||
                        strtolower($paymentMode->name) === 'fuel' ||
                        strpos(strtolower($paymentMode->name), 'pump') !== false
                    ) {
                        $paymentStatus = 'pending';
                    }

                    // Create voucher payment record
                    $voucherPayment = \App\Models\VoucherPayment::create([
                        'freight_booking_id' => $bookingId,
                        'voucher_number' => $freightBooking->voucher_number,
                        'payment_type' => $paymentType->name,
                        'payment_mode' => $paymentMode->name,
                        'payment_type_id' => $validated['payment_type_id'],
                        'payment_mode_id' => $validated['payment_mode_id'],
                        'pump_id' => $validated['pump_id'] ?? null,
                        'amount' => $amount,
                        'remark' => $validated['notes'] ?? null,
                        'payment_status' => $paymentStatus,
                        'company_id' => auth()->user()->company_id,
                        'date' => $validated['date'] ?? now()->toDateString(),
                    ]);

                    \Log::info('Payment status set based on payment mode', [
                        'payment_mode' => $paymentMode->name,
                        'payment_status' => $paymentStatus
                    ]);

                    // Update freight booking balance
                    // $newadvance_amount = $freightBooking->advance_amount + $amount;
                    // $newBalance = max(0, $freightBooking->balance_amount - $amount);
                    // $freightBooking->update([
                    //     'balance_amount' => $newBalance,
                    //     'advance_amount' => $newadvance_amount
                    // ]);
                    // Update freight booking balance / transporter advance
                    if (strtolower($paymentMode->name) === 'fuel(paid to pump)') {
                        if (empty($validated['pump_id'])) {
                            throw ValidationException::withMessages([
                                'pump_id' => ['Please select Pump for fuel payment.']
                            ]);
                        }

                        // 👉 If pump_id is valid, you can proceed with pump-related logic
                        $pumpId = $validated['pump_id'];
                        // your pump payment logic here
                    }
                    if (strtolower($paymentMode->name) === 'adjust in advance') {
                        // make sure transporter exists for this booking
                        $transporter = \App\Models\Transporter::find($freightBooking->transporter_id);

                        if (!$transporter) {
                            throw new \Exception("Transporter not found for booking ID {$bookingId}");
                        }

                        if ($amount > $transporter->advance_amount) {
                            throw new \Exception("Allocated amount ₹{$amount} cannot exceed transporter available advance ₹{$transporter->advance_amount}");
                        }

                        // reduce from transporter advance
                        // $newTransporterAdvance = $transporter->advance_amount - $amount;
                        // $transporter->update([
                        //     'advance_amount' => $newTransporterAdvance
                        // ]);

                        // Recalculate balance_amount using full formula (advance_amount not changed for adjust in advance)
                        $effectivePayableAmount = $freightBooking->tr_amount
                            - ($freightBooking->tds_amount ?? 0)
                            - ($freightBooking->damage_amount ?? 0)
                            - ($freightBooking->shortage_amount ?? 0)
                            + ($freightBooking->detention ?? 0)
                            + ($freightBooking->deviation ?? 0)
                            - ($freightBooking->misc_expense ?? 0);

                        $freightBooking->balance_amount = $effectivePayableAmount - $freightBooking->advance_amount;
                        $freightBooking->save();

                        // \App\Models\AdvancePayment::create([
                        //     'company_id'      => $freightBooking->company_id,
                        //     'transporter_id'  => $transporter->id,
                        //     'advance_amount'  => -$amount,
                        //     'payment_date'    => now(), // current date-time
                        //     'remark'          => "LR#{$freightBooking->lr_number}",
                        // ]);
                        // Call AdvancePaymentController@store logic
                        $advanceRequest = new \Illuminate\Http\Request([
                            'company_id' => $freightBooking->company_id,
                            'transporter_id' => $transporter->id,
                            'advance_amount' => -$amount, // NEGATIVE because adjusting
                            'payment_date'   => now()->toDateString(),
                            'remark'         => "LR#{$freightBooking->lr_number}",
                        ]);

                        // mark request as ajax so it won’t redirect
                        $advanceRequest->headers->set('X-Requested-With', 'XMLHttpRequest');

                        app(\App\Http\Controllers\AdvancePaymentController::class)->store($advanceRequest);

                        \Log::info('Adjusted from transporter advance', [

                            'transporter_id' => $transporter->id,
                            'old_advance' => $transporter->getOriginal('advance_amount'),
                            'deducted' => $amount,
                            'new_advance' => $transporter->advance_amount,
                            'booking_id' => $bookingId,
                            'new_balance' => $freightBooking->balance_amount
                        ]);
                    }
                    // normal payment flow (cash, bank, fuel, etc.)
                    // Calculate effective payable amount (considering all deductions)
                    $effectivePayableAmount = $freightBooking->tr_amount
                        - ($freightBooking->tds_amount ?? 0)
                        - ($freightBooking->damage_amount ?? 0)
                        - ($freightBooking->shortage_amount ?? 0)
                        + ($freightBooking->detention ?? 0)
                        + ($freightBooking->deviation ?? 0)
                        - ($freightBooking->misc_expense ?? 0);

                    $newAdvanceAmount = $freightBooking->advance_amount + $amount;

                    // Recalculate balance_amount using full formula
                    $freightBooking->advance_amount = $newAdvanceAmount;
                    $freightBooking->balance_amount = $effectivePayableAmount - $freightBooking->advance_amount;

                    $freightBooking->save();

                    \Log::info('Processed normal payment for booking', [
                        'booking_id' => $bookingId,
                        'amount' => $amount,
                        'new_balance' => $freightBooking->balance_amount,
                        'new_advance_amount' => $newAdvanceAmount
                    ]);

                    $processedPayments[] = $voucherPayment->id;
                }
            }

            \DB::commit();

            \Log::info('Voucher payment completed successfully', [
                'processed_payments' => $processedPayments
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voucher payment processed successfully',
                'processed_count' => count($processedPayments),
                'processed_payments' => $processedPayments
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in voucher payment', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Voucher payment error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history for selected freight bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $validated = $request->validate([
                'freight_booking_ids' => 'required|array',
                'freight_booking_ids.*' => 'required|integer|exists:freight_bookings,id'
            ]);

            $payments = \App\Models\VoucherPayment::with([
                'freight_booking.lr',
                'freight_booking.transporter',
                'pump'
            ])
                ->whereIn('freight_booking_id', $validated['freight_booking_ids'])
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Payment history query result:', [
                'freight_booking_ids' => $validated['freight_booking_ids'],
                'payments_count' => $payments->count(),
                'payments' => $payments->toArray()
            ]);

            return response()->json([
                'success' => true,
                'payments' => $payments
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment history fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign memo ID to lorry receipts and update freight bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkMemoId(Request $request)
    {
        try {
            $validated = $request->validate([
                'memo_id' => 'required|string|max:255',
                'lr_ids' => 'required|array',
                'lr_ids.*' => 'required|integer|exists:lorry_receipts,id',
                'freight_booking_ids' => 'required|array',
                'freight_booking_ids.*' => 'required|integer|exists:freight_bookings,id'
            ]);

            $customers = \App\Models\LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->pluck('customer_id')
                ->unique();

            // ❌ If multiple customers found → return with warning
            if ($customers->count() > 1) {
                return redirect()->back()
                    ->with('warning', 'Selected Lorry Receipts belong to different customers. Please select LRs of the same customer only.')
                    ->withInput();
            }

            $customerId = $customers->first();

            // ✅ Check globally if this memo_id is already linked to a different customer
            $conflict = \App\Models\LorryReceipt::where('memo_id', $validated['memo_id'])
                ->where('company_id', auth()->user()->company_id)
                ->where('customer_id', '!=', $customerId)
                ->exists();

            if ($conflict) {
                return redirect()->back()
                    ->with('warning', 'This Memo ID is already used by another customer.')
                    ->withInput();
            }
            // ✅ Check if this memo_id is already linked to a different customer
            $conflictInvoiceGenerated = \App\Models\LorryReceipt::where('memo_id', $validated['memo_id'])
                ->where('company_id', auth()->user()->company_id)
                ->where('customer_id', $customerId)
                ->where('invoice_generated', 1)
                ->exists();

            if ($conflictInvoiceGenerated) {
                return redirect()->back()
                    ->with('warning', 'The invoice is generated on this memo id. Please choose a unique one.')
                    ->withInput();
            }


            \DB::transaction(function () use ($validated) {
                // Update memo_id and delivery_status for lorry receipts
                // Update memo_id and delivery_status for lorry receipts individually to trigger auditing
                $lorryReceipts = \App\Models\LorryReceipt::whereIn('id', $validated['lr_ids'])->get();
                foreach ($lorryReceipts as $lr) {
                    $lr->memo_id = $validated['memo_id'];
                    $lr->delivery_status = 'Delivered';
                    $lr->save();
                }

                // Update is_delivered for freight bookings individually to trigger auditing
                $freightBookings = FreightBooking::whereIn('id', $validated['freight_booking_ids'])->get();
                foreach ($freightBookings as $fb) {
                    $fb->is_delivered = 1;
                    $fb->save();
                }

                \Log::info('Bulk memo ID assignment completed', [
                    'memo_id' => $validated['memo_id'],
                    'lr_ids' => $validated['lr_ids'],
                    'freight_booking_ids' => $validated['freight_booking_ids']
                ]);
            });

            return redirect()->route('freight_bookings.index')
                ->with('success', 'Memo ID assigned successfully to ' . count($validated['lr_ids']) . ' lorry receipts and ' . count($validated['freight_booking_ids']) . ' freight bookings updated.');
        } catch (\Exception $e) {
            \Log::error('Bulk memo ID assignment error: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['general' => 'An error occurred while assigning memo ID: ' . $e->getMessage()]);
        }
    }
}
