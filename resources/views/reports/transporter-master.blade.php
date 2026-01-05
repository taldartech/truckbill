@extends('layouts.app')

@section('title', 'Transporter / Truck Owner Master Report')

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.transporter-master') }}" id="searchForm">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="transporter" {{ ($type ?? 'transporter') === 'transporter' ? 'selected' : '' }}>
                                Transporter
                            </option>
                            <option value="owner" {{ ($type ?? '') === 'owner' ? 'selected' : '' }}>
                                Truck Owner
                            </option>
                            <option value="both" {{ ($type ?? '') === 'both' ? 'selected' : '' }}>
                                Both
                            </option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.transporter-master') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (empty($transporters) || $transporters->isEmpty())
        <div class="alert alert-info">
            No records found for the selected criteria.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Transporter / Truck Owner Details</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2"
                            href="{{ route('reports.transporter-master-export', ['type' => $type ?? 'transporter']) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <style>
                .table-sticky {
                    width: 100%;
                    max-height: 400px;
                    overflow-y: auto;
                    overflow-x: auto;
                    display: block;
                    padding-top: 0px;
                }

                .table-sticky table {
                    border-collapse: collapse;
                    width: max-content;
                    min-width: 100%;
                }

                .select2-dropdown {
                    border: 1px solid var(--brand-border);
                    border-radius: 0.375rem;
                }

                .select2-results__option--highlighted {
                    background-color: var(--brand-primary);
                    color: white;
                }

                .table-sticky thead th {
                    position: sticky;
                    top: 0;
                    background: #fff;
                    z-index: 2;
                    border-bottom: 1px solid #ddd;
                }
            </style>
            <div class="card-body table-sticky">
                <table class="table table-bordered table-striped" style="font-size: 12px !important;">
                    <thead>
                        <tr>
                            <th>S. No</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Short Name</th>
                            <th>Contact Person Name</th>
                            <th>Mobile Number</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Country</th>
                            <th>PAN Number</th>
                            <th>PAN Active</th>
                            <th>GST Number</th>
                            <th>Beneficiary ID</th>
                            <th>TDS Percentage</th>
                            <th>Declaration Given</th>
                            <th>Total Trucks</th>
                            <th>Advance Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transporters as $index => $t)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $t->is_owner ? 'Truck Owner' : 'Transporter' }}</td>
                                <td>{{ $t->name }}</td>
                                <td>{{ $t->short_name }}</td>
                                <td>{{ $t->contact_person_name }}</td>
                                <td>{{ $t->mobile_number }}</td>
                                <td>{{ $t->address }}</td>
                                <td>{{ optional($t->city)->name }}</td>
                                <td>{{ optional($t->state)->name }}</td>
                                <td>{{ optional($t->country)->name }}</td>
                                <td>{{ $t->pan_number }}</td>
                                <td>{{ $t->pan_active ? 'Yes' : 'No' }}</td>
                                <td>{{ $t->gst_number }}</td>
                                <td>{{ $t->beneficiary_id }}</td>
                                <td>{{ $t->tds_percentage }}</td>
                                <td>{{ $t->is_owner ? ($t->declaration_given ? 'Yes' : 'No') : '-' }}</td>
                                <td>{{ $t->is_owner ? $t->total_trucks : '-' }}</td>
                                <td>{{ number_format($t->advance_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection


