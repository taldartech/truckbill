@extends('layouts.app')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
@endpush

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Company Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (auth()->user()->userRole->name !== 'master_admin')
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('company.create') }}" class="btn btn-brand-primary">
            <i class="bi bi-plus"></i> Create
        </a>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Companies List</h3>
        </div>
        <div class="card-body">
            <table id="companiesTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 80px;">SR No.</th>
                        <th>Name</th>
                        <th>GST Number</th>
                        <th>PAN Number</th>
                        <th>GST Scheme</th>
                        <th>GST Type</th>
                        <th>Address</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @if (auth()->user()->userRole->name === 'master_admin')
        <div class="modal fade" id="subscriptionModal" tabindex="-1" aria-labelledby="subscriptionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="subscriptionForm" method="POST" action="">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="subscription_company_id" id="subscription_company_id" value="{{ old('subscription_company_id') }}">
                        <input type="hidden" name="subscription_company_name" id="subscription_company_name_input" value="{{ old('subscription_company_name') }}">
                        <div class="modal-header">
                            <h5 class="modal-title" id="subscriptionModalLabel">Update Subscription</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3 text-muted">
                                Updating plan for: <strong id="subscription_company_name">{{ old('subscription_company_name', '-') }}</strong>
                            </p>
                            <div class="mb-3">
                                <label class="form-label" for="plan_end_date">Plan End Date</label>
                                <input type="date" id="plan_end_date" name="plan_end_date" class="form-control" value="{{ old('plan_end_date') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="lr_credits">LR Credits</label>
                                <input type="number" id="lr_credits" name="lr_credits" class="form-control" min="0" value="{{ old('lr_credits') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="truck_credits">Truck Credits</label>
                                <input type="number" id="truck_credits" name="truck_credits" class="form-control" min="0" value="{{ old('truck_credits') }}" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand-primary">Update Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
<script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

<script>
$(document).ready(function() {
    const subscriptionModalElement = document.getElementById('subscriptionModal');
    const subscriptionModal = subscriptionModalElement ? new bootstrap.Modal(subscriptionModalElement) : null;
    const subscriptionForm = $('#subscriptionForm');

    $('#companiesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("company.index") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                width: '80px',
                className: 'text-center'
            },
            {
                data: 'name',
                name: 'name'
            },
            {
                data: 'gst_number',
                name: 'gst_number',
                width: '150px'
            },
            {
                data: 'pan_number',
                name: 'pan_number',
                width: '120px'
            },
            {
                data: 'gst_scheme',
                name: 'gst_scheme',
                width: '120px'
            },
            {
                data: 'gst_type',
                name: 'gst_type',
                width: '100px'
            },
            {
                data: 'address',
                name: 'address'
            },
            {
                data: 'contact_person',
                name: 'contact_person',
                width: '150px'
            },
            {
                data: 'contact_number',
                name: 'contact_number',
                width: '130px'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                width: '120px',
                className: 'text-center'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12 col-md-12"B>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            {
                extend: 'excel',
                className: 'btn btn-secondary btn-sm',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel'
            }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            zeroRecords: "No matching records found",
            emptyTable: "No data available in table",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: true,
        autoWidth: false,
        stateSave: true,
        searchDelay: 500,
        scrollX: true,
        initComplete: function() {
            // Add individual column search
            this.api().columns([1, 2, 3, 4, 5, 6, 7, 8]).every(function() {
                var column = this;
                var $header = $(column.header());
                var title = $header.text().trim();

                // Wrap existing text in a div to keep it at top
                $header.html('<div style="margin-bottom: 5px;">' + title + '</div>');

                // Create input element
                var input = $('<input type="text" class="form-control form-control-sm" placeholder="Search ' + title + '" />')
                    .appendTo($header)
                    .on('keyup change clear', function() {
                        if (column.search() !== this.value) {
                            column.search(this.value).draw();
                        }
                    });
            });
        }
    });

    // Handle delete confirmation
    $(document).on('submit', 'form[action*="destroy"]', function(e) {
        if (!confirm('Are you sure you want to delete this company?')) {
            e.preventDefault();
            return false;
        }
    });

    // Open subscription modal with row data
    $(document).on('click', '.js-update-subscription', function() {
        if (!subscriptionModalElement) {
            return;
        }

        const companyId = $(this).data('company-id');
        const companyName = $(this).data('company-name');
        const planEndDate = $(this).data('plan-end-date');
        const lrCredits = $(this).data('lr-credits');
        const truckCredits = $(this).data('truck-credits');

        $('#subscription_company_id').val(companyId);
        $('#subscription_company_name_input').val(companyName);
        $('#subscription_company_name').text(companyName);
        $('#plan_end_date').val(planEndDate);
        $('#lr_credits').val(lrCredits);
        $('#truck_credits').val(truckCredits);

        subscriptionForm.attr('action', "{{ url('/company') }}/" + companyId + "/subscription");
        subscriptionModal.show();
    });

    // Confirmation popup on subscription submit
    subscriptionForm.on('submit', function(e) {
        if (!confirm('Are you sure you want to update this company subscription plan?')) {
            e.preventDefault();
            return false;
        }
    });

    @if ($errors->has('plan_end_date') || $errors->has('lr_credits') || $errors->has('truck_credits'))
        @if (old('subscription_company_id'))
            subscriptionForm.attr('action', "{{ url('/company') }}/{{ old('subscription_company_id') }}/subscription");
            $('#subscription_company_name').text(@json(old('subscription_company_name', 'Selected Company')));
            if (subscriptionModal) {
                subscriptionModal.show();
            }
        @endif
    @endif
});
</script>
@endpush
