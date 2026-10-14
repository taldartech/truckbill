@php
    $exceptions = $insights['exceptions'];
    $linkTrips = $linkTrips ?? false;
    $ewayHref = $ewayHref ?? null;
    $today = \Carbon\Carbon::today();
@endphp

<section class="dashboard-panel">
    <div class="dashboard-panel__header">
        <h2 class="dashboard-panel__title"><i class="bi bi-binoculars"></i> Trip watchlist</h2>
        <div class="dashboard-pills">
            <span class="dashboard-pill {{ $exceptions['eway_expired'] > 0 ? 'dashboard-pill--danger' : '' }}">{{ $exceptions['eway_expired'] }} e-way expired</span>
            <span class="dashboard-pill {{ $exceptions['eway_expiring'] > 0 ? 'dashboard-pill--warning' : '' }}">{{ $exceptions['eway_expiring'] }} expiring</span>
            <span class="dashboard-pill {{ $exceptions['long_running'] > 0 ? 'dashboard-pill--warning' : '' }}">{{ $exceptions['long_running'] }} running {{ \App\Services\Dashboard\BusinessInsightsService::LONG_RUNNING_TRIP_DAYS }}+ days</span>
            <span class="dashboard-pill {{ $exceptions['without_hire'] > 0 ? 'dashboard-pill--warning' : '' }}">{{ $exceptions['without_hire'] }} without hire</span>
            <span class="dashboard-pill">{{ $exceptions['cancellation_rate'] }}% cancelled</span>
        </div>
    </div>
    <div class="dashboard-panel__body dashboard-panel__body--flush">
        <div class="dashboard-grid dashboard-grid--2 dashboard-grid--divided">
            <div>
                <h3 class="dashboard-subhead dashboard-subhead--padded">Running longer than {{ \App\Services\Dashboard\BusinessInsightsService::LONG_RUNNING_TRIP_DAYS }} days</h3>
                @if (count($exceptions['long_running_trips']) === 0)
                    <div class="dashboard-empty">No delayed trips.</div>
                @else
                    <div class="table-responsive">
                        <table class="table dashboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>Bilty</th>
                                    <th>Route</th>
                                    <th>Truck</th>
                                    <th class="text-end">Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($exceptions['long_running_trips'] as $trip)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">
                                            @if ($linkTrips && $trip['freight_booking_id'])
                                                <a href="{{ route('freight_bookings.show', $trip['freight_booking_id']) }}">{{ $trip['lr_number'] }}</a>
                                            @else
                                                {{ $trip['lr_number'] }}
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $trip['route'] }}</td>
                                        <td class="text-nowrap">{{ $trip['truck'] }}</td>
                                        <td class="text-end fw-bold {{ $trip['days'] >= 14 ? 'text-danger' : 'text-warning' }}">{{ $trip['days'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div>
                <h3 class="dashboard-subhead dashboard-subhead--padded">E-way bills expired or expiring in {{ \App\Services\Dashboard\BusinessInsightsService::EWAY_WARNING_DAYS }} days</h3>
                @if ($exceptions['eway_alerts']->isEmpty())
                    <div class="dashboard-empty">All running trips have valid e-way bills.</div>
                @else
                    <div class="table-responsive">
                        <table class="table dashboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>E-way bill</th>
                                    <th>Bilty</th>
                                    <th>Truck</th>
                                    <th class="text-end">Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($exceptions['eway_alerts'] as $ewayBill)
                                    @php
                                        $expiry = \Carbon\Carbon::parse($ewayBill->eway_bill_expiry)->startOfDay();
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $ewayBill->eway_bill_number }}</td>
                                        <td class="text-nowrap">{{ $ewayBill->freightBooking?->lr?->lr_number ?? '—' }}</td>
                                        <td class="text-nowrap">{{ $ewayBill->freightBooking?->lr?->truck?->truck_number ?? '—' }}</td>
                                        <td class="text-end text-nowrap">
                                            <span class="dashboard-pill {{ $expiry->lt($today) ? 'dashboard-pill--danger' : 'dashboard-pill--warning' }}">
                                                {{ $expiry->lt($today) ? 'Expired' : ($expiry->isSameDay($today) ? 'Today' : $expiry->format('d M')) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($ewayHref)
                    <div class="dashboard-panel__footer dashboard-panel__footer--padded">
                        <span></span>
                        <a href="{{ $ewayHref }}" class="dashboard-link">E-way bill report <i class="bi bi-arrow-right-short"></i></a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
