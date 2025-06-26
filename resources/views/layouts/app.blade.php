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
            <a class="navbar-brand fw-bold" href="#">@yield('title', 'Transport Management')</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle fa-lg"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <a class="dropdown-item" href="#">Profile</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Fixed Simple Sidebar -->
        <nav class="bg-dark text-white p-3" style="width:220px;position:fixed;top:56px;left:0;height:calc(100vh - 56px);z-index:1040;">
            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2">
                    <a href="/dashboard" class="nav-link text-white">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/voucher_payments" class="nav-link text-white">
                        <i class="fas fa-money-check-alt"></i> Voucher Payments
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/vouchers" class="nav-link text-white">
                        <i class="fas fa-file-invoice"></i> Vouchers
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/freight_bookings" class="nav-link text-white">
                        <i class="fas fa-shipping-fast"></i> Freight Bookings
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/trucks" class="nav-link text-white">
                        <i class="fas fa-truck"></i> Trucks
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/transporters" class="nav-link text-white">
                        <i class="fas fa-people-carry"></i> Transporters
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/users" class="nav-link text-white">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/reports" class="nav-link text-white">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="/settings" class="nav-link text-white">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
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
    <!-- AdminLTE JS -->
    <script src="/theme/dist/js/adminlte.min.js"></script>
</body>
</html>
