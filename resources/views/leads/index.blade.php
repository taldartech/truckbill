@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="container mt-4">
    <div class="col-sm-6"><h3 class="mb-0">Lead Management</h3></div>
    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#createLeadModal">
            <i class="bi bi-plus"></i> Create Lead
        </button>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Leads List</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter" class="form-control">
                        <option value="">All</option>
                        <option value="LEAD_CREATED">LEAD_CREATED</option>
                        <option value="DEMO_SCHEDULED">DEMO_SCHEDULED</option>
                        <option value="DEMO_COMPLETED">DEMO_COMPLETED</option>
                        <option value="ACTIVATED">ACTIVATED</option>
                        <option value="REJECTED">REJECTED</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sourceFilter">Source</label>
                    <select id="sourceFilter" class="form-control">
                        <option value="">All</option>
                        <option value="website">website</option>
                        <option value="manual">manual</option>
                    </select>
                </div>
            </div>

            <table id="leadsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>SR No.</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('leads.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Mobile</label>
                            <input type="text" class="form-control" name="mobile" required maxlength="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Company Name</label>
                            <input type="text" class="form-control" name="company_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Business Type</label>
                            <input type="text" class="form-control" name="business_type">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>GST Number</label>
                            <input type="text" class="form-control" name="gst_number">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>City</label>
                            <input type="text" class="form-control" name="city">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const table = $('#leadsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("leads.index") }}',
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.source = $('#sourceFilter').val();
            },
        },
        columns: [
            { data: 'id', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'mobile' },
            { data: 'email' },
            { data: 'status' },
            { data: 'source' },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[6, 'desc']],
    });

    $('#statusFilter, #sourceFilter').on('change', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.js-lead-status', function () {
        const id = $(this).data('id');
        const status = $(this).data('status');
        $.ajax({
            url: `/leads/${id}/status`,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf},
            data: { status },
            success: function () {
                table.ajax.reload();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Unable to update status.');
            }
        });
    });

    $(document).on('click', '.js-reject-lead', function () {
        const id = $(this).data('id');
        const reason = prompt('Rejection reason (optional)');
        $.ajax({
            url: `/leads/${id}/reject`,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf},
            data: { rejection_reason: reason || '' },
            success: function () {
                table.ajax.reload();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Unable to reject lead.');
            }
        });
    });

    $(document).on('click', '.js-generate-link', function () {
        const id = $(this).data('id');
        $.ajax({
            url: `/leads/${id}/generate-onboarding-link`,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf},
            success: function (response) {
                navigator.clipboard.writeText(response.url);
                alert(`Onboarding link copied:\n${response.url}`);
                table.ajax.reload();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Unable to generate onboarding link.');
            }
        });
    });
});
</script>
@endpush
