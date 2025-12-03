@extends('layouts.app')

@section('title', 'Dashboard')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@push('styles')
    <style>
        .info-box {
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
            border-radius: .25rem;
            background: #fff;
            display: flex;
            margin-bottom: 1rem;
            min-height: 80px;
            padding: .5rem;
            position: relative;
        }

        .info-box .icon {
            border-radius: .25rem;
            -ms-flex-align: center;
            align-items: center;
            display: -ms-flexbox;
            display: flex;
            font-size: 1.875rem;
            -ms-flex-pack: center;
            justify-content: center;
            text-align: center;
            width: 70px;
        }

        .info-box .content {
            -ms-flex: 1;
            flex: 1;
            padding: 5px 10px;
        }

        .info-box .text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .info-box .number {
            display: block;
            font-weight: 700;
            margin-top: .25rem;
        }

        .card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0 0.375rem rgba(0, 0, 0, .1);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #4e73df;
            margin: 0;
        }

        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .chart-container {
            position: relative;
            min-height: 300px;
        }

        .badge {
            padding: 0.25em 0.6em;
            font-size: 0.875rem;
        }
    </style>
@endpush

@section('content')
    <div class="content">
        <div class="container-fluid">
            @php
                $userRole = auth()->user()->userRole->name;
            @endphp

            {{-- MASTER ADMIN DASHBOARD --}}
            @if($userRole === 'master_admin' && isset($systemStats))
                @include('dashboard.roles.master_admin')
            
            {{-- SUPER ADMIN DASHBOARD --}}
            @elseif($userRole === 'super_admin' && isset($adminStats))
                @include('dashboard.roles.super_admin')
            
            {{-- ADMIN DASHBOARD --}}
            @elseif($userRole === 'admin' && isset($monthlyStats))
                @include('dashboard.roles.admin')
            
            {{-- MODERATOR DASHBOARD --}}
            @elseif($userRole === 'moderator' && isset($operationalStats))
                @include('dashboard.roles.moderator')
            
            {{-- ACCOUNTANT DASHBOARD --}}
            @elseif($userRole === 'accountant' && isset($financialStats))
                @include('dashboard.roles.accountant')
            
            {{-- DEFAULT FALLBACK --}}
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h4>Welcome to Dashboard</h4>
                            <p>Dashboard content is being prepared for your role.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table th,
        .table td {
            padding: 0.5rem;
        }

        .card-title {
            font-size: 1rem;
            margin-bottom: 0;
        }
    </style>
@endpush

@push('scripts')
    <!-- Additional scripts can be added here if needed -->
@endpush
