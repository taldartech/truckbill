<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Invoice & Transport Management System')</title>
    <!-- FontAwesome for AdminLTE Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- AdminLTE Theme CSS -->
    <link href="/theme/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- You can add more theme CSS here if needed -->
</head>
<body class="sidebar-mini layout-fixed">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand navbar-light bg-white border-bottom fixed-top" style="z-index:1050;">
        <div class="container-fluid">
            <!-- Page Title as Link -->
            <a class="navbar-brand fw-bold" href="#">
    @yield('title', 'Transport Management')
</a>
            @auth
    <span class="align-middle ms-3 me-2 border-start ps-3 small text-muted" style="font-size: 1em;">
        <i class="fas fa-building me-1"></i>{{ Auth::user()->company ? Auth::user()->company->name : '' }}
    </span>
@endauth
<ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-user-circle fa-lg"></i>
    @auth
        <span class="ms-2">{{ Auth::user()->name ?? Auth::user()->email }}</span>
    @endauth
</a>
<div class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
    <a class="dropdown-item" href="/profile">Profile</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item text-danger" href="#" id="logout-link">Logout</a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</div>
                </li>
            </ul>
        </div>
    </nav>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Fixed Simple Sidebar -->
        <nav class="bg-dark text-white" style="width:220px;position:fixed;top:56px;left:0;z-index:1040;">
    <div class="p-3" style="height: calc(100vh - 56px); overflow-y: auto;">
        
        <ul class="nav nav-pills flex-column">
                    <li class="nav-item mb-2">
                        <a href="/dashboard" class="nav-link text-white">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    @if(auth()->check() && auth()->user()->userRole->name === 'super_admin')
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#companyMenu" role="button" aria-expanded="false" aria-controls="companyMenu">
                                <span><i class="fas fa-money-check-alt"></i> Company</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="companyMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/company" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Company List
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/company/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register Company
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @else
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#transporterMenu" role="button" aria-expanded="false" aria-controls="transporterMenu">
                                <span><i class="fas fa-truck"></i> Transporter</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="transporterMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/transporters" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/transporters/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/transporter_bank_accounts/create" class="nav-link text-white">
                                            <i class="fas fa-university"></i> Add Bank Details
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#truckMenu" role="button" aria-expanded="false" aria-controls="truckMenu">
                                <span><i class="fas fa-truck-moving"></i> Truck</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="truckMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/trucks" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/trucks/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/drivers/create" class="nav-link text-white">
                                            <i class="fas fa-user-tie"></i> Add Driver
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#customerMenu" role="button" aria-expanded="false" aria-controls="customerMenu">
                                <span><i class="fas fa-users"></i> Customer</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="customerMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/customers" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/customers/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#consigneeMenu" role="button" aria-expanded="false" aria-controls="consigneeMenu">
                                <span><i class="fas fa-people-carry"></i> Consignee</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="consigneeMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/consignees" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/consignees/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#lorryReceiptMenu" role="button" aria-expanded="false" aria-controls="lorryReceiptMenu">
                                <span><i class="fas fa-file-alt"></i> Lorry Receipts</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="lorryReceiptMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/lorry_receipts" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/lorry_receipts/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#freightBookingMenu" role="button" aria-expanded="false" aria-controls="freightBookingMenu">
                                <span><i class="fas fa-shipping-fast"></i> Freight Bookings</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="freightBookingMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/freight_bookings" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/freight_bookings/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#voucherMenu" role="button" aria-expanded="false" aria-controls="voucherMenu">
                                <span><i class="fas fa-file-invoice"></i> Vouchers</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="voucherMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/vouchers" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/vouchers/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#voucherPaymentMenu" role="button" aria-expanded="false" aria-controls="voucherPaymentMenu">
                                <span><i class="fas fa-money-bill-wave"></i> Voucher Payments</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="voucherPaymentMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/voucher_payments" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/voucher_payments/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link text-white d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#invoiceMenu" role="button" aria-expanded="false" aria-controls="invoiceMenu">
                                <span><i class="fas fa-file-invoice-dollar"></i> Invoices</span>
                                <i class="fas fa-chevron-down small"></i>
                            </a>
                            <div class="collapse ms-3" id="invoiceMenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item mb-1">
                                        <a href="/invoices" class="nav-link text-white">
                                            <i class="fas fa-list"></i> Listing
                                        </a>
                                    </li>
                                    <li class="nav-item mb-1">
                                        <a href="/invoices/create" class="nav-link text-white">
                                            <i class="fas fa-plus"></i> Register
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                       
                        {{-- <li class="nav-item mb-2">
                            <a href="/vouchers" class="nav-link text-white">
                                <i class="fas fa-file-invoice"></i> Vouchers
                            </a>
                        </li> --}}
                        {{-- <li class="nav-item mb-2">
                            <a href="/voucher_payments" class="nav-link text-white">
                                <i class="fas fa-money-check-alt"></i> Voucher Payments
                            </a>
                        </li> --}}
                        {{-- <li class="nav-item mb-2">
                            <a href="/invoices" class="nav-link text-white">
                                <i class="fas fa-money-check-alt"></i> Invoices
                            </a>
                        </li> --}}
                        <li class="nav-item mb-2">
                            <a href="/reports" class="nav-link text-white">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                       
                    @endif
                    {{-- <li class="nav-item mb-2">
                        <a href="/vouchers" class="nav-link text-white">
                            <i class="fas fa-file-invoice"></i> Vouchers
                        </a>
                    </li> --}}
                    {{-- <li class="nav-item mb-2">
                        <a href="/voucher_payments" class="nav-link text-white">
                            <i class="fas fa-money-check-alt"></i> Voucher Payments
                        </a>
                    </li> --}}
                   
                    {{-- <li class="nav-item mb-2">
                        <a href="/invoices" class="nav-link text-white">
                            <i class="fas fa-money-check-alt"></i> Invoices
                        </a>
                    </li> --}}
                   
                   
                    @if(auth()->check() && auth()->user()->userRole->name === 'super_admin')
<li class="nav-item mb-2">
    <a href="/users" class="nav-link text-white">
        <i class="fas fa-users"></i> Users
    </a>
</li>
@endif
                    {{-- <li class="nav-item mb-2">
                        <a href="/reports" class="nav-link text-white">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="/settings" class="nav-link text-white">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li> --}}
                </ul>
            </div>
        </nav>
        <!-- Main Content -->
        <div style="margin-left:220px;flex:1;padding-top:56px;">
            <div class="p-4">
                @yield('content')
            </div>
        </div>
    </div>
    <!-- jQuery (required for AdminLTE) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Popper.js (required for AdminLTE v4 sidebar) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <!-- Bootstrap JS (recommended for dropdowns, tooltips, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var logoutLink = document.getElementById('logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('logout-form').submit();
            });
        }
    });
</script>
    <script src="/js/dynamic-dropdowns.js"></script>
    <!-- AdminLTE JS -->
    <script src="/theme/dist/js/adminlte.min.js"></script>
</body>
</html>
