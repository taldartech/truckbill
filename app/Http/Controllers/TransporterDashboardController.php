<?php

namespace App\Http\Controllers;

use App\Models\AdvancePayment;
use App\Models\FreightBooking;
use App\Models\Transporter;
use App\Models\VoucherPayment;
use Illuminate\Http\Request;

class TransporterDashboardController extends Controller
{
    private function getTransporterIds(): array
    {
        $masterId = auth()->user()->transporter_master_id;
        if (! $masterId) {
            return [];
        }
        return Transporter::where('transporter_master_id', $masterId)->pluck('id')->toArray();
    }

    public function freightBookings(Request $request)
    {
        $transporterIds = $this->getTransporterIds();
        if (empty($transporterIds)) {
            return view('transporter.freight_bookings', ['bookings' => collect()]);
        }

        $query = FreightBooking::with(['lr.consignor', 'lr.consignee', 'lr.truck', 'transporter.transporterMaster'])
            ->whereIn('transporter_id', $transporterIds);

        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->input('end_date', date('Y-m-d', strtotime('+7 days')));
        $query->whereHas('lr', fn ($q) => $q->whereDate('gr_date', '>=', $startDate)->whereDate('gr_date', '<=', $endDate));

        $bookings = $query->latest()->paginate(20)->withQueryString();

        return view('transporter.freight_bookings', compact('bookings', 'startDate', 'endDate'));
    }

    public function showFreightBooking(FreightBooking $freight_booking)
    {
        $transporterIds = $this->getTransporterIds();
        if (empty($transporterIds) || ! in_array($freight_booking->transporter_id, $transporterIds)) {
            abort(403, 'Unauthorized access to this good hire (lorry hire).');
        }

        $freight_booking->load(['lr.consignor', 'lr.consignee', 'lr.truck', 'transporter.transporterMaster', 'payments']);

        return view('transporter.freight_booking_show', ['freightBooking' => $freight_booking]);
    }

    public function payments(Request $request)
    {
        $transporterIds = $this->getTransporterIds();
        if (empty($transporterIds)) {
            return view('transporter.payments', ['voucherPayments' => collect(), 'advancePayments' => collect()]);
        }

        $voucherPayments = VoucherPayment::with(['freight_booking.lr'])
            ->whereHas('freight_booking', fn ($q) => $q->whereIn('transporter_id', $transporterIds))
            ->latest()
            ->paginate(20);

        $advancePayments = AdvancePayment::whereIn('transporter_id', $transporterIds)
            ->latest()
            ->paginate(20, ['*'], 'advance_page');

        return view('transporter.payments', compact('voucherPayments', 'advancePayments'));
    }
}
