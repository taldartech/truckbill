@extends('layouts.app')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
    <div class="container mt-4">
        <div class="col-sm-6">
            <h3 class="mb-0">Promo Code</h3>
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Promo Codes List</h3>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('promo_codes.create') }}" class="btn btn-brand-primary">
                        <i class="bi bi-plus"></i> Create
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="promoCodesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>SR No.</th>
                                <th>Promo Code</th>
                                <th>Discount Type</th>
                                <th>Discount Value</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Max Uses</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promoCodes as $promoCode)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $promoCode->code }}</td>
                                    <td>{{ ucfirst($promoCode->discount_type) }}</td>
                                    <td data-order="{{ $promoCode->discount_value }}">
                                        @if ($promoCode->discount_type == 'absolute')
                                        Rs.
                                        @endif
                                        {{ $promoCode->discount_value }}
                                        @if ($promoCode->discount_type == 'percentage')
                                        %
                                        @endif
                                    </td>
                                    <td data-order="{{ $promoCode->start_date }}">{{ \Carbon\Carbon::parse($promoCode->start_date)->format('Y-m-d') }}</td>
                                    <td data-order="{{ $promoCode->end_date ?? '' }}">{{ $promoCode->end_date ? \Carbon\Carbon::parse($promoCode->end_date)->format('Y-m-d') : 'N/A' }}</td>
                                    <td>{{ $promoCode->max_uses }}</td>
                                    <td>
                                        @if ($promoCode->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('promo_codes.edit', $promoCode->id) }}"
                                            class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('promo_codes.destroy', $promoCode->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this promo code?')"><i
                                                    class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#promoCodesTable').DataTable({
                responsive: true,
                paging: false,

                "order": [
                    [0, "asc"]
                ]
            });
        });
    </script>
@endpush
