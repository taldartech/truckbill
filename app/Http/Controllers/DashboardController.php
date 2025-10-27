<?php

namespace App\Http\Controllers;

use App\Models\FreightBooking as Booking;
use App\Models\Transporter;
use App\Models\Voucher;
use App\Models\VoucherPayment;
use App\Models\Truck;
use App\Models\LorryReceipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FreightBooking;

class DashboardController extends Controller
{
  public function index()
  {
    $now = Carbon::now();
    $startOfMonth = $now->copy()->startOfMonth();
    $endOfMonth = $now->copy()->endOfMonth();

    // Get monthly stats
    $monthlyStats = [
      'total_bookings' => Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
      'total_revenue' => VoucherPayment::whereBetween('created_at', [$startOfMonth, $endOfMonth])->where('company_id', auth()->user()->company_id)->sum('amount'),
      'pending_payments' => VoucherPayment::where('payment_status', 'pending')->where('company_id', auth()->user()->company_id)->count(),
      'active_transporters' => Transporter::where('is_owner', 0)->where('company_id', auth()->user()->company_id)->count(),
    ];

    // Get revenue data for the last 6 months
    $revenueData = VoucherPayment::select(
      DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
      DB::raw('SUM(amount) as total')
    )
      ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
      ->where('company_id', auth()->user()->company_id)
      ->groupBy('month')
      ->orderBy('created_at')
      ->pluck('total', 'month');

    // Get booking status distribution
    $bookingStatuses = [
      'delivered' => Booking::where('is_delivered', true)->where('company_id', auth()->user()->company_id)->count(),
      'cancelled' => Booking::where('is_cancelled', true)->where('company_id', auth()->user()->company_id)->count(),
      'in_progress' => Booking::where('is_delivered', false)
        ->where('is_cancelled', false)
        ->where('company_id', auth()->user()->company_id)
        ->count()
    ];

    // Recent bookings
    $recentBookings = Booking::with(['lr.consignor', 'lr.consignee'])
      ->where('company_id', auth()->user()->company_id)
      ->latest()
      ->take(5)
      ->get();

    // Pending approvals - Using lr relationship since vouchers are related to freight_bookings
    $pendingApprovals = Voucher::where('status', 'pending_approval')
      ->where('company_id', auth()->user()->company_id)
      ->with(['freightBooking.lr.consignor', 'freightBooking.lr.consignee'])
      ->latest()
      ->take(5)
      ->get();

    // Get top 5 trucks by number of lorry receipts
    $topTrucks = Truck::select('trucks.*', DB::raw('(SELECT COUNT(*) FROM lorry_receipts WHERE lorry_receipts.truck_id = trucks.id) as lr_count'))
      ->orderBy('lr_count', 'desc')
      ->take(5)
      ->get();

    // Get top 5 transporters by number of lorry receipts
    $topTransporters = Transporter::where('is_owner', 0)
      ->where('company_id', auth()->user()->company_id)
      ->withCount('lorryReceipts') // Count related lorry_receipts
      ->orderByDesc('lorry_receipts_count') // Sort by the count in descending order
      ->take(5) // Limit the results to the first 5
      ->get(['name', 'lorry_receipts_count']); // Select only the name and count

    // Get top 5 consignees by number of lorry receipts
    $topConsignees = \App\Models\Consignee::where('company_id', auth()->user()->company_id)
      ->withCount('lorryReceipts') // Count related lorry_receipts
      ->orderByDesc('lorry_receipts_count') // Sort by the count in descending order
      ->take(5) // Limit the results to the first 5
      ->get(['name', 'lorry_receipts_count']); // Select only the name and count



$expiredEwayBills = FreightBooking::with('lr')
    ->where('company_id', auth()->user()->company_id)
    ->where('is_delivered', 0)
    ->where('eway_bill_expiry', '<=', now()->addDays(2)->endOfDay())
    ->orderBy('eway_bill_expiry', 'asc')
    ->get();

    return view('dashboard', compact(
      'monthlyStats',
      'revenueData',
      'bookingStatuses',
      'recentBookings',
      'pendingApprovals',
      'topTrucks',
      'topTransporters',
      'topConsignees',
      'expiredEwayBills'
    ));
  }
}
