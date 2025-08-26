<?php

namespace App\Http\Controllers;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\Company;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;
use PDF;
use App\Exports\BookingsExport;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $companies = Company::orderBy('name')->get();
        $transporters = Transporter::orderBy('name')->get();
        
        // Default report data (last 30 days)
        $endDate = now()->endOfDay();
        $startDate = now()->subDays(30)->startOfDay();
        
        $query = FreightBooking::with(['company', 'transporter', 'sourceCity', 'destinationCity', 'truck'])
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        $bookings = $query->latest()->paginate(15);
        
        // Summary data
        $summary = [
            'total_bookings' => $bookings->total(),
            'total_freight' => $bookings->sum('total_amount'),
            'delivered' => $bookings->where('status', 'delivered')->count(),
            'in_transit' => $bookings->where('status', 'in_transit')->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
        ];
        
        return view('reports.index', [
            'companies' => $companies,
            'transporters' => $transporters,
            'bookings' => $bookings,
            'summary' => $summary,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);
    }

    /**
     * Generate and display/download the requested report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\View\View|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:basic,advanced',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:view,excel,pdf',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        
        // Get base query with filters applied
        $query = $this->getBaseQuery($request, $startDate, $endDate);
        
        // Get report data based on type
        if ($request->report_type === 'basic') {
            $data = $this->getBasicReportData($query, $startDate, $endDate);
            $view = 'reports.basic';
        } else {
            $data = $this->getAdvancedReportData($query, $startDate, $endDate);
            $view = 'reports.advanced';
        }

        // Handle different output formats
        if ($request->format === 'excel') {
            return Excel::download(new ReportExport($data, $view), 'report_'.now()->format('Y-m-d').'.xlsx');
        } 
        
        if ($request->format === 'pdf') {
            $pdf = PDF::loadView($view, $data);
            return $pdf->download('report_'.now()->format('Y-m-d').'.pdf');
        }

        // For HTML view
        return view($view, $data);
    }

    /**
     * Get the base query with common filters applied
     */
    private function getBaseQuery($request, $startDate, $endDate)
    {
        // Start with freight bookings within date range
        $query = FreightBooking::query()
            ->with([
                'lr', 
                'truck', 
                'transporter', 
                'company',
                'sourceCity',
                'destinationCity',
                'voucherPayments'
            ])
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Apply company filter if provided
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Apply transporter filter if provided
        if ($request->filled('transporter_id')) {
            $query->where('transporter_id', $request->transporter_id);
        }

        // Apply status filter if provided
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return $query;
    }

    /**
     * Get data for basic report
     */
    private function getBasicReportData($query, $startDate, $endDate)
    {
        $bookings = $query->get();
        
        // Calculate summary statistics
        $summary = [
            'total_bookings' => $bookings->count(),
            'total_freight' => $bookings->sum('total_amount'),
            'total_paid' => $bookings->sum('paid_amount'),
            'total_balance' => $bookings->sum('balance_amount'),
            'delivered' => $bookings->where('status', 'delivered')->count(),
            'in_transit' => $bookings->where('status', 'in_transit')->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
        ];

        return [
            'bookings' => $bookings,
            'summary' => $summary,
            'start_date' => $startDate->format('d-m-Y'),
            'end_date' => $endDate->format('d-m-Y'),
            'filters' => request()->all(),
        ];
    }

    /**
     * Get data for advanced report with detailed analytics
     */
    private function getAdvancedReportData($query, $startDate, $endDate)
    {
        $bookings = $query->get();
        
        // Group by transporter for performance analysis
        $transporterData = $bookings->groupBy('transporter_id')->map(function($items, $transporterId) {
            $transporter = $items->first()->transporter;
            return [
                'name' => $transporter ? $transporter->name : 'N/A',
                'total_bookings' => $items->count(),
                'total_freight' => $items->sum('total_amount'),
                'avg_freight' => $items->avg('total_amount'),
                'total_paid' => $items->sum('paid_amount'),
                'total_balance' => $items->sum('balance_amount'),
                'statuses' => $items->groupBy('status')->map->count()
            ];
        })->sortByDesc('total_freight');

        // Monthly trends
        $monthlyData = $bookings->groupBy(function($item) {
            return $item->created_at->format('Y-m');
        })->map(function($items) {
            return [
                'bookings' => $items->count(),
                'freight' => $items->sum('total_amount'),
                'paid' => $items->sum('paid_amount'),
                'balance' => $items->sum('balance_amount')
            ];
        });

        // Status distribution
        $statusDistribution = $bookings->groupBy('status')->map(function($items) {
            return [
                'count' => $items->count(),
                'percentage' => 0, // Will be calculated in the view
                'total_freight' => $items->sum('total_amount'),
                'avg_freight' => $items->avg('total_amount')
            ];
        });

        // Calculate percentages
        $totalBookings = $bookings->count();
        if ($totalBookings > 0) {
            foreach ($statusDistribution as $status => $data) {
                $statusDistribution[$status]['percentage'] = round(($data['count'] / $totalBookings) * 100, 2);
            }
        }

        return [
            'bookings' => $bookings,
            'transporterData' => $transporterData,
            'monthlyData' => $monthlyData,
            'statusDistribution' => $statusDistribution,
            'start_date' => $startDate->format('d-m-Y'),
            'end_date' => $endDate->format('d-m-Y'),
            'filters' => request()->all(),
        ];
    }

    /**
     * Generate truck reports
     */
    public function truckReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'truck_id' => 'nullable|exists:trucks,id',
            'format' => 'in:view,excel,pdf',
        ]);

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : now()->startOfMonth();
            
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : now()->endOfDay();

        $query = FreightBooking::with(['truck', 'transporter', 'company'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->filled('truck_id')) {
            $query->where('truck_id', $request->truck_id);
        }

        $bookings = $query->get();
        $trucks = Truck::orderBy('truck_number')->get();

        $truckData = $bookings->groupBy('truck_id')->map(function($items, $truckId) {
            $truck = $items->first()->truck;
            $totalKm = $items->sum('distance') ?? 0;
            $totalFreight = $items->sum('total_amount');
            $avgFreightPerKm = $totalKm > 0 ? $totalFreight / $totalKm : 0;
            
            return [
                'truck_number' => $truck ? $truck->truck_number : 'N/A',
                'transporter_name' => $truck && $truck->transporter ? $truck->transporter->name : 'N/A',
                'total_trips' => $items->count(),
                'total_km' => $totalKm,
                'total_freight' => $totalFreight,
                'avg_freight_per_km' => $avgFreightPerKm,
                'utilization_rate' => ($items->count() / 30) * 100, // Assuming 30 days in month
                'statuses' => $items->groupBy('status')->map->count()
            ];
        })->sortBy('truck_number');

        $data = [
            'truckData' => $truckData,
            'trucks' => $trucks,
            'start_date' => $startDate->format('d-m-Y'),
            'end_date' => $endDate->format('d-m-Y'),
            'filters' => $request->all(),
        ];

        if ($request->format === 'excel') {
            return Excel::download(new ReportExport($data, 'reports.trucks'), 'truck_report_'.now()->format('Y-m-d').'.xlsx');
        } 
        
        if ($request->format === 'pdf') {
            $pdf = PDF::loadView('reports.trucks', $data);
            return $pdf->download('truck_report_'.now()->format('Y-m-d').'.pdf');
        }

        return view('reports.trucks', $data);
    }
}
