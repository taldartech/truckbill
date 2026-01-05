@extends('layouts.app')

@section('title', 'Memo Copy')

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
            color: var(--brand-text-primary);
            line-height: 36px;
            background: var(--brand-card-bg);
            border: 1px solid var(--brand-border);
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single:focus-visible {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.25rem var(--brand-primary-light);
            outline: 0;
            border-radius: 0.375rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 12px;
        }

        .select2-dropdown {
            border: 1px solid var(--brand-border);
            border-radius: 0.375rem;
        }

        .select2-results__option--highlighted {
            background-color: var(--brand-primary);
            color: white;
        }
    </style>
@endpush

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.memo-copy') }}" id="searchForm">
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
                        <a href="{{ route('reports.memo-copy') }}?date={{ date('Y-m-d') }}"
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
                        <h3 class="card-title mb-0">Memo Copy</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2" target="_blank"
                            href="{{ route('reports.memo-copy-export', ['memo_id' => $memo_id]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                        <a class="btn btn-danger ms-2" target="_blank"
                        href="{{ route('reports.memo-copy-export', ['memo_id' => $memo_id, 'type' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
              @include('reports.exports.memo-copy')
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
              url: '/invoices/all-memos/' + customerId,
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
