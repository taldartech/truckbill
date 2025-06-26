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
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Fixed Simple Sidebar -->
        <nav class="bg-dark text-white p-3" style="width:220px;position:fixed;top:0;left:0;height:100vh;z-index:1040;">
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
            </ul>
        </nav>
        <!-- Main Content -->
        <div style="margin-left:250px;flex:1;">
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
