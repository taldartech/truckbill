@extends('layouts.app')

@section('title', 'Good Receipt (Bilty) Copy')

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
    @php
        $rawTemplate = request()->input('template', $companyDefaultTemplate ?? 'classic');
        $template = \App\Support\LrCopyPdfTemplate::normalizeKey($rawTemplate);
    @endphp

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.lr-copy') }}" id="searchForm">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select select2" required onchange="fetchLrNumbers(this.value)">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option @if ($customerId == $customer->id) selected @endif value="{{ $customer->id }}">
                                    {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">LR Number <span class="text-danger">*</span></label>
                        <select name="lr_id" class="form-select select2" required>
                            <option value="">Select LR Number</option>
                            @foreach ($lrs as $lrOption)
                                <option @if ($lr_id == $lrOption->id) selected @endif value="{{ $lrOption->id }}">
                                    {{ $lrOption->lr_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 align-self-end p-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_freight" id="show_freight"
                                value="1" @if (request()->has('show_freight')) checked @endif>
                            <label class="form-check-label" for="show_freight">
                                Show Freight
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Format</label>
                        <select name="template" class="form-select">
                            <option value="classic" @selected($template === 'classic')>A — default table</option>
                            <option value="modern" @selected($template === 'modern')>B — green Form A</option>
                            <option value="compact" @selected($template === 'compact')>C — compact amber</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.lr-copy') }}?date={{ date('Y-m-d') }}"
                            class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (!$customerId && !$lr)
        <div class="alert alert-warning">
            Please select the Customer to generate the report.
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">Good Receipt (Bilty) Copy</h3>
                    </div>
                    <div class="d-flex">
                        <a class="btn btn-warning ms-2" target="_blank"
                            href="{{ route('reports.lr-copy-export', ['lr_id' => $lr_id, 'show_freight' => request()->has('show_freight') ? 1 : 0, 'template' => $template]) }}">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <iframe src="{{ route('reports.lr-copy-export', ['customer_id' => $customerId, 'lr_id' => $lr_id, 'show_freight' => request()->has('show_freight') ? 1 : 0, 'template' => $template]) }}"
                    style="width: 100%; height: 600px; border: none;"></iframe>
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
      function fetchLrNumbers(customerId) {
          if (!customerId) {
              $('select[name="lr_id"]').html('<option value="">Select LR Number</option>');
              return;
          }
          $.ajax({
              url: '/api/lorry-receipts/customer/' + customerId,
              method: 'GET',
              success: function(data) {
                  let options = '<option value="">Select LR Number</option>';
                  data.forEach(function(lr) {
                      options += `<option value="${lr.id}">${lr.lr_number}</option>`;
                  });
                  $('select[name="lr_id"]').html(options);
              },
              error: function() {
                  alert('Failed to fetch LR numbers. Please try again.');
              }
          });
      }
    </script>
@endpush
