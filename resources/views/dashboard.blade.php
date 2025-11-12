@extends('layouts.app')

@section('title', 'Dashboard')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@push('styles')
    <style>
        .info-box {
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
            border-radius: .25rem;
            background: #fff;
            display: flex;
            margin-bottom: 1rem;
            min-height: 80px;
            padding: .5rem;
            position: relative;
        }

        .info-box .icon {
            border-radius: .25rem;
            -ms-flex-align: center;
            align-items: center;
            display: -ms-flexbox;
            display: flex;
            font-size: 1.875rem;
            -ms-flex-pack: center;
            justify-content: center;
            text-align: center;
            width: 70px;
        }

        .info-box .content {
            -ms-flex: 1;
            flex: 1;
            padding: 5px 10px;
        }

        .info-box .text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .info-box .number {
            display: block;
            font-weight: 700;
            margin-top: .25rem;
        }

        .card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0 0.375rem rgba(0, 0, 0, .1);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #4e73df;
            margin: 0;
        }

        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .chart-container {
            position: relative;
            min-height: 300px;
        }
    </style>
@endpush

@section('content')
    <div class="content">
        <div class="container-fluid">
            <!-- Info boxes -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info">
                            <i class="fas fa-truck"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Bookings</span>
                            <span class="info-box-number">{{ number_format($monthlyStats['total_bookings']) }}</span>
                            <small>This Month</small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success">
                            <i class="fa-solid fa-indian-rupee-sign"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Revenue</span>
                            <span class="info-box-number">₹{{ number_format($monthlyStats['total_revenue'], 2) }}</span>
                            <small>This Month</small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pending Payments</span>
                            <span class="info-box-number">{{ number_format($monthlyStats['pending_payments']) }}</span>
                            <small>Require Attention</small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Active Transporters</span>
                            <span class="info-box-number">{{ number_format($monthlyStats['active_transporters']) }}</span>
                            <small>Currently Active</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Row -->
            <div class="row">
                <!-- Recent Bookings -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Bookings</h3>
                            <div class="card-tools">
                                <a href="{{ route('freight_bookings.index') }}" class="btn btn-sm btn-primary">View All</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Consignor</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentBookings as $booking)
                                            <tr>
                                                <td>#{{ $booking->id }}</td>
                                                <td>{{ $booking->lr->consignor->name ?? 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $statusClass =
                                                            [
                                                                'Pending' => 'warning',
                                                                'In-Progress' => 'info',
                                                                'Delivered' => 'success',
                                                                'Cancelled' => 'danger',
                                                            ][$booking->lr->delivery_status] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge bg-{{ $statusClass }}">
                                                        {{ str_replace('_', ' ', ucfirst($booking->lr->delivery_status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('freight_bookings.show', $booking->id) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">No recent bookings</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 5 Lists -->
            <div class="row mt-4">
                <!-- Top 5 Trucks -->
                {{--  <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Top 5 Trucks</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Truck No</th>
                                        <th class="text-end">Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topTrucks as $truck)
                                    <tr>
                                        <td>{{ $truck->truck_no }}</td>
                                        <td class="text-end">{{ $truck->booking_count }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center">No data available</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> --}}

                <!-- Top 5 Transporters -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Top 5 Transporters</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th class="text-end">Bookings</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topTransporters as $transporter)
                                            <tr>
                                                <td>{{ Str::limit($transporter->name, 15) }}</td>
                                                <td class="text-end">{{ $transporter->lorry_receipts_count }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No data available</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Customers -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Top 5 Customers</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th class="text-end">Bookings</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topConsignees as $consignee)
                                            <tr>
                                                <td>{{ Str::limit($consignee->name, 15) }}</td>
                                                <td class="text-end">{{ $consignee->lorry_receipts_count }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No data available</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- show companies whose plan_end_date is todays or got expired--}}
            @if(auth()->user()->user_role_id==1 || auth()->user()->user_role_id==2)
            <div class="row mt-4">
                <div class="col-md-12 col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Companies whose plan_end_date is todays or got expired</h3>
                            <div class="card-tools">
                                
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Company Name</th>
                                            <th>Plan End Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($companies as $company)
                                            <tr>
                                                <td>{{ $company->name }}</td>
                                                <td>{{ $company->plan_end_date }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No data available</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        {{-- Expired Eway bills --}}
        <div class="row mt-4">
            <div class="col-md-12 col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Expired Eway Bills</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>SR no.</th>
                                        <th>LR Number</th>
                                        <th>Truck Number</th>
                                        <th>Driver Mobile Number</th>
                                        <th>Destination</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expiredEwayBills as $ewayBill)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $ewayBill->lr->lr_number }}</td>
                                        <td>{{ $ewayBill->lr->truck->truck_number }}</td>
                                        <td>{{ $ewayBill->lr?->truck?->driver?->mobile ?? '-' }}</td>

                                        <td>{{ $ewayBill->lr->destination_city }}</td>
                                        @php


                                        $expiryDate = \Carbon\Carbon::parse($ewayBill->eway_bill_expiry);
                                        $today = \Carbon\Carbon::today();
                                    @endphp

                                    <td
                                        @if ($expiryDate->isSameDay($today) || $expiryDate->lessThan($today))
                                            style="background-color: #FF0000; color: white;"
                                        @elseif ($expiryDate->isSameDay($today->copy()->addDay()))
                                            style="background-color: #FFFF00;"
                                        @else
                                            @if($expiryDate->isSameDay($today->copy()->addDays(2)))
                                            style="background-color: #00FF00;"
                                            @endif
                                        @endif
                                    >
                                        {{ $ewayBill->eway_bill_expiry }}
                                    </td>



                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No expired Eway bills</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table th,
        .table td {
            padding: 0.5rem;
        }

        .card-title {
            font-size: 1rem;
            margin-bottom: 0;
        }
    </style>
@endpush

@push('scripts')
    <!-- Additional scripts can be added here if needed -->
@endpush
