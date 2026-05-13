@extends('layouts.app')

@section('title', 'Good Receipt (Bilty) Copy')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    @include('reports.partials.select2-customer-brand')
@endpush

@section('content')
    @php
        $rawTemplate = request()->input('template', $companyDefaultTemplate ?? 'classic');
        $template = \App\Support\LrCopyPdfTemplate::normalizeKey($rawTemplate);
    @endphp

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.lr-copy') }}" id="searchForm" class="tb-report-customer-select2-form">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="lr-copy-customer-select" class="form-select" required>
                            <option value="">Select Customer</option>
                            @if ($selectedCustomer)
                                <option value="{{ $selectedCustomer->id }}" selected>{{ $selectedCustomer->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">LR Number <span class="text-danger">*</span></label>
                        <select name="lr_id" id="lr-copy-lr-select" class="form-select select2" required>
                            <option value="">Select LR Number</option>
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
            const $lrSelect = $('#lr-copy-lr-select');
            const $customerSelect = $('#lr-copy-customer-select');
            const initialCustomerId = @json($customerId);
            const initialLrId = @json($lr_id ? (int) $lr_id : null);
            const $filterCardBody = $('#searchForm').closest('.card-body');

            const lrSelect2Opts = {
                theme: 'bootstrap-5',
                width: '100%',
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $filterCardBody,
            };

            window.fetchLrNumbers = function(customerId, selectedLrId) {
                if ($lrSelect.hasClass('select2-hidden-accessible')) {
                    $lrSelect.select2('destroy');
                }

                if (!customerId) {
                    $lrSelect.html('<option value="">Select LR Number</option>');
                    $lrSelect.select2(lrSelect2Opts);

                    return;
                }

                $.ajax({
                    url: '/api/lorry-receipts/customer/' + customerId,
                    method: 'GET',
                    success: function(data) {
                        let options = '<option value="">Select LR Number</option>';
                        const want = selectedLrId != null && selectedLrId !== '' ? String(selectedLrId) : '';
                        data.forEach(function(lr) {
                            const isSel = want !== '' && want === String(lr.id) ? ' selected' : '';
                            const label = $('<div>').text(lr.lr_number).html();
                            options += '<option value="' + lr.id + '"' + isSel + '>' + label + '</option>';
                        });
                        $lrSelect.html(options);
                        $lrSelect.select2(lrSelect2Opts);
                        if (want !== '') {
                            $lrSelect.val(want).trigger('change');
                        }
                    },
                    error: function() {
                        alert('Failed to fetch LR numbers. Please try again.');
                    },
                });
            };

            $customerSelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search customer…',
                allowClear: false,
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $filterCardBody,
                ajax: {
                    url: '/api/consignees/search',
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1,
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination && data.pagination.more
                            },
                        };
                    },
                },
            });

            $customerSelect.on('change', function() {
                window.fetchLrNumbers($(this).val(), null);
            });

            if (initialCustomerId) {
                window.fetchLrNumbers(String(initialCustomerId), initialLrId);
            } else {
                $lrSelect.select2(lrSelect2Opts);
            }
        });
    </script>
@endpush
