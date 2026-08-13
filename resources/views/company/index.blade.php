@extends('layouts.app')

@push('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
@endpush

@section('content')
<div class="container mt-4 app-index-page app-index-page--standard">
    @if(session('success'))
        <div class="mb-3">
            <span class="badge text-bg-dark border app-index-flash">
                <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
            </span>
        </div>
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

    <div class="card app-index-list-card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Company Management</h3>
            <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                @if (auth()->user()->userRole->name !== 'master_admin')
                    <a href="{{ route('company.create') }}" class="btn app-index-header-btn" aria-label="Create company">
                        <i class="fas fa-plus"></i> Create
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="companiesTable" class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">SR No.</th>
                            <th>Name</th>
                            <th>GST Number</th>
                            <th>PAN Number</th>
                            @if (auth()->check() && auth()->user()->userRole?->name === 'master_admin')
                                <th>Dummy PAN (System)</th>
                            @endif
                            <th>Address</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th class="app-index-actions-cell" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade fb-pay-modal" id="companyDetailsModal" tabindex="-1" aria-labelledby="companyDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyDetailsModalLabel">
                    <i class="bi bi-building"></i> Company Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="companyDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading company details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Close
                </button>
                @if (auth()->user()->userRole->name !== 'master_admin')
                    <a href="#" id="editCompanyBtn" class="btn btn-brand-primary" style="display: none;">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endif
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
<script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

<script>
$(document).ready(function() {
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
            @if (auth()->check() && auth()->user()->userRole?->name === 'master_admin')
            {
                data: 'dummy_pan_number',
                name: 'dummy_pan_number',
                width: '140px'
            },
            @endif
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
                className: 'app-index-actions-cell'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: '<"app-index-dt-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2"<"d-flex flex-wrap align-items-center gap-2"l><"d-flex flex-wrap align-items-center gap-2"fB>>' +
            't' +
            '<"app-index-partload-pagination d-flex flex-wrap align-items-center justify-content-between gap-2"<"app-index-partload-pagination__summary"i><"app-index-partload-pagination__nav"p>>',
        buttons: [
            {
                extend: 'excel',
                className: 'btn btn-secondary btn-sm',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel'
            }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_",
            info: "Showing _START_ to _END_ of _TOTAL_ companies",
            infoEmpty: "Showing 0 to 0 of 0 companies",
            infoFiltered: "(filtered from _MAX_ total)",
            zeroRecords: "No companies found.",
            emptyTable: "No companies found.",
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
        stateSaveCallback: function (settings, data) {
            localStorage.setItem('dt_companies_index_v2', JSON.stringify(data));
        },
        stateLoadCallback: function () {
            try {
                return JSON.parse(localStorage.getItem('dt_companies_index_v2'));
            } catch (e) {
                return null;
            }
        },
        searchDelay: 500,
        scrollX: true
    });

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fact(label, value) {
        return `
            <div class="fb-pay-fact">
                <span class="fb-pay-fact__label">${escapeHtml(label)}</span>
                <span class="fb-pay-fact__value">${value}</span>
            </div>
        `;
    }

    function panel(icon, title, factsHtml) {
        return `
            <div class="fb-pay-panel">
                <div class="fb-pay-panel__head">
                    <i class="${icon}"></i> ${escapeHtml(title)}
                </div>
                <div class="fb-pay-panel__body">
                    <div class="fb-pay-facts fb-pay-facts--2">
                        ${factsHtml}
                    </div>
                </div>
            </div>
        `;
    }

    $(document).on('click', '.company-name-link', function(e) {
        e.preventDefault();
        var companyId = $(this).data('id');

        $('#companyDetailsModal').modal('show');
        $('#companyDetailsContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading company details...</p>
            </div>
        `);
        $('#editCompanyBtn').hide();

        $.ajax({
            url: '/company/' + companyId,
            type: 'GET',
            success: function(data) {
                var logoHtml = data.logo_url
                    ? `<img src="${escapeHtml(data.logo_url)}" alt="Company logo" style="max-height: 56px; width: auto; object-fit: contain;">`
                    : escapeHtml('Not provided');

                var taxFacts = fact('GST Number', escapeHtml(data.gst_number))
                    + fact('PAN Number', escapeHtml(data.pan_number))
                    + fact('GST Scheme', escapeHtml(data.gst_scheme))
                    + fact('GST Type', escapeHtml(data.gst_type))
                    + fact('GST Slab', escapeHtml(data.gst_slab));

                if (data.dummy_pan_number) {
                    taxFacts += fact('Dummy PAN (System)', escapeHtml(data.dummy_pan_number));
                }

                var detailsHtml = panel('bi bi-building', 'Company',
                    fact('Name', escapeHtml(data.name))
                    + fact('Short Name', escapeHtml(data.short_name))
                    + fact('Type', escapeHtml(data.company_type))
                    + fact('Status', data.is_blocked
                        ? '<span class="badge text-bg-danger">Blocked</span>'
                        : '<span class="badge text-bg-success">Active</span>')
                    + fact('Contact Person', escapeHtml(data.contact_person))
                    + fact('Contact Number', escapeHtml(data.contact_number))
                    + fact('Logo', logoHtml)
                )
                + panel('bi bi-receipt', 'GST & Tax', taxFacts)
                + panel('bi bi-geo-alt', 'Location',
                    fact('Address', escapeHtml(data.address))
                    + fact('Country', escapeHtml(data.country))
                    + fact('State', escapeHtml(data.state))
                    + fact('City', escapeHtml(data.city))
                    + fact('Pin Code', escapeHtml(data.pin_code))
                )
                + panel('bi bi-bank', 'Bank Details',
                    fact('Bank', escapeHtml(data.bank))
                    + fact('Account Number', escapeHtml(data.account_number))
                    + fact('IFSC', escapeHtml(data.ifsc))
                );

                $('#companyDetailsContent').html(detailsHtml);

                if (data.can_edit && data.edit_url) {
                    $('#editCompanyBtn').attr('href', data.edit_url).show();
                } else {
                    $('#editCompanyBtn').hide();
                }
            },
            error: function() {
                $('#companyDetailsContent').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error!</strong> Failed to load company details. Please try again.
                    </div>
                `);
                $('#editCompanyBtn').hide();
            }
        });
    });

    $(document).on('submit', 'form[action*="destroy"]', function(e) {
        if (!confirm('Are you sure you want to delete this company?')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
