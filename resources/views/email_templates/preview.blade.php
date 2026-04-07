@extends('layouts.app')

@section('title', 'Preview Email Template')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Preview: {{ $email_template->name }}</h3>
            <div>
                <a href="{{ route('email_templates.edit', $email_template) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('email_templates.index') }}" class="btn btn-brand-primary">Back</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <p class="mb-2"><strong>Key:</strong> <code>{{ $email_template->key }}</code></p>
                <p class="mb-2"><strong>Subject:</strong> {{ $email_template->subject }}</p>
                <p class="mb-2"><strong>Allowed Variables:</strong>
                    @forelse(($email_template->allowed_variables ?? []) as $variable)
                        <span class="badge bg-info me-1">{{ $variable }}</span>
                    @empty
                        <span class="text-muted">None</span>
                    @endforelse
                </p>
                <p class="mb-2"><strong>Allowed Roles:</strong>
                    @forelse(($email_template->allowed_roles ?? []) as $role)
                        <span class="badge bg-primary me-1">{{ $role }}</span>
                    @empty
                        <span class="text-muted">None</span>
                    @endforelse
                </p>
                <p class="mb-2">
                    <strong>Target Flags:</strong>
                    <span class="badge {{ $email_template->send_to_transporter ? 'bg-success' : 'bg-secondary' }}">Transporter {{ $email_template->send_to_transporter ? 'ON' : 'OFF' }}</span>
                    <span class="badge {{ $email_template->send_to_customer ? 'bg-success' : 'bg-secondary' }}">Customer {{ $email_template->send_to_customer ? 'ON' : 'OFF' }}</span>
                </p>
                <p class="mb-0"><strong>Detected in Body:</strong>
                    @forelse($detectedVariables as $variable)
                        <span class="badge bg-secondary me-1">{{ $variable }}</span>
                    @empty
                        <span class="text-muted">No placeholders found</span>
                    @endforelse
                </p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Send Test Email</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('email_templates.send-test', $email_template) }}" class="row g-2">
                    @csrf
                    <div class="col-md-9">
                        <input type="email" name="test_email" class="form-control @error('test_email') is-invalid @enderror"
                            placeholder="recipient@example.com" required>
                        @error('test_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">Send Test</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Rendered Output (Sample Variables)</strong></div>
            <div class="card-body">
                {!! $renderedBody !!}
            </div>
        </div>
    </div>
@endsection
