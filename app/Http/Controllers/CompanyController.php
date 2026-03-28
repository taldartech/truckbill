<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\CustomInvoiceDetail;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\State;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Super admin: enter company context and redirect to dashboard.
     */
    public function enter(\App\Models\Company $company)
    {
        if (! Auth::check() || Auth::user()->userRole->name !== 'super_admin') {
            abort(403, 'Unauthorized.');
        }
        if ($company->user_id !== Auth::id()) {
            abort(403, 'You can only enter companies you own.');
        }
        session(['super_admin_company_id' => $company->id]);

        return redirect()->route('dashboard');
    }

    /**
     * Super admin: exit company context and return to company list.
     */
    public function exit()
    {
        if (! Auth::check() || Auth::user()->userRole->name !== 'super_admin') {
            abort(403, 'Unauthorized.');
        }
        session()->forget('super_admin_company_id');

        return redirect()->route('company.index');
    }

    public function index(Request $request)
    {

        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getCompaniesData($request);
        }

        return view('company.index');
    }

    private function getCompaniesData(Request $request)
    {

        //if user is master admin than show all companies
        if (auth()->user()->userRole->name === 'master_admin') {
            $query = \App\Models\Company::query();
        } else {
            $query = \App\Models\Company::query()->where('user_id', auth()->user()->id);
        }

        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('gst_number', 'like', "%{$searchValue}%")
                    ->orWhere('pan_number', 'like', "%{$searchValue}%")
                    ->orWhere('gst_scheme', 'like', "%{$searchValue}%")
                    ->orWhere('gst_type', 'like', "%{$searchValue}%")
                    ->orWhere('address', 'like', "%{$searchValue}%")
                    ->orWhere('contact_person', 'like', "%{$searchValue}%")
                    ->orWhere('contact_number', 'like', "%{$searchValue}%");
            });
        }

        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // GST Number
                            $query->where('gst_number', 'like', "%{$searchValue}%");
                            break;
                        case 3: // PAN Number
                            $query->where('pan_number', 'like', "%{$searchValue}%");
                            break;
                        case 4: // GST Scheme
                            $query->where('gst_scheme', 'like', "%{$searchValue}%");
                            break;
                        case 5: // GST Type
                            $query->where('gst_type', 'like', "%{$searchValue}%");
                            break;
                        case 6: // Address
                            $query->where('address', 'like', "%{$searchValue}%");
                            break;
                        case 7: // Contact Person
                            $query->where('contact_person', 'like', "%{$searchValue}%");
                            break;
                        case 8: // Contact Number
                            $query->where('contact_number', 'like', "%{$searchValue}%");
                            break;
                    }
                }
            }
        }

        // Handle sorting
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];

            switch ($orderColumn) {
                case 0: // ID
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // GST Number
                    $query->orderBy('gst_number', $orderDirection);
                    break;
                case 3: // PAN Number
                    $query->orderBy('pan_number', $orderDirection);
                    break;
                case 4: // GST Scheme
                    $query->orderBy('gst_scheme', $orderDirection);
                    break;
                case 5: // GST Type
                    $query->orderBy('gst_type', $orderDirection);
                    break;
                case 6: // Address
                    $query->orderBy('address', $orderDirection);
                    break;
                case 7: // Contact Person
                    $query->orderBy('contact_person', $orderDirection);
                    break;
                case 8: // Contact Number
                    $query->orderBy('contact_number', $orderDirection);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get total count before pagination
        $totalRecords = \App\Models\Company::where('user_id', auth()->user()->id)->count();
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $companies = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $companies->map(function ($company, $index) use ($start) {
            return [
                'id' => $index + $start + 1,
                'name' => $company->name,
                'gst_number' => $company->gst_number ?: '-',
                'pan_number' => $company->pan_number ?: '-',
                'gst_scheme' => $company->gst_scheme ? ucfirst($company->gst_scheme) : '-',
                'gst_type' => $company->gst_type ? ucfirst($company->gst_type) : '-',
                'address' => $company->address ?: '-',
                'contact_person' => $company->contact_person ?: '-',
                'contact_number' => $company->contact_number ?: '-',
                'actions' => $this->getActionButtons($company)
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    private function getActionButtons($company)
    {
        $buttons = '<div class="d-flex gap-1">';

        // Enter button (super_admin only) - company listing only, for companies they own
        if (auth()->check() && auth()->user()->userRole->name === 'super_admin' && $company->user_id === auth()->id()) {
            $enterUrl = route('company.enter', $company);
            $buttons .= '<a href="' . $enterUrl . '" class="btn btn-sm btn-success" title="Enter company panel">
                            <i class="bi bi-box-arrow-in-right"></i></a>';
        }

        // Edit button - always allow edit; GST scheme change restricted in edit form when invoices exist
        if (auth()->user()->userRole->name !== 'master_admin') {
            $editUrl = route('company.edit', $company->id);
            $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-edit" title="Edit Company">
                    <i class="bi bi-pencil"></i></a>';



            // Delete button
            $deleteUrl = route('company.destroy', $company->id);
            $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this company?\')">';
            $buttons .= csrf_field() . method_field('DELETE');
            $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Company">';
            $buttons .= '<i class="bi bi-trash"></i></button></form>';

            $buttons .= '</div>';
            return $buttons;
        } else {
            if ($company->is_blocked) {
                $unblockUrl = route('company.unblock', $company->id);
                $buttons .= '<a href="' . $unblockUrl . '" class="btn btn-sm btn-danger" title="Unblock User"
                          onclick="return confirm(\'Are you sure you want to unblock this company?\');">
                          <i class="bi bi-unlock"></i></a>';
            } else {
                $blockUrl = route('company.block', $company->id);
                $buttons .= '<a href="' . $blockUrl . '" class="btn btn-sm btn-edit" title="Block User"
                          onclick="return confirm(\'Are you sure you want to block this company?\');">
                          <i class="bi bi-lock"></i></a>';
            }
            $buttons .= '</div>';
            return $buttons;
        }
    }
    public function block($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->is_blocked = true;
        $company->save();
        return redirect()->back()->with('success', 'Company blocked successfully');
    }
    public function unblock($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->is_blocked = false;
        $company->save();
        return redirect()->back()->with('success', 'Company unblocked successfully');
    }



    public function edit($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $countries = \App\Models\Country::all();
        $states = \App\Models\State::where('country_id', $company->country_id)->get();
        $cities = \App\Models\City::where('state_id', $company->state_id)->get();
        $companySlabs = config('payments.gst_slabs');

        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        if ($currentMonth >= 4) {
            $fyStart = Carbon::create($currentYear, 4, 1)->startOfDay();
            $fyEnd = Carbon::create($currentYear + 1, 3, 31)->endOfDay();
        } else {
            $fyStart = Carbon::create($currentYear - 1, 4, 1)->startOfDay();
            $fyEnd = Carbon::create($currentYear, 3, 31)->endOfDay();
        }

        $hasInvoicesInFy = \App\Models\Invoice::where('company_id', $company->id)
            ->whereBetween('generated_date', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->exists();

        return view('company.edit', compact('company', 'countries', 'states', 'cities', 'companySlabs', 'hasInvoicesInFy', 'fyStart', 'fyEnd'));
    }

    public function update(Request $request, $id)
    {
        $company = \App\Models\Company::findOrFail($id);

        $newGstScheme = $request->input('gst_scheme');
        $oldGstScheme = $company->gst_scheme;
        $gstSchemeChanged = $newGstScheme !== $oldGstScheme;

        if ($gstSchemeChanged && $newGstScheme !== null) {
            $now = Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;

            if ($currentMonth >= 4) {
                $fyStart = Carbon::create($currentYear, 4, 1)->startOfDay();
                $fyEnd = Carbon::create($currentYear + 1, 3, 31)->endOfDay();
            } else {
                $fyStart = Carbon::create($currentYear - 1, 4, 1)->startOfDay();
                $fyEnd = Carbon::create($currentYear, 3, 31)->endOfDay();
            }

            $hasInvoices = \App\Models\Invoice::where('company_id', $company->id)
                ->whereBetween('generated_date', [$fyStart->toDateString(), $fyEnd->toDateString()])
                ->exists();

            if ($hasInvoices) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['gst_scheme' => 'Cannot change GST scheme. Invoices have already been created for the current financial year (April '.$fyStart->year.' - March '.$fyEnd->year.').']);
            }
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/[A-Za-z]/', // ✅ ensures at least one alphabet is present
            ],
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value && \App\Models\Company::where('gst_number', $value)->where('id', '!=', $id)->exists()) {
                        $fail('The GST number has already been taken.');
                    }
                },
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value && \App\Models\Company::where('pan_number', $value)->where('id', '!=', $id)->exists()) {
                        $fail('The PAN number has already been taken.');
                    }
                },
            ],
            'company_type' => 'nullable|in:Broker,Agency,Both',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:250',
            'invoice_template' => 'nullable|in:classic,modern,compact',
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
                'nullable',
                'digits:10',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value && \App\Models\Company::where('contact_number', $value)->where('id', '!=', $id)->exists()) {
                        $fail('The contact number has already been taken.');
                    }
                },
            ],


            'plan_end_date' => 'nullable|date',
            'lr_credits' => 'nullable|integer|min:0',
            'truck_credits' => 'nullable|integer|min:0',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            // 'pin_code' => 'nullable|string|max:10',
            'pin_code' => 'nullable|digits_between:1,10',
            'gst_slab' => [
                'required',
                Rule::in(config('payments.gst_slabs')),
            ],

        ], [
            'name.regex' => 'The name must contain at least one alphabetic character.',
            'pin_code.max' => 'The pin code must be a maximum of 10 characters.',
        ]);
        // dd($validated);
        if ($request->hasFile('company_logo')) {
            // Store the file in the 'public/company_logos' directory
            $path = $request->file('company_logo')->store('company_logos', 'public');

            $validated['company_logo'] = $path;
        }
        $validated['user_id'] = auth()->user()->id;
        if (!$request->filled('plan_end_date')) {
            // You can decide the logic:
            // Option 1: keep existing plan_end_date
            $validated['plan_end_date'] = $company->plan_end_date;
        }

        // ✅ Default values if not set
        $validated['lr_credits'] = $request->input('lr_credits', $company->lr_credits ?? 0);
        $validated['truck_credits'] = $request->input('truck_credits', $company->truck_credits ?? 0);

        $company->update($validated);
        return redirect()->route('company.index')->with('success', 'Company updated successfully!');
    }

    public function destroy($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->delete();
        return redirect()->route('company.index')->with('success', 'Company deleted successfully!');
    }

    /**
     * Generate a short company code from the provided name.
     * Uses initials from the first two words (alphabetic), otherwise falls back to first 5 letters.
     */
    private function generateShortNameFromCompanyName(?string $companyName): string
    {
        $name = trim((string) $companyName);
        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = [];

        foreach ($words as $word) {
            if (count($initials) >= 2) {
                break;
            }

            if (preg_match('/[a-zA-Z]/', $word, $matches) === 1) {
                $initials[] = strtoupper($matches[0]);
            }
        }

        if (count($initials) >= 2) {
            return implode('', $initials);
        }

        $lettersOnly = preg_replace('/[^a-zA-Z]/', '', $name) ?: '';

        return strtoupper(substr($lettersOnly, 0, 5));
    }
    public function create()
    {

        $companySlabs = config('payments.gst_slabs');

        return view('company.create', compact('companySlabs'));
    }

    public function store(Request $request)
    {
        $companyName = (string) $request->input('name', '');
        $incomingShortName = (string) $request->input('short_name', '');

        if (trim($incomingShortName) === '') {
            $incomingShortName = $this->generateShortNameFromCompanyName($companyName);
        }

        // Keep DB/invoice-friendly codes: uppercase A-Z0-9 only
        $normalizedShortName = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $incomingShortName)));
        $request->merge(['short_name' => $normalizedShortName]);

        $validated = $request->validate([


            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/[A-Za-z]/', // ✅ ensures at least one alphabet is present
            ],

            'short_name' => [
                'required',
                'string',
                'max:255',
                'unique:companies,short_name',
            ],


            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                function ($attribute, $value, $fail) {
                    if ($value && \App\Models\Company::where('gst_number', $value)->exists()) {
                        $fail('The GST number has already been taken.');
                    }
                },
            ],

            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                function ($attribute, $value, $fail) {
                    if ($value && \App\Models\Company::where('pan_number', $value)->exists()) {
                        $fail('The PAN number has already been taken.');
                    }
                },
            ],
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
                'nullable',
                'digits:10',
                function ($attribute, $value, $fail) {
                    if ($value && \App\Models\Company::where('contact_number', $value)->exists()) {
                        $fail('The contact number has already been taken.');
                    }
                },
            ],
            'company_type' => 'nullable|in:Broker,Agency,Both',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:250',
            'invoice_template' => 'nullable|in:classic,modern,compact',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            // 'pin_code' => 'nullable|string|max:10',
            'pin_code' => 'nullable|digits_between:1,10',
            'gst_slab' => [
                'required',
                Rule::in(config('payments.gst_slabs')),
            ],


        ], [
            'name.regex' => 'The name must contain at least one alphabetic character.',
            'pin_code.max' => 'The pin code must be a maximum of 10 characters.',
        ]);

        // Set default GST type to regular if not provided
        if ($request->hasFile('company_logo')) {
            // Store the file in the 'public/company_logos' directory
            $path = $request->file('company_logo')->store('company_logos', 'public');
            $validated['company_logo'] = $path;
        }
        $validated['user_id'] = auth()->user()->id;
        if (!$request->filled('gst_type')) {
            $validated['gst_type'] = 'regular';
        }
        if (!$request->filled('invoice_template')) {
            $validated['invoice_template'] = 'classic';
        }

        // ✅ Automatically set plan_end_date = created_at + 45 days
        $validated['plan_end_date'] = Carbon::now()->addDays(44)->toDateString();

        // ✅ Optional: Set default credits
        $validated['lr_credits'] = 250;
        $validated['truck_credits'] = null;

        $validated['short_name'] = $normalizedShortName;

        \App\Models\Company::create($validated);
        return redirect()->route('company.index')->with('success', 'Company registered successfully!,You are eligible for 45 days free plan');
    }

    /**
     * HTML preview of party-bill PDF templates (same Blade as real PDF) for the given company.
     */
    public function invoiceTemplatePreview(Request $request, Company $company)
    {
        $this->authorizeCompanyAccess($company);

        $template = (string) $request->query('template', 'classic');
        $company->loadMissing(['city', 'state']);

        $data = $this->buildDemoPartyBillPreviewData($company);
        $data['company'] = $company;

        $view = match ($template) {
            'modern' => 'reports.exports.party-bill-pdf-modern',
            'compact' => 'reports.exports.party-bill-pdf-compact',
            default => 'reports.exports.party-bill-pdf',
        };

        if ($request->query('format') === 'pdf') {
            return $this->streamPartyBillPreviewPdf($view, $data, 'party_bill_preview_'.$template);
        }

        $data['partyBillInvoiceUsePdfLogoPath'] = false;

        return response()->view($view, $data);
    }

    /**
     * Demo preview for company create form (no saved company yet).
     */
    public function invoiceTemplateDemo(Request $request)
    {
        $company = $this->buildDemoCompanyFromQuery($request);

        $data = $this->buildDemoPartyBillPreviewData($company);
        $data['company'] = $company;

        $template = (string) $request->query('template', 'classic');
        $view = match ($template) {
            'modern' => 'reports.exports.party-bill-pdf-modern',
            'compact' => 'reports.exports.party-bill-pdf-compact',
            default => 'reports.exports.party-bill-pdf',
        };

        if ($request->query('format') === 'pdf') {
            return $this->streamPartyBillPreviewPdf($view, $data, 'party_bill_demo_'.$template);
        }

        $data['partyBillInvoiceUsePdfLogoPath'] = false;

        return response()->view($view, $data);
    }

    /**
     * Same PDF engine as Party Bill export (DomPDF).
     */
    private function streamPartyBillPreviewPdf(string $view, array $data, string $downloadBaseName): Response
    {
        $data['partyBillInvoiceUsePdfLogoPath'] = true;
        $html = view($view, $data)->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->stream($downloadBaseName.'.pdf');
    }

    private function authorizeCompanyAccess(Company $company): void
    {
        if (Auth::user()->userRole->name === 'master_admin') {
            return;
        }

        if ($company->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function buildDemoCompanyFromQuery(Request $request): Company
    {
        $company = new Company([
            'name' => $request->query('name', 'Your Company'),
            'address' => $request->query('address', 'Sample address line'),
            'pin_code' => $request->query('pin_code', '314002'),
            'gst_number' => $request->query('gst_number', '29AAACH7409R1ZX'),
            'pan_number' => $request->query('pan_number', 'AABAD1452A'),
            'contact_number' => $request->query('contact_number', '9999999999'),
            'bank' => $request->query('bank', 'KOTAK MAHINDRA BANK'),
            'account_number' => $request->query('account_number', '5847918143'),
            'ifsc' => $request->query('ifsc', 'KKBK0003538'),
        ]);

        $city = new City(['name' => $request->query('city', 'Udaipur')]);
        $state = new State(['name' => $request->query('state', 'Rajasthan'), 'code' => '08']);
        $company->setRelation('city', $city);
        $company->setRelation('state', $state);

        return $company;
    }

    /**
     * @return array{invoice: Invoice, customDetails: \Illuminate\Support\Collection, lrs: \Illuminate\Support\Collection, totalAmount: float, cgst: float, sgst: float, igst: float}
     */
    private function buildDemoPartyBillPreviewData(Company $company): array
    {
        $company->loadMissing(['city', 'state']);

        $city = $company->city ?? new City(['name' => 'Ahmedabad']);
        $state = $company->state ?? new State(['name' => 'Gujarat', 'code' => '24']);

        $consignee = new Consignee([
            'name' => 'Sample Customer',
            'gst_number' => '24AAAAA0000A1Z5',
            'pan_number' => 'AABCD1234E',
        ]);
        $consignee->setRelation('city', $city);
        $consignee->setRelation('state', $state);

        $truck = new Truck(['truck_number' => 'RJ27AC1234']);
        $lr = new LorryReceipt([
            'lr_number' => 'test001',
            'destination_city' => 'Jaipur',
            'invoice_number' => '2331231233',
            'gr_date' => now()->toDateString(),
            'weight' => 10,
            'unloading_weight' => 10,
            'no_of_units' => 100,
            'fr_rate' => 100,
            'shortage_amount' => 0,
            'damage_amount' => 0,
            'actual_amount' => 50,
        ]);
        $lr->setRelation('truck', $truck);

        $detail = new CustomInvoiceDetail([
            'portal_rate' => 100,
            'diff' => 10,
            'amount' => 50,
            'remark' => 'demo',
        ]);
        $detail->setRelation('lorryReceipt', $lr);

        $baseAmount = 50.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 2.5;

        $invoice = new Invoice([
            'id' => 0,
            'manual_invoice_number' => 'PREVIEW123',
            'invoice_number' => '3212322',
            'invoice_type' => 'Reverse',
            'custom_invoice' => 'rate_difference',
            'generated_date' => now()->toDateString(),
            'total_amount' => 52.50,
            'base_amount' => $baseAmount,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
        ]);
        $invoice->setRelation('company', $company);
        $invoice->setRelation('consignee', $consignee);

        return [
            'invoice' => $invoice,
            'customDetails' => collect([$detail]),
            'lrs' => collect(),
            'totalAmount' => $baseAmount,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
        ];
    }
}
