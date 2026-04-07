@extends('layouts.app')

@section('title', 'Email Templates')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Email Templates</h3>
            <a href="{{ route('email_templates.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Template
            </a>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body py-2">
                        <small class="text-muted">Today Total</small>
                        <h4 class="mb-0">{{ $todaySummary['total'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body py-2">
                        <small class="text-muted">Today Sent</small>
                        <h4 class="mb-0 text-success">{{ $todaySummary['sent'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body py-2">
                        <small class="text-muted">Today Failed</small>
                        <h4 class="mb-0 text-danger">{{ $todaySummary['failed'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body py-2">
                        <small class="text-muted">Today Delivered</small>
                        <h4 class="mb-0 text-primary">{{ $todaySummary['delivered'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Today Company-wise Mail Dashboard</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
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
                            <tr>
                                <td colspan="5" class="text-center text-muted">No mail activity found for today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Template List</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Key</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th style="width: 180px;">Action</th>
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
                                <td>
                                    <a href="{{ route('email_templates.preview', $template) }}" class="btn btn-info btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('email_templates.edit', $template) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No email templates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
