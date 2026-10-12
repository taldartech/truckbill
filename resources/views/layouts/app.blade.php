<!DOCTYPE html>
<html lang="en">

<head>
    @php
        $canonicalBaseUrl = rtrim((string) config('app.website_url', config('app.url')), '/');
        $canonicalPath = request()->path() === '/' ? '' : '/'.ltrim(request()->path(), '/');
        $canonicalUrl = $canonicalBaseUrl.$canonicalPath;
        $canonicalQuery = request()->getQueryString();

        if (! empty($canonicalQuery)) {
            $canonicalUrl .= '?'.$canonicalQuery;
        }
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() ?? '' }}">
    <meta name="company-id" content="{{ auth()->user()->company_id ?? '' }}">
    <title>@yield('title', 'Invoice & Transport Management System')</title>
    @if (config('app.block_search_indexing'))
        <meta name="robots" content="noindex, nofollow, noarchive">
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endif
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
    <!-- End Favicon -->

    <!-- FontAwesome for AdminLTE Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Include jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css"
        integrity="sha256-tZHrRjVqNSRyWg2wbppGnT833E/Ys0DHWGwT04GiqQg=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI=" crossorigin="anonymous" />
    <link rel="stylesheet" href="/theme/dist/css/adminlte.min.css?v={{ config('app.ASSET_VERSION') }}" />
    <link rel="stylesheet" href="/css/buttons.css?v={{ config('app.ASSET_VERSION') }}">
    <link rel="stylesheet" href="/css/brand.css?v={{ config('app.ASSET_VERSION') }}">
    <link rel="stylesheet" href="/css/crud-action-buttons.css?v={{ config('app.ASSET_VERSION') }}">
    <link rel="stylesheet" href="/css/app-index-page.css?v={{ config('app.ASSET_VERSION') }}">
    <link rel="stylesheet" href="/css/theme-overlays.css?v={{ config('app.ASSET_VERSION') }}">
    <link rel="stylesheet" href="/css/fb-show-redesigned.css?v={{ config('app.ASSET_VERSION') }}">
    <link rel="stylesheet" href="/css/sidebar-redesigned.css?v={{ config('app.ASSET_VERSION') }}">
    <!-- apexcharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
        integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />
    <!-- jsvectormap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
        integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4=" crossorigin="anonymous" />
    <style>
        label {
            font-weight: bold !important;
        }

        /* DataTable header alignment - align all header text to top */
        table.dataTable thead th {
            vertical-align: top !important;
            position: relative;
        }

        /* Ensure header text stays at top - wrap it in a div */
        table.dataTable thead th {
            display: table-cell;
        }

        /* Style for search inputs in headers */
        table.dataTable thead th input.form-control {
            margin-top: 5px;
            display: block;
            width: 100%;
        }

        /* Ensure all header text aligns to top */
        table.dataTable thead th {
            text-align: left;
        }


        html,
        body {
            font-size: 15px !important;
        }

        h1 {
            font-size: 1.8rem !important;
        }

        h2 {
            font-size: 1.5rem !important;
        }

        h3 {
            font-size: 1.2rem !important;
        }

        h4,
        h5,
        h6 {
            font-size: 1rem !important;
        }

        table,
        th,
        td {
            font-size: 1em !important;
            border-color: var(--brand-border) !important;
        }

        .table-hover tbody tr:hover {
            background-color: var(--brand-row-hover) !important;
        }

        .form-select-sm,
        .form-select-sm option {
            font-size: 0.875rem !important;
        }

        .app-sidebar,
        .main-sidebar {
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
        }

        .app-sidebar .nav-link.active,
        .app-sidebar .nav-link.router-link-exact-active {
            background: var(--brand-active-bg) !important;
            color: var(--brand-primary) !important;
            border-right: 3px solid var(--brand-primary) !important;
            font-weight: 600 !important;
        }

        .app-sidebar .nav-link.active i,
        .app-sidebar .nav-link.router-link-exact-active i {
            color: var(--brand-primary) !important;
        }

        /* User dropdown styling */
        .user-menu .dropdown-menu {
            border: none;
            border-radius: 8px;
            overflow: hidden;
        }

        .user-header {
            background: var(--brand-primary) !important;
            position: relative;
        }

        .user-header i {
            margin-bottom: 10px;
        }

        .user-footer {
            background: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .user-footer .btn {
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .user-footer .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .app-sidebar {
            --lte-sidebar-hover-bg: rgba(0, 0, 0, 0.05);
            --lte-sidebar-color: #374151;
            --lte-sidebar-hover-color: #111827;
            --lte-sidebar-active-color: var(--brand-primary);
            --lte-sidebar-menu-active-bg: var(--brand-active-bg);
            --lte-sidebar-menu-active-color: var(--brand-primary);
            --lte-sidebar-submenu-bg: transparent;
            --lte-sidebar-submenu-color: #4b5563;
            --lte-sidebar-submenu-hover-color: #111827;
            --lte-sidebar-submenu-hover-bg: rgba(0, 0, 0, 0.05);
            --lte-sidebar-submenu-active-color: var(--brand-primary);
            --lte-sidebar-submenu-active-bg: var(--brand-active-bg);
            --lte-sidebar-header-color: #6b7280;
        }

        .sidebar-brand .brand-text {
            color: #fff;
            font-weight: 700;
        }

        .sidebar-brand .brand-text:hover {
            color: #fff;
            font-weight: 700;
        }

        /* Navbar Header Responsive */
        .app-header.navbar {
            min-height: 48px;
            height: auto;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        /* Completely remove blue border / focus outline on sidebar menu bar icon */
        [data-lte-toggle="sidebar"],
        [data-lte-toggle="sidebar"]:hover,
        [data-lte-toggle="sidebar"]:focus,
        [data-lte-toggle="sidebar"]:active,
        [data-lte-toggle="sidebar"]:focus-visible,
        .app-header [data-lte-toggle="sidebar"],
        .app-header [data-lte-toggle="sidebar"]:hover,
        .app-header [data-lte-toggle="sidebar"]:focus,
        .app-header [data-lte-toggle="sidebar"]:active,
        .app-header [data-lte-toggle="sidebar"]:focus-visible {
            outline: none !important;
            border: 0 !important;
            box-shadow: none !important;
            text-decoration: none !important;
            -webkit-tap-highlight-color: transparent !important;
        }

        .credits-container {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            flex-shrink: 0;
            background: #fff;
            border: 1px solid rgba(47, 179, 111, 0.35);
            border-radius: 50px;
            padding: 4px 10px;
            line-height: 1;
        }

        .credit-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--brand-primary, #2fb36f);
            white-space: nowrap;
            line-height: 1;
        }

        .credit-pill i {
            color: var(--brand-primary, #2fb36f);
            font-size: 0.85rem;
        }

        .credit-pill .value {
            color: var(--brand-primary, #2fb36f);
            font-weight: 700;
            white-space: nowrap;
        }

        .credit-divider {
            width: 1px;
            height: 14px;
            background: rgba(0, 0, 0, 0.15);
            margin: 0 6px;
            flex-shrink: 0;
        }

        @media (max-width: 575.98px) {
            .app-header.navbar {
                min-height: 46px;
            }
            .credits-container {
                padding: 3px 8px;
            }
            .credit-pill {
                font-size: 0.70rem;
                gap: 4px;
            }
            .credit-pill i {
                font-size: 0.78rem;
            }
            .credit-divider {
                height: 12px;
                margin: 0 4px;
            }
        }

        /* Constrain main content area to prevent wide tables from breaking layout without interfering with sticky sidebar */
        .app-main {
            min-width: 0;
            max-width: 100%;
        }

        .app-footer {
            grid-area: lte-app-footer !important;
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            min-height: 44px !important;
            padding: 0.35rem 1rem !important;
            background-color: #ffffff !important;
            border-top: 1px solid #e9ecef !important;
            box-sizing: border-box !important;
        }

        .app-footer .container-fluid {
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
        }

        .truckbill-footer-content {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            font-size: 0.85rem;
            line-height: 1.45;
            color: #475569;
        }

        .truckbill-footer-content .footer-col-brand,
        .truckbill-footer-content .footer-col-nav,
        .truckbill-footer-content .footer-col-meta {
            display: inline-flex !important;
            align-items: center !important;
            line-height: 1.45;
        }

        .truckbill-footer-content .footer-separator {
            margin: 0 0.35rem;
            color: #94a3b8;
            opacity: 0.75;
            user-select: none;
            display: inline-flex;
            align-items: center;
        }

        .truckbill-footer-content .footer-link {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s ease;
            cursor: pointer;
            line-height: 1.45;
        }

        .truckbill-footer-content .footer-link:hover,
        .truckbill-footer-content .footer-link:focus,
        .truckbill-footer-content .footer-link:focus-visible {
            color: #0f172a !important;
            text-decoration: none;
            outline: none;
            box-shadow: none;
        }

        .truckbill-footer-content .footer-link:active {
            color: #000000 !important;
        }

        .truckbill-footer-content .footer-version-badge {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.72rem !important;
            font-weight: 600 !important;
            padding: 0.15rem 0.5rem !important;
            line-height: 1.2 !important;
            vertical-align: middle;
        }

        /* Large Screens (>= 1200px): Clean single line, larger 0.85rem font, generous spacing */
        @media (min-width: 1200px) {
            .truckbill-footer-content {
                flex-direction: row;
                flex-wrap: nowrap;
                white-space: nowrap;
                font-size: 0.85rem;
                gap: 1.25rem;
            }
            .truckbill-footer-content .footer-col-brand,
            .truckbill-footer-content .footer-col-nav,
            .truckbill-footer-content .footer-col-meta {
                flex-shrink: 0;
            }
        }

        /* Medium-Large Screens (992px to 1199.98px - Laptops / sidebar open): Single line, 0.78rem font */
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .truckbill-footer-content {
                flex-direction: row;
                flex-wrap: nowrap;
                white-space: nowrap;
                font-size: 0.78rem;
                gap: 0.75rem;
            }
            .truckbill-footer-content .footer-col-brand,
            .truckbill-footer-content .footer-col-nav,
            .truckbill-footer-content .footer-col-meta {
                flex-shrink: 0;
            }
            .truckbill-footer-content .footer-separator {
                margin: 0 0.25rem;
            }
            .truckbill-footer-content .footer-version-badge {
                font-size: 0.68rem !important;
                padding: 0.1rem 0.4rem !important;
            }
        }

        /* Middle Screens (768px to 991.98px - Tablets landscape / compact laptops): Single line preserved, 0.72rem font */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .truckbill-footer-content {
                flex-direction: row;
                flex-wrap: nowrap;
                white-space: nowrap;
                font-size: 0.72rem;
                gap: 0.45rem;
            }
            .truckbill-footer-content .footer-col-brand,
            .truckbill-footer-content .footer-col-nav,
            .truckbill-footer-content .footer-col-meta {
                flex-shrink: 0;
            }
            .truckbill-footer-content .footer-separator {
                margin: 0 0.15rem;
            }
            .truckbill-footer-content .footer-version-badge {
                font-size: 0.65rem !important;
                padding: 1px 4px !important;
            }
        }

        /* Small Screens & Mobile (< 768px): Stacked centered rows with comfortable readable font */
        @media (max-width: 767.98px) {
            .app-footer {
                min-height: auto !important;
                padding: 0.5rem 0.75rem !important;
            }
            .app-footer .container-fluid {
                display: block !important;
            }
            .truckbill-footer-content {
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                text-align: center !important;
                gap: 0.4rem !important;
                font-size: 0.82rem !important;
            }
            .truckbill-footer-content .footer-col-nav {
                order: 1;
                flex-wrap: wrap;
                justify-content: center;
                row-gap: 0.2rem;
            }
            .truckbill-footer-content .footer-col-brand {
                order: 2;
                justify-content: center;
            }
            .truckbill-footer-content .footer-col-meta {
                order: 3;
                justify-content: center;
            }
            .truckbill-footer-content .footer-separator {
                margin: 0 0.35rem;
            }
            .truckbill-footer-content .footer-version-badge {
                font-size: 0.70rem !important;
                padding: 0.1rem 0.45rem !important;
            }
        }

        /* Extra Small Mobile (< 480px) */
        @media (max-width: 479.98px) {
            .truckbill-footer-content {
                font-size: 0.76rem !important;
                gap: 0.35rem !important;
            }
            .truckbill-footer-content .footer-separator {
                margin: 0 0.2rem;
            }
        }

        /* Global Print Stylesheet for Browser Printing */
        @media print {
            /* Hide non-printable app layout & backdrops/overlays */
            .sidebar-overlay,
            .modal-backdrop,
            .loading-overlay,
            .overlay,
            [class*="overlay"],
            [class*="backdrop"],
            .app-header,
            .app-sidebar,
            .main-sidebar,
            .app-footer,
            .app-index-card-toolbar,
            .sidebar-wrapper,
            .no-print,
            .d-print-none,
            .btn,
            .btn-group,
            .app-content-header > .container-fluid > .alert,
            #subscription-access-banner {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                background: transparent !important;
                width: 0 !important;
                height: 0 !important;
                pointer-events: none !important;
            }

            html,
            body,
            body.layout-fixed,
            body.bg-body-tertiary,
            body.sidebar-open {
                background: #ffffff !important;
                background-color: #ffffff !important;
                background-image: none !important;
                color: #000000 !important;
                font-size: 11pt !important;
                width: 100% !important;
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }

            body.layout-fixed {
                overflow: visible !important;
            }

            .app-wrapper {
                display: block !important;
                position: static !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .app-main,
            .sidebar-expand-lg.layout-fixed .app-main,
            .sidebar-expand.layout-fixed .app-main,
            .layout-fixed .app-main {
                display: block !important;
                position: static !important;
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .app-content,
            .app-content-header,
            .app-content-header > .container-fluid,
            .container-fluid {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }

            .app-index-list-card,
            .card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
                overflow: visible !important;
                margin-bottom: 1rem !important;
                page-break-inside: auto !important;
            }

            .table-responsive {
                overflow: visible !important;
                display: block !important;
            }

            table {
                width: 100% !important;
                page-break-inside: auto !important;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }
        }
        /* Sidebar layout polish lives in sidebar-redesigned.css (OwnFleet-aligned) */
    </style>
    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body px-2 px-md-3 py-1">
            <!--begin::Container-->
            <div class="container-fluid d-flex flex-nowrap align-items-center justify-content-between p-0">
                <!--begin::Start Navbar Links-->
                <ul class="navbar-nav d-flex flex-row align-items-center flex-nowrap gap-1 gap-sm-2 mb-0">
                    <li class="nav-item flex-shrink-0">
                        <a class="nav-link p-1 p-sm-2 text-secondary border-0 shadow-none" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar" style="outline: none !important; border: 0 !important; box-shadow: none !important;">
                            <i class="fa-solid fa-bars fs-5"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block flex-shrink-0">
                        <span class="nav-link text-muted small border-start ps-3 py-1 text-nowrap">
                            @auth
                                <i class="fas fa-building me-1"></i>{{ Auth::user()->company ? Auth::user()->company->name : '' }}
                            @endauth
                        </span>
                    </li>
                    @auth
                        @if (Auth::user()->userRole->name === 'super_admin' && session('super_admin_company_id'))
                            <li class="nav-item flex-shrink-0">
                                <a href="{{ route('company.exit') }}" class="nav-link text-warning text-nowrap d-inline-flex align-items-center p-1 p-sm-2" title="Exit company context" style="white-space: nowrap;">
                                    <i class="bi bi-box-arrow-left me-1"></i>Exit Company
                                </a>
                            </li>
                        @endif
                    @endauth

                </ul>
                <!--end::Start Navbar Links-->
                <!--begin::End Navbar Links-->
                <ul class="navbar-nav ms-auto d-flex flex-row align-items-center flex-nowrap gap-1 gap-sm-2 mb-0">
                    @auth
                        @if (in_array(Auth::user()->user_role_id, [3, 4, 5]) || (Auth::user()->userRole->name === 'super_admin' && Auth::user()->canAccessCompanyPanel()))
                            <li class="nav-item flex-shrink-0">
                                <div class="credits-container">
                                    <div class="credit-pill credit-pill--expiry" title="Plan Expiry">
                                        <i class="bi bi-calendar-event"></i>
                                        <span class="value">{{ Auth::user()->company?->subscription?->ends_on?->format('d M, Y') ?? 'N/A' }}</span>
                                    </div>
                                    <div class="credit-divider"></div>
                                    <div class="credit-pill credit-pill--credits" title="LR Credits Remaining">
                                        <i class="bi bi-coin"></i>
                                        <span class="value">{{ Auth::user()->company?->lrCreditsRemaining() ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </li>
                        @endif
                    @endauth

                    <!--begin::User Menu Dropdown-->
                    <li class="nav-item dropdown user-menu flex-shrink-0">
                        <a href="#" class="nav-link dropdown-toggle p-1 p-sm-2 d-inline-flex align-items-center text-secondary" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fs-5"></i>
                            @auth
                                <span class="d-none d-md-inline ms-1 small">{{ Auth::user()->name ?? Auth::user()->email }}</span>
                            @endauth
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow">
                            <!--begin::User Image-->
                            <div class="user-header bg-primary text-center py-4">

                                <i class="fas fa-user-circle fa-3x text-white mb-2"></i>
                                <p class="text-white mb-0">
                                    @auth
                                        {{ Auth::user()->name ?? Auth::user()->email }}
                                        <small class="d-block mt-1">Member since
                                            {{ Auth::user()->created_at->format('M. Y') }}</small>
                                    @endauth
                                </p>

                            </div>
                            <!--end::User Image-->
                            <!--begin::Menu Footer-->
                            <div class="user-footer d-flex justify-content-center p-3">
                                <form action="{{ route('logout') }}" method="POST" class="me-2 no-draft" data-draft-ignore="true">
                                    @csrf
                                    <button type="submit" class="btn btn-danger text-white btn-sm rounded-3">Sign out</button>
                                </form>
                                <a href="{{ route('password.change') }}"
                                    class="btn btn-primary text-white  btn-sm rounded-3">Change Password</a>

                            </div>
                            <!--end::Menu Footer-->
                        </div>
                    </li>
                    <!--end::User Menu Dropdown-->
                </ul>
                <!--end::End Navbar Links-->
            </div>
            <!--end::Container-->
        </nav>
        <aside class="app-sidebar shadow">
            <div class="sidebar-container">
            <!--begin::Sidebar Brand-->
            <div class="sidebar-brand">
                <!--begin::Brand Link-->
                <a href="{{ url('/dashboard') }}" class="brand-link">
                    <img src="{{ asset('logo.png') }}" alt="TruckBill Logo" class="brand-image">
                </a>
                <!--end::Brand Link-->
            </div>
            <!--end::Sidebar Brand-->
            <!--begin::Sidebar Wrapper-->
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <!--begin::Sidebar Menu-->
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-header">Overview</li>
                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link{{ Request::is('dashboard') ? ' active' : '' }}">
                                <i class="nav-icon bi bi-speedometer"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        @if (auth()->check() && auth()->user()->userRole->name === 'master_admin')
                            <li class="nav-item">
                                <a href="{{ route('master.summary-dashboard') }}"
                                    class="nav-link{{ Request::is('master/summary-dashboard') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-chart-pie"></i>
                                    <p>Summary Dashboard</p>
                                </a>
                            </li>
                        @endif
                        @if (auth()->check() && auth()->user()->userRole->name === 'transporter')
                            <li class="nav-header">My Work</li>
                            <li class="nav-item">
                                <a href="{{ route('transporter.freight_bookings.index') }}" class="nav-link{{ Request::is('transporter/freight-bookings*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-shipping-fast"></i>
                                    <p>My Lorry Hires</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('transporter.payments.index') }}" class="nav-link{{ Request::is('transporter/payments*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-indian-rupee-sign"></i>
                                    <p>My Payments</p>
                                </a>
                            </li>
                        @elseif (auth()->check() && auth()->user()->userRole->name === 'customer')
                            <li class="nav-header">My Work</li>
                            <li class="nav-item">
                                <a href="{{ route('customer.lorry-receipts.index') }}" class="nav-link{{ Request::is('customer/lorry-receipts*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>My Bilty</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('customer.invoices.index') }}" class="nav-link{{ Request::is('customer/invoices*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>My Invoices</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('customer.payments.index') }}" class="nav-link{{ Request::is('customer/payments*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-wallet"></i>
                                    <p>Invoice Payments</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('customer.transporters.index') }}" class="nav-link{{ Request::is('customer/transporters*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-building"></i>
                                    <p>Associated Companies</p>
                                </a>
                            </li>
                        @elseif (auth()->check() && auth()->user()->userRole->name === 'master_admin')
                            <li class="nav-header">Platform</li>
                            <li class="nav-item">
                                <a href="/users" class="nav-link{{ Request::is('users*') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Users</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('company') || Request::is('company/create') ? ' active' : '' }}"
                                    href="#companyMenu">
                                    <i class="fas fa-building"></i>
                                    <p>
                                        Company
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/company"
                                            class="nav-link{{ Request::is('company') ? ' active' : '' }}">
                                            <i class="fas fa-list"></i>
                                            <p>Company List</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('email_templates.index') }}"
                                    class="nav-link{{ Request::is('email_templates*') ? ' active' : '' }}">
                                    <i class="fas fa-envelope"></i>
                                    <p>Email Templates</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('master.transporter-masters.index') }}"
                                    class="nav-link{{ Request::is('master/transporter-masters*') ? ' active' : '' }}">
                                    <i class="fas fa-truck-moving"></i>
                                    <p>Transporter Masters</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('master/countries*') || Request::is('master/states*') || Request::is('master/cities*') ? ' active' : '' }}"
                                    href="#masterLocationMenuMasterAdmin">
                                    <i class="fas fa-globe"></i>
                                    <p>
                                        Locations (Master)
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/master/countries" class="nav-link{{ Request::is('master/countries*') ? ' active' : '' }}">
                                            <i class="fas fa-list"></i>
                                            <p>Countries</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/master/states" class="nav-link{{ Request::is('master/states*') ? ' active' : '' }}">
                                            <i class="fas fa-map"></i>
                                            <p>States</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/master/cities" class="nav-link{{ Request::is('master/cities*') ? ' active' : '' }}">
                                            <i class="fas fa-city"></i>
                                            <p>Cities</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if (auth()->check() && auth()->user()->userRole->name === 'super_admin' && !session('super_admin_company_id'))
                            <li class="nav-header">Platform</li>
                            <li class="nav-item">
                                <a href="{{ route('audit-logs.index') }}"
                                    class="nav-link{{ Request::is('audit-logs') ? ' active' : '' }}">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>Audit log</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('company') || Request::is('company/create') ? ' active' : '' }}"
                                    href="#companyMenu">
                                    <i class="fas fa-building"></i>
                                    <p>
                                        Company
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/company"
                                            class="nav-link{{ Request::is('company') ? ' active' : '' }}">
                                            <i class="fas fa-list"></i>
                                            <p>Company List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/company/create"
                                            class="nav-link{{ Request::is('company/create') ? ' active' : '' }}">
                                            <i class="fas fa-plus"></i>
                                            <p>Register Company</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('branches*') || Request::is('branches/create') ? ' active' : '' }}"
                                    href="#branchesMenu">
                                    <i class="fas fa-sitemap"></i>
                                    <p>
                                        Branch
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/branches"
                                            class="nav-link{{ Request::is('branches') ? ' active' : '' }}">
                                            <i class="fas fa-list"></i>
                                            <p>Branch List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/branches/create"
                                            class="nav-link{{ Request::is('branches/create') ? ' active' : '' }}">
                                            <i class="fas fa-plus"></i>
                                            <p>Register Branch</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a href="/users" class="nav-link{{ Request::is('users*') ? ' active' : '' }}">
                                    <i class="fas fa-users"></i>
                                    <p>Users</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('company_settings.index') }}"
                                    class="nav-link{{ Request::is('company_settings*') ? ' active' : '' }}">
                                    <i class="fas fa-sliders-h"></i>
                                    <p>Company Settings</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('truck_owner_bank_accounts*') || Request::is('transporter_bank_accounts*') ? ' active' : '' }}"
                                    href="#bankAccountMenu">
                                    <i class="fas fa-university"></i>
                                    <p>
                                        Bank Account Setup
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/truck_owner_bank_accounts/index"
                                            class="nav-link{{ Request::is('truck_owner_bank_accounts/index') ? ' active' : '' }}">
                                            <i class="fas fa-truck"></i>
                                            <p>Truck Owners</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/transporter_bank_accounts/index"
                                            class="nav-link{{ Request::is('transporter_bank_accounts/index') ? ' active' : '' }}">
                                            <i class="fas fa-shipping-fast"></i>
                                            <p>Transporters</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('subscription.index') }}"
                                    class="nav-link{{ Request::is('subscription*') ? ' active' : '' }}">
                                    <i class="fas fa-credit-card"></i>
                                    <p>Subscription</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('master/countries*') || Request::is('master/states*') || Request::is('master/cities*') ? ' active' : '' }}"
                                    href="#masterLocationMenuSuperAdmin">
                                    <i class="fas fa-globe"></i>
                                    <p>
                                        Locations (Master)
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/master/countries" class="nav-link{{ Request::is('master/countries*') ? ' active' : '' }}">
                                            <i class="fas fa-list"></i>
                                            <p>Countries</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/master/states" class="nav-link{{ Request::is('master/states*') ? ' active' : '' }}">
                                            <i class="fas fa-map"></i>
                                            <p>States</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/master/cities" class="nav-link{{ Request::is('master/cities*') ? ' active' : '' }}">
                                            <i class="fas fa-city"></i>
                                            <p>Cities</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            {{-- end master --}}
                        @endif

                        {{-- Company panel: ops-first IA aligned with OwnFleet patterns --}}
                        @if (auth()->check() && auth()->user()->canAccessCompanyPanel())
                            <li class="nav-header">Operations</li>

                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('lorry_receipts*') ? ' active' : '' }}"
                                    href="#lorryReceiptMenu">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>
                                        Bilty
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/lorry_receipts"
                                            class="nav-link{{ Request::is('lorry_receipts*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-list"></i>
                                            <p>List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/lorry_receipts/create"
                                            class="nav-link{{ Request::is('lorry_receipts/create') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-plus"></i>
                                            <p>Create</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('freight_bookings*') ? ' active' : '' }}"
                                    href="#freightBookingMenu">
                                    <i class="nav-icon fas fa-shipping-fast"></i>
                                    <p>
                                        Lorry Hire
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/freight_bookings"
                                            class="nav-link{{ Request::is('freight_bookings*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-list"></i>
                                            <p>List</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            @if (auth()->user()->canAccessCompanyPanel())
                                <li class="nav-item">
                                    <a class="nav-link{{ Request::is('voucher_payments*') || Request::is('daily-payments') ? ' active' : '' }}"
                                        href="#voucherPaymentMenu">
                                        <i class="nav-icon fas fa-indian-rupee-sign"></i>
                                        <p>
                                            Voucher Payments
                                            <i class="nav-arrow bi bi-chevron-right"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="/voucher_payments"
                                                class="nav-link{{ Request::is('voucher_payments*') && !Request::is('daily-payments') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-list"></i>
                                                <p>List</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('voucher_payments.daily') }}"
                                                class="nav-link{{ Request::is('daily-payments') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-calendar-day"></i>
                                                <p>Daily Payments</p>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('invoices*') ? ' active' : '' }}"
                                    href="#invoiceMenu">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>
                                        Invoices
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/invoices"
                                            class="nav-link{{ Request::is('invoices*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-list"></i>
                                            <p>List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/invoices/create"
                                            class="nav-link{{ Request::is('invoices/create') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-plus"></i>
                                            <p>Create</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-header">Parties &amp; Fleet</li>

                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('transporters*') ? ' active' : '' }}"
                                    href="#transporterMenu">
                                    <i class="nav-icon fas fa-building"></i>
                                    <p>
                                        Transporter / Agency
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/transporters"
                                            class="nav-link{{ Request::is('transporters*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-list"></i>
                                            <p>List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/transporters/create"
                                            class="nav-link{{ Request::is('transporters/create') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-plus"></i>
                                            <p>Create</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('truck_owners*') || Request::is('truck_owner_bank_accounts/create') ? ' active' : '' }}"
                                    href="#truckOwnerMenu">
                                    <i class="nav-icon fas fa-user-tie"></i>
                                    <p>
                                        Truck Owner
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/truck_owners"
                                            class="nav-link{{ Request::is('truck_owners*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-list"></i>
                                            <p>List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/truck_owners/create"
                                            class="nav-link{{ Request::is('truck_owners/create') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-plus"></i>
                                            <p>Create</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('trucks*') || Request::is('drivers*') ? ' active' : '' }}"
                                    href="#truckMenu">
                                    <i class="nav-icon fas fa-truck-moving"></i>
                                    <p>
                                        Truck
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/trucks"
                                            class="nav-link{{ Request::is('trucks*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-list"></i>
                                            <p>Truck List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/drivers"
                                            class="nav-link{{ Request::is('drivers*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-id-card"></i>
                                            <p>Driver List</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('consignees*') ? ' active' : '' }}"
                                    href="#consigneeMenu">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        Customers
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="/consignees"
                                            class="nav-link{{ Request::is('consignees*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-list"></i>
                                            <p>List</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="/consignees/create"
                                            class="nav-link{{ Request::is('consignees/create') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-plus"></i>
                                            <p>Create</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            @if (in_array(auth()->user()->userRole->name, ['super_admin', 'moderator', 'admin']))
                                <li class="nav-header">Setup</li>
                                <li class="nav-item">
                                    <a class="nav-link{{ Request::is('master/*') ? ' active' : '' }}" href="#masterMenu">
                                        <i class="nav-icon fas fa-database"></i>
                                        <p>
                                            Master
                                            <i class="nav-arrow bi bi-chevron-right"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="/master/countries" class="nav-link{{ Request::is('master/countries*') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-globe"></i>
                                                <p>Countries</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="/master/states" class="nav-link{{ Request::is('master/states*') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-map"></i>
                                                <p>States</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="/master/cities" class="nav-link{{ Request::is('master/cities*') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-city"></i>
                                                <p>Cities</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="/master/goods-types" class="nav-link{{ Request::is('master/goods-types*') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-box"></i>
                                                <p>Goods Types</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('master.truck-types.index') }}" class="nav-link{{ Request::is('master/truck-types*') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-truck"></i>
                                                <p>Truck Types</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="/master/pumps" class="nav-link{{ Request::is('master/pumps*') ? ' active' : '' }}">
                                                <i class="nav-icon fas fa-gas-pump"></i>
                                                <p>Pumps</p>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                        @endif

                        {{-- Accounting Section --}}
                        @if (auth()->check() && (in_array(auth()->user()->userRole->name, ['accountant', 'admin']) || (auth()->user()->userRole->name === 'super_admin' && auth()->user()->canAccessCompanyPanel())))
                            <li class="nav-header">Financials & Accounting</li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('accounting*') ? ' active' : '' }}" href="#accountingMenu"
                                    data-lte-toggle="collapse">
                                    <i class="nav-icon bi bi-calculator"></i>
                                    <p>
                                        Accounting <span class="badge bg-warning text-dark ms-1 fw-bold" style="font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; vertical-align: middle;">(BETA)</span>
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview collapse {{ Request::is('accounting*') ? ' show' : '' }}"
                                    id="accountingMenu">
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.day-book') }}"
                                            class="nav-link{{ Request::is('accounting/day-book*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-calendar-day"></i>
                                            <p>Day Book</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.ledger-statement') }}"
                                            class="nav-link{{ Request::is('accounting/ledger-statement*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-book-open"></i>
                                            <p>Ledger Statement</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.trial-balance') }}"
                                            class="nav-link{{ Request::is('accounting/trial-balance*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-balance-scale"></i>
                                            <p>Trial Balance</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.profit-and-loss') }}"
                                            class="nav-link{{ Request::is('accounting/profit-and-loss*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-chart-line"></i>
                                            <p>Profit & Loss</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.balance-sheet') }}"
                                            class="nav-link{{ Request::is('accounting/balance-sheet*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                            <p>Balance Sheet</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.debtors-aging') }}"
                                            class="nav-link{{ Request::is('accounting/debtors-aging*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-hourglass-half"></i>
                                            <p>Debtors Aging (MSME)</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.gstr1') }}"
                                            class="nav-link{{ Request::is('accounting/gstr1*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-file-invoice"></i>
                                            <p>GSTR-1 Preparation</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.vouchers.index') }}"
                                            class="nav-link{{ Request::is('accounting/vouchers*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-receipt"></i>
                                            <p>Vouchers Entry</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('accounting.ledgers.index') }}"
                                            class="nav-link{{ Request::is('accounting/ledgers*') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-sitemap"></i>
                                            <p>Chart of Accounts</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- Reports Section - admin, accountant, moderator, or super_admin in company context --}}
                        @if (auth()->check() && (in_array(auth()->user()->userRole->name, ['accountant', 'admin', 'moderator']) || (auth()->user()->userRole->name === 'super_admin' && auth()->user()->canAccessCompanyPanel())))
                            <li class="nav-header">Insights</li>
                            <li class="nav-item">
                                <a class="nav-link{{ Request::is('reports*') ? ' active' : '' }}" href="#reportsMenu"
                                    data-lte-toggle="collapse">
                                    <i class="nav-icon bi bi-graph-up-arrow"></i>
                                    <p>
                                        Reports
                                        <i class="nav-arrow bi bi-chevron-right"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview collapse {{ Request::is('reports*') ? ' show' : '' }}"
                                    id="reportsMenu">
                                    <li class="nav-item">
                                        <a href="{{ route('reports.dispatch') }}"
                                            class="nav-link{{ Request::is('reports/dispatch') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-file-alt"></i>
                                            <p>Dispatch</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.bank') }}"
                                            class="nav-link{{ Request::is('reports/bank') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-university"></i>
                                            <p>Bank</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.cash') }}"
                                            class="nav-link{{ Request::is('reports/cash') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-money-bill-wave"></i>
                                            <p>Cash</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.pump') }}"
                                            class="nav-link{{ Request::is('reports/pump') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-gas-pump"></i>
                                            <p>Pump</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.party-bill') }}"
                                            class="nav-link{{ Request::is('reports/party-bill') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-file-invoice"></i>
                                            <p>Party Bill</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.transporter-report') }}"
                                            class="nav-link{{ Request::is('reports/transporter-report') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-truck"></i>
                                            <p>Transporter Report</p>
                                        </a>
                                    </li>

                                 <li class="nav-item">
                                        <a href="{{ route('reports.profitability') }}"
                                        class="nav-link{{ Request::is('reports/profitability') ? ' active' : '' }}">
                                        <i class="nav-icon fas fa-chart-line"></i>
                                         <p>Profitability Report</p>
                                             </a>
                                         </li>

                                    <li class="nav-item">
                                        <a href="{{ route('reports.tds') }}"
                                            class="nav-link{{ Request::is('reports/tds') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-percent"></i>
                                            <p>TDS</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.invoice') }}"
                                            class="nav-link{{ Request::is('reports/invoice') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-receipt"></i>
                                            <p>GSTR1</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.party-ledger') }}"
                                            class="nav-link{{ Request::is('reports/party-ledger') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-book"></i>
                                            <p>Party Ledger</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.eway-bill') }}"
                                            class="nav-link{{ Request::is('reports/eway-bill') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-road"></i>
                                            <p>Eway Bill</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.shortage-damage') }}"
                                            class="nav-link{{ Request::is('reports/shortage-damage') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-exclamation-triangle"></i>
                                            <p>Shortage Damage</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.pending-invoice') }}"
                                            class="nav-link{{ Request::is('reports/pending-invoice') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-clock"></i>
                                            <p>Pending Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.party-pending-invoice') }}"
                                            class="nav-link{{ Request::is('reports/party-pending-invoice') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-hourglass-half"></i>
                                            <p>Party Pending Invoice</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.pending-memo') }}"
                                            class="nav-link{{ Request::is('reports/pending-memo') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-sticky-note"></i>
                                            <p>Pending Memo / POD</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.consignor-consignee') }}"
                                            class="nav-link{{ Request::is('reports/consignor-consignee') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-people-arrows"></i>
                                            <p>Consignor Consignee</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.lr-copy') }}"
                                            class="nav-link{{ Request::is('reports/lr-copy') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-copy"></i>
                                            <p>LR Copy</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.memo-copy') }}"
                                            class="nav-link{{ Request::is('reports/memo-copy') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-clone"></i>
                                            <p>Memo / POD Copy</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reports.transporter-master') }}"
                                            class="nav-link{{ Request::is('reports/transporter-master') ? ' active' : '' }}">
                                            <i class="nav-icon fas fa-address-book"></i>
                                            <p>Transporter Master</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                    <!--end::Sidebar Menu-->
                </nav>
            </div>
            <!--end::Sidebar Wrapper-->

            @auth
                <div class="sidebar-user">
                    <i class="fas fa-user-circle sidebar-user__avatar" aria-hidden="true"></i>
                    <div class="sidebar-user__info">
                        <span class="sidebar-user__name">{{ auth()->user()->name }}</span>
                        <span class="sidebar-user__role">{{ str_replace('_', ' ', auth()->user()->userRole->name ?? '') }}</span>
                    </div>
                </div>
            @endauth
            </div>
            <!--end::Sidebar Container-->
        </aside>
        <main class="app-main">
            <!--begin::App Content Header-->
            <div class="app-content-header">
                <!--begin::Container-->
                <div class="container-fluid">
                    @include('partials.subscription-access-banner')
                    @yield('content')

                    <!-- /.row (main row) -->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content-->
        </main>

        <footer class="app-footer bg-white border-top">
            <div class="container-fluid">
                <div class="truckbill-footer-content">
                    <!-- Branding: TruckBill — A Product of Taldar Tech Consultancy -->
                    <div class="footer-col-brand d-inline-flex align-items-center text-nowrap">
                        <span class="fw-bold text-dark">TruckBill</span>
                        <span class="footer-separator fw-semibold" style="color: #475569;">&mdash;</span>
                        <span class="d-inline-flex align-items-center">A Product of&nbsp;<a href="{{ config('app.company_url', 'https://www.taldartechconsultancy.com/') }}" target="_blank" rel="noopener noreferrer" class="text-success text-decoration-none fw-semibold">Taldar Tech Consultancy</a></span>
                    </div>

                    <!-- Footer Links: Help & Support, Privacy Policy, Terms & Conditions, Contact Us -->
                    <nav class="footer-col-nav d-inline-flex align-items-center justify-content-center text-nowrap" aria-label="Footer Navigation">
                        <a href="#helpSupportModal" class="footer-link fw-medium" data-bs-toggle="modal" data-bs-target="#helpSupportModal" role="button">Help &amp; Support</a>
                        <span class="footer-separator" aria-hidden="true">&bull;</span>
                        <a href="https://www.truckbill.in/policies/privacy-policy.html" target="_blank" rel="noopener noreferrer" class="footer-link fw-medium">Privacy Policy</a>
                        <span class="footer-separator" aria-hidden="true">&bull;</span>
                        <a href="https://www.truckbill.in/policies/terms-and-conditions.html" target="_blank" rel="noopener noreferrer" class="footer-link fw-medium">Terms &amp; Conditions</a>
                        <span class="footer-separator" aria-hidden="true">&bull;</span>
                        <a href="https://www.truckbill.in/#contact" target="_blank" rel="noopener noreferrer" class="footer-link fw-medium">Contact Us</a>
                    </nav>

                    <!-- Copyright & Dynamic Application Version -->
                    <div class="footer-col-meta d-inline-flex align-items-center text-nowrap gap-2">
                        <span class="d-inline-flex align-items-center">&copy; {{ config('app.copyright_year', date('Y')) }} TruckBill. All rights reserved.</span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill footer-version-badge" title="Application Version">
                            v{{ ltrim(config('app.version', '1.0.0'), 'v') }}
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <!--end::App Wrapper-->

    <!-- Help & Support Modal -->
    <!-- Help & Support Modal -->
    <div class="modal fade" id="helpSupportModal" tabindex="-1" aria-labelledby="helpSupportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                <!-- Modal Header -->
                <div class="modal-header bg-success text-white px-4 py-3 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-success flex-shrink-0" style="width: 38px; height: 38px;">
                            <i class="fas fa-headset fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="helpSupportModalLabel">Help &amp; Support</h5>
                            <span class="small text-white-50">TruckBill Technical &amp; Operational Assistance</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4 text-start">
                    <p class="text-muted small mb-3">
                        Need assistance with billing, lorry receipts (LR), GST compliance, or fleet operations? Contact our support team:
                    </p>

                    <!-- Email Contact Card -->
                    <div class="d-flex align-items-center p-3 mb-3 rounded-3 bg-success-subtle border border-success-subtle">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 bg-success text-white" style="width: 42px; height: 42px;">
                            <i class="fas fa-envelope fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-secondary small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.04em;">Email Support</div>
                            <a href="https://mail.google.com/mail/?view=cm&fs=1&to={{ config('app.support_email', 'support@truckbill.in') }}&su=TruckBill%20Support%20Inquiry" target="_blank" rel="noopener noreferrer" class="fw-bold text-decoration-none text-success text-truncate d-block" style="font-size: 0.95rem;" title="Click to open in Gmail">
                                {{ config('app.support_email', 'support@truckbill.in') }}
                            </a>
                        </div>
                        <button type="button" class="btn btn-sm bg-white border border-success text-success px-3 py-1 fw-semibold rounded-pill ms-2 flex-shrink-0" onclick="navigator.clipboard.writeText('{{ config('app.support_email', 'support@truckbill.in') }}'); const orig=this.innerHTML; this.innerHTML='<i class=\'fas fa-check me-1\'></i> Copied!'; setTimeout(() => this.innerHTML=orig, 2000);" title="Copy email address">
                            <i class="far fa-copy me-1"></i> Copy
                        </button>
                    </div>

                    <!-- Phone Contact Card -->
                    <div class="d-flex align-items-center p-3 mb-3 rounded-3 bg-primary-subtle border border-primary-subtle">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 bg-primary text-white" style="width: 42px; height: 42px;">
                            <i class="fas fa-phone-alt fa-lg"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-secondary small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.04em;">Helpline &amp; Contact</div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                <a href="tel:+919784451256" class="fw-bold text-decoration-none text-primary" style="font-size: 0.95rem;" title="Call +91 97844 51256">
                                    +91 97844 51256
                                </a>
                                <span class="text-secondary opacity-50">&bull;</span>
                                <a href="tel:+919784946004" class="fw-bold text-decoration-none text-primary" style="font-size: 0.95rem;" title="Call +91 97849 46004">
                                    +91 97849 46004
                                </a>
                            </div>
                        </div>
                        <button id="phoneCopyBtn" type="button" class="btn btn-sm bg-white border border-primary text-primary px-3 py-1 fw-semibold rounded-pill ms-2 flex-shrink-0" onclick="navigator.clipboard.writeText('+91 97844 51256, +91 97849 46004'); const o=this.innerHTML; this.innerHTML='<i class=\'fas fa-check me-1\'></i> Copied!'; setTimeout(()=>this.innerHTML=o, 2000); if(window.Swal){ Swal.fire({toast:true, position:'top-end', icon:'success', title:'Phone numbers copied!', showConfirmButton:false, timer:2000}); }" title="Copy phone numbers">
                            <i class="far fa-copy me-1"></i> Copy
                        </button>
                    </div>

                    <!-- Support Details -->
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted small"><i class="fas fa-clock text-warning me-1"></i> Working Hours:</span>
                            <span class="fw-semibold small text-dark">Mon &ndash; Sat: 10:00 AM &ndash; 7:00 PM IST</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted small"><i class="fas fa-building text-secondary me-1"></i> Product by:</span>
                            <a href="{{ config('app.company_url', 'https://www.taldartechconsultancy.com/') }}" target="_blank" rel="noopener noreferrer" class="fw-semibold small text-decoration-none text-success">
                                {{ config('app.company_name', 'Taldar Tech Consultancy') }} <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light px-4 py-2 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold rounded-pill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- bootstrap.bundle includes Popper; do not also load popper + bootstrap.min.js --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/theme-ui.js') }}?v={{ config('app.ASSET_VERSION') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
        integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ=" crossorigin="anonymous"></script>
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>
    <!-- AdminLTE JS -->
    <script src="/theme/dist/js/adminlte.min.js?v={{ config('app.ASSET_VERSION') }}"></script>
    <script>
        $(document).off('select2:open.autofocus').on('select2:open.autofocus', function() {
            // Delay to let the search input render
            setTimeout(function() {
                const searchInput = document.querySelector(
                    '.select2-container--open .select2-search__field');
                if (searchInput) {
                    searchInput.focus();
                }
            }, 0);
        });
        // Initialize AdminLTE
        $(document).ready(function() {
            // Check if AdminLTE is loaded
            if (typeof $.AdminLTE !== 'undefined') {
                // Initialize layout fix
                if ($.AdminLTE.layout) {
                    $.AdminLTE.layout.fix();
                    $.AdminLTE.layout.fixSidebar();
                }

                // Initialize dropdowns
                if ($.AdminLTE.dropdown) {
                    $.AdminLTE.dropdown.init();
                }
            }

            // Bootstrap 5 no longer exposes jQuery dropdown plugins by default.
            // Initialize dropdowns via native API and avoid broad click overrides
            // that can interfere with sidebar/treeview links.
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle[data-bs-toggle="dropdown"]');
            dropdownToggles.forEach(function(toggleEl) {
                bootstrap.Dropdown.getOrCreateInstance(toggleEl);
            });

            // Initialize tooltips and popovers
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
    </script>
    <script>
        // Prevent accidental wheel-based step changes in number inputs.
        document.addEventListener('wheel', function(event) {
            const numberInput = event.target.closest('input[type="number"]');
            if (!numberInput || numberInput.disabled || numberInput.readOnly) {
                return;
            }
            numberInput.blur();
        }, {
            passive: false
        });
    </script>
    <script>
        // Clear DataTable localStorage state on page load to reset filters
        (function() {
            // Get current page path
            var currentPath = window.location.pathname;

            // Clear all DataTable state from localStorage
            // DataTables stores state with keys like: DataTables_<table-id>_<page-path>
            var keysToRemove = [];
            for (var i = 0; i < localStorage.length; i++) {
                var key = localStorage.key(i);
                if (key && key.startsWith('DataTables_')) {
                    keysToRemove.push(key);
                }
            }

            // Remove all DataTable state keys
            keysToRemove.forEach(function(key) {
                localStorage.removeItem(key);
            });
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
        integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
    <!-- ChartJS -->
    <script>
        // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
        // IT'S ALL JUST JUNK FOR DEMO
        // ++++++++++++++++++++++++++++++++++++++++++

        const sales_chart_options = {
            series: [{
                    name: 'Digital Goods',
                    data: [28, 48, 40, 19, 86, 27, 90],
                },
                {
                    name: 'Electronics',
                    data: [65, 59, 80, 81, 56, 55, 40],
                },
            ],
            chart: {
                height: 300,
                type: 'area',
                toolbar: {
                    show: false,
                },
            },
            legend: {
                show: false,
            },
            colors: ['#0d6efd', '#20c997'],
            dataLabels: {
                enabled: false,
            },
            stroke: {
                curve: 'smooth',
            },
            xaxis: {
                type: 'datetime',
                categories: [
                    '2023-01-01',
                    '2023-02-01',
                    '2023-03-01',
                    '2023-04-01',
                    '2023-05-01',
                    '2023-06-01',
                    '2023-07-01',
                ],
            },
            tooltip: {
                x: {
                    format: 'MMMM yyyy',
                },
            },
        };

        // Only render chart if element exists
        const revenueChartElement = document.querySelector('#revenue-chart');
        if (revenueChartElement) {
            const sales_chart = new ApexCharts(revenueChartElement, sales_chart_options);
            sales_chart.render();
        }
    </script>

    <!-- jsvectormap -->
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"
        integrity="sha256-/t1nN2956BT869E6H4V1dnt0X5pAQHPytli+1nTZm2Y=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"
        integrity="sha256-XPpPaZlU8S/HWf7FZLAncLg2SAkP8ScUTII89x9D3lY=" crossorigin="anonymous"></script>
    <!-- jsvectormap -->
    <script>
        const visitorsData = {
            US: 398, // USA
            SA: 400, // Saudi Arabia
            CA: 1000, // Canada
            DE: 500, // Germany
            FR: 760, // France
            CN: 300, // China
            AU: 700, // Australia
            BR: 600, // Brazil
            IN: 800, // India
            GB: 320, // Great Britain
            RU: 3000, // Russia
        };

        // World map by jsVectorMap - only render if element exists
        const worldMapElement = document.querySelector('#world-map');
        if (worldMapElement) {
            const map = new jsVectorMap({
                selector: '#world-map',
                map: 'world',
            });
        }

        // Sparkline charts
        const option_sparkline1 = {
            series: [{
                data: [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021],
            }, ],
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true,
                },
            },
            stroke: {
                curve: 'straight',
            },
            fill: {
                opacity: 0.3,
            },
            yaxis: {
                min: 0,
            },
            colors: ['#DCE6EC'],
        };

        // Sparkline 1 - only render if element exists
        const sparkline1Element = document.querySelector('#sparkline-1');
        if (sparkline1Element) {
            const sparkline1 = new ApexCharts(sparkline1Element, option_sparkline1);
            sparkline1.render();
        }

        const option_sparkline2 = {
            series: [{
                data: [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921],
            }, ],
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true,
                },
            },
            stroke: {
                curve: 'straight',
            },
            fill: {
                opacity: 0.3,
            },
            yaxis: {
                min: 0,
            },
            colors: ['#DCE6EC'],
        };

        // Sparkline 2 - only render if element exists
        const sparkline2Element = document.querySelector('#sparkline-2');
        if (sparkline2Element) {
            const sparkline2 = new ApexCharts(sparkline2Element, option_sparkline2);
            sparkline2.render();
        }

        const option_sparkline3 = {
            series: [{
                data: [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21],
            }, ],
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true,
                },
            },
            stroke: {
                curve: 'straight',
            },
            fill: {
                opacity: 0.3,
            },
            yaxis: {
                min: 0,
            },
            colors: ['#DCE6EC'],
        };

        // Sparkline 3 - only render if element exists
        const sparkline3Element = document.querySelector('#sparkline-3');
        if (sparkline3Element) {
            const sparkline3 = new ApexCharts(sparkline3Element, option_sparkline3);
            sparkline3.render();
        }
    </script>
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.1.1/ckeditor5.css" />
    <script type="importmap">
        {
            "imports": {
                "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.1.1/ckeditor5.js",
                "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.1.1/"
            }
        }
    </script>
    <script type="module">
        import {
            ClassicEditor,
            Essentials,
            Bold,
            Italic,
            Font,
            Paragraph,
            Heading,
            Underline,
            HorizontalLine
        } from 'ckeditor5';

        document.querySelectorAll('.texteditor').forEach((editorElement) => {
            ClassicEditor
                .create(editorElement, {
                    plugins: [Essentials, Bold, Italic, Font, Paragraph, Heading, Underline, HorizontalLine],
                    toolbar: [
                        'undo', 'redo', '|', 'bold', 'italic', 'underline', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'Heading',
                        'HorizontalLine'
                    ]
                })
                .then(editorElement => {
                    $('.ck-body-wrapper').hide();
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
    <!--end::Script-->
    @yield('scripts')
    @stack('scripts')

</body>

</html>
