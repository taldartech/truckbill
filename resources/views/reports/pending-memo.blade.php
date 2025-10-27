@extends('layouts.app')

@section('title', 'Pending Memo')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Select2 custom styling for truck dropdown */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-result-truck {
            padding: 4px 0;
        }

        .truck-info {
            font-size: 14px;
            line-height: 1.4;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 12px;
            padding-right: 20px;
            color: #212529;
            line-height: 36px;
            background: var(--bs-body-bg);
            border: var(--bs-border-width) solid var(--bs-border-color);
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single:focus-visible {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
            outline: 0;
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 12px;
        }

        .select2-dropdown {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }

        .select2-results__option--highlighted {
            background-color: var(--bs-primary);
            color: white;
        }
    </style>
@endpush

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.pending-memo') }}" id="searchForm">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select select2" required onchange="fetchMemoID(this.value)">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option @if ($customerId == $customer->id) selected @endif value="{{ $customer->id }}">
                                    {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Memo ID <span class="text-danger">*</span></label>
                        <select name="memo_id" class="form-select select2" required>
                            <option value="">Select Memo ID</option>
                            @foreach ($memo_ids as $id)
                                <option @if ($memo_id == $id) selected @endif value="{{ $id }}">
                                    {{ $id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 text-end d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.pending-memo') }}?date={{ date('Y-m-d') }}"
                            class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$customerId && !$memo_id)
        <div class="alert alert-warning">
            Please select the Customer to generate the report.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Pending Memo</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2" target="_blank"
                            href="{{ route('reports.pending-memo-export', ['memo_id' => $memo_id]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                      
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                @if ($lrs->count() > 0)
                    @php
                        $totalAmount = 0;
                        $damageAmount = 0;
                        $shortageAmount = 0;
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Truck No</th>
                                    <th>From Place</th>
                                    <th>To Place</th>
                                    <th>Consignment No</th>
                                    <th>Date</th>
                                    <th>Lane</th>
                                    <th>QTY(MT)</th>
                                    <th>Rate</th>
                                    <th>Damage</th>
                                    <th>Shortage</th>
                                    <th>Amt.</th>
                                    <th>S.Tax</th>
                                    <th>S.B.Cess</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lrs as $lr)
                                    @php
                                        $damageAmount += $lr->damage_amount;
                                        $shortageAmount += $lr->shortage_amount;
                                        $amount = $lr->weight * $lr->fr_rate;
                                        $totalAmount += $amount;
                                    @endphp
                                    <tr>
                                        <td>{{ $lr->truck->truck_number ?? 'N/A' }}</td>
                                        <td>{{ $lr->source_city }}</td>
                                        <td>{{ $lr->destination_city }}</td>
                                        <td>{{ $lr->lr_number }}</td>
                                        <td>{{ date('d-m-Y', strtotime($lr->gr_date)) }}</td>
                                        <td>{{ $lr->lane }}</td>
                                        <td style="text-align: right">{{ number_format($lr->weight, 2) }}</td>
                                        <td style="text-align: right">{{ number_format($lr->fr_rate, 2) }}</td>
                                        <td style="text-align: right">{{ number_format($lr->damage_amount, 2) }}</td>
                                        <td style="text-align: right">{{ number_format($lr->shortage_amount, 2) }}</td>
                                        <td style="text-align: right">{{ number_format($amount, 2) }}</td>
                                        <td style="text-align: right">0.00</td>
                                        <td style="text-align: right">0.00</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="6"></td>
                                    <td colspan="4" style="text-align: right; font-weight: bold;">Total Amount:</td>
                                    <td style="text-align: right; font-weight: bold;">{{ number_format($totalAmount, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="6"></td>
                                    <td colspan="4" style="text-align: right; font-weight: bold;">Less :Shortage</td>
                                    <td style="text-align: right; font-weight: bold;">{{ number_format($shortageAmount, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="6"></td>
                                    <td colspan="4" style="text-align: right; font-weight: bold;">Less: Damage</td>
                                    <td style="text-align: right; font-weight: bold;">{{ number_format($damageAmount, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr>
                                    <td colspan="6"></td>
                                    <td colspan="4" style="text-align: right; font-weight: bold;">Net Amount</td>
                                    <td style="text-align: right; font-weight: bold;">{{ number_format($totalAmount - $shortageAmount - $damageAmount, 2) }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">No records found for the selected criteria.</div>
                @endif
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            });
        });
    </script>
    <script>
      function fetchMemoID(customerId) {
          if (!customerId) {
              $('select[name="memo_id"]').html('<option value="">Select Memo ID</option>');
              return;
          }
          $.ajax({
              url: '/invoices/pending-memo/' + customerId,
              method: 'GET',
              success: function(data) {
                  let options = '<option value="">Select Memo ID</option>';
                  data.forEach(function(memo) {
                      options += `<option value="${memo}">${memo}</option>`;
                  });
                  $('select[name="memo_id"]').html(options);
              },
              error: function() {
                  alert('Failed to fetch Memo IDs. Please try again.');
              }
          });
      }
    </script>
@endpush
