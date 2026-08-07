@extends('layouts.app')

@section('title', 'Email Templates')

@section('content')
    <div class="container mt-4 app-index-page app-index-page--standard">
        @if (session('success'))
            <div class="mb-3">
                <span class="badge text-bg-dark border app-index-flash">
                    <i class="bi bi-check-circle-fill text-success"></i> {{ session('success') }}
                </span>
            </div>
        @endif

        <div class="card app-index-list-card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Email Templates</h3>
                <div class="app-index-card-toolbar d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('email_templates.create') }}" class="btn app-index-header-btn">
                        <i class="bi bi-plus-circle"></i> Add Template
                    </a>
                </div>
            </div>

            <div class="app-index-dt-toolbar">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="card mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">Today Total</small>
                                <h4 class="mb-0">{{ $todaySummary['total'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">Today Sent</small>
                                <h4 class="mb-0 text-success">{{ $todaySummary['sent'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">Today Failed</small>
                                <h4 class="mb-0 text-danger">{{ $todaySummary['failed'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card mb-0">
                            <div class="card-body py-2">
                                <small class="text-muted">Today Delivered</small>
                                <h4 class="mb-0 text-primary">{{ $todaySummary['delivered'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="px-3 pt-3 pb-2">
                    <h6 class="mb-2" style="font-size: 0.875rem; color: var(--brand-text-muted);">Today Company-wise Mail Dashboard</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped table-hover align-middle mb-0 app-index-table w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Company</th>
                                    <th>Total</th>
                                    <th>Sent</th>
                                    <th>Failed</th>
                                    <th>Delivered</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($companyStats as $row)
                                    <tr>
                                        <td>{{ $row->company?->name ?? 'N/A' }}</td>
                                        <td>{{ $row->total_count }}</td>
                                        <td>{{ $row->sent_count }}</td>
                                        <td>{{ $row->failed_count }}</td>
                                        <td>{{ $row->delivered_count }}</td>
                                    </tr>
                                @empty
                                    <tr class="app-index-empty-row">
                                        <td colspan="5" class="text-center text-muted">No mail activity found for today.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-3 pb-3">
                    <h6 class="mb-2" style="font-size: 0.875rem; color: var(--brand-text-muted);">Template List</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 app-index-table w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Company</th>
                                    <th>Key</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th class="app-index-actions-cell" style="width: 180px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($templates as $template)
                                    <tr>
                                        <td>{{ $template->id }}</td>
                                        <td>{{ $template->name }}</td>
                                        <td>{{ $template->company?->name ?? 'All' }}</td>
                                        <td><code>{{ $template->key }}</code></td>
                                        <td>{{ $template->subject }}</td>
                                        <td>
                                            @if ($template->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="app-index-actions-cell">
                                            <a href="{{ route('email_templates.preview', $template) }}" class="btn btn-info btn-sm">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('email_templates.edit', $template) }}" class="btn btn-primary btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="app-index-empty-row">
                                        <td colspan="7" class="text-center text-muted">No email templates found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
