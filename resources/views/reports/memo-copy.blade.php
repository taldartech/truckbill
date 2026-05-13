@extends('layouts.app')

@section('title', 'Memo Copy')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    @include('reports.partials.select2-customer-brand')
@endpush

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.memo-copy') }}" id="searchForm" class="tb-report-customer-select2-form">
                <div class="row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="memo_copy_customer_id" class="form-select" required>
                            <option value=""></option>
                            @if ($presetCustomer)
                                <option value="{{ $presetCustomer->id }}" selected>{{ $presetCustomer->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Memo ID <span class="text-danger">*</span></label>
                        <select name="memo_id" id="memo_copy_memo_id" class="form-select" required>
                            <option value="">Select Memo ID</option>
                            @foreach ($memo_ids as $id)
                                <option @if ($memo_id == $id) selected @endif value="{{ $id }}">
                                    {{ $id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 text-end d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-brand-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('reports.memo-copy') }}?date={{ date('Y-m-d') }}"
                            class="btn btn-secondary">
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
        function fetchMemoID(customerId, selectedMemoId) {
            const $memo = $('#memo_copy_memo_id');
            if ($memo.hasClass('select2-hidden-accessible')) {
                $memo.select2('destroy');
            }

            if (!customerId) {
                $memo.html('<option value="">Select Memo ID</option>');
                $memo.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#searchForm').closest('.card-body'),
                });

                return;
            }

            $.ajax({
                url: `{{ url('/reports/customer-memos') }}/${encodeURIComponent(customerId)}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (! Array.isArray(data)) {
                        alert('Failed to fetch Memo IDs. Please try again.');

                        return;
                    }

                    let options = '<option value="">Select Memo ID</option>';
                    const sel = selectedMemoId != null && selectedMemoId !== '' ? String(selectedMemoId) : '';
                    data.forEach(function(memo) {
                        const isSel = sel !== '' && String(memo) === sel ? ' selected' : '';
                        options += `<option value="${memo}"${isSel}>${memo}</option>`;
                    });
                    $memo.html(options);
                    $memo.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#searchForm').closest('.card-body'),
                    });
                },
                error: function() {
                    alert('Failed to fetch Memo IDs. Please try again.');
                }
            });
        }

        $(document).ready(function() {
            const partiesAjax = {
                url: '/api/consignees/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1,
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || [],
                        pagination: {
                            more: Boolean(data.pagination && data.pagination.more),
                        },
                    };
                },
                cache: true,
            };

            const $customer = $('#memo_copy_customer_id');
            const initialCustomerId = @json($customerId);
            const initialMemoId = @json($memo_id);

            $customer.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: false,
                placeholder: 'Search customer…',
                minimumInputLength: 0,
                containerCssClass: 'tb-select2-container-brand',
                dropdownCssClass: 'tb-select2-dropdown-brand',
                dropdownParent: $('#searchForm').closest('.card-body'),
                ajax: partiesAjax,
            });

            $customer.on('change', function() {
                fetchMemoID($(this).val(), null);
            });

            if (initialCustomerId) {
                fetchMemoID(String(initialCustomerId), initialMemoId);
            } else {
                $('#memo_copy_memo_id').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $('#searchForm').closest('.card-body'),
                });
            }
        });
    </script>
@endpush
