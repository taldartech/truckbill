<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Invoice & Transport Management System')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FontAwesome for AdminLTE Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- OverlayScrollbars CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <!-- AdminLTE Theme CSS -->
    <link href="/theme/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- You can add more theme CSS here if needed -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
    />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css"
      integrity="sha256-tZHrRjVqNSRyWg2wbppGnT833E/Ys0DHWGwT04GiqQg="
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI="
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="../../dist/css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
    <!-- apexcharts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous"
    />
    <!-- jsvectormap -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
      integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4="
      crossorigin="anonymous"
    />
</head>

    <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
        <!--begin::App Wrapper-->
        <div class="app-wrapper">
          <!--begin::Header-->
          <nav class="app-header navbar navbar-expand bg-body">
            <!--begin::Container-->
            <div class="container-fluid">
              <!--begin::Start Navbar Links-->
              <ul class="navbar-nav">
                <li class="nav-item">
                  <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fa-solid fa-bars"></i>
                  </a>
                </li>
                <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">  
                     @auth
                    <span class="align-middle ms-3 me-2 border-start ps-3 small text-muted" style="font-size: 1em;">
                        <i class="fas fa-building me-1"></i>{{ Auth::user()->company ? Auth::user()->company->name : '' }}
                    </span>
                @endauth</a>
            </li>
               
              </ul>
              <!--end::Start Navbar Links-->
              <!--begin::End Navbar Links-->
              <ul class="navbar-nav ms-auto">
              
               
            
              
               
                <!--begin::User Menu Dropdown-->
                <!--begin::User Menu Dropdown-->
                <li class="nav-item dropdown user-menu">
                  <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fa-lg"></i>
                    @auth
                        <span class="d-none d-md-inline">{{ Auth::user()->name ?? Auth::user()->email }}</span>
                    @endauth
                  </a>
                  <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <!--begin::User Image-->
                    <div class="user-header bg-primary">
                      <i class="fas fa-user-circle fa-3x"></i>
                      <p>
                        @auth
                          {{ Auth::user()->name ?? Auth::user()->email }}
                          <small>Member since {{ Auth::user()->created_at->format('M. Y') }}</small>
                        @endauth
                      </p>
                    </div>
                    <!--end::User Image-->
                    <!--begin::Menu Body-->
                    <div class="user-body">
                      <!--begin::Row-->
                     
                      <!--end::Row-->
                    </div>
                    <!--end::Menu Body-->
                    <!--begin::Menu Footer-->
                    <div class="user-footer">
                      <a href="/profile" class="btn btn-default btn-flat">Profile</a>
                      <a href="#" class="btn btn-default btn-flat float-end" id="logout-link">Sign out</a>
                      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                      </form>
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
          <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <!--begin::Sidebar Brand-->
            <div class="sidebar-brand">
              <!--begin::Brand Link-->
              <a href="#" class="brand-link">
               
                <!--begin::Brand Text-->
                <span class="brand-text fw-light" style="font-size: 1.2em;"> TruckMate-TMS</span>
                <!--end::Brand Text-->
              </a>
              <!--end::Brand Link-->
            </div>
            <!--end::Sidebar Brand-->
            <!--begin::Sidebar Wrapper-->
            <div class="sidebar-wrapper">
              <nav class="mt-2">
                <!--begin::Sidebar Menu-->
                <ul
                  class="nav sidebar-menu flex-column"
                  data-lte-toggle="treeview"
                  role="menu"
                  data-accordion="false"
                >
                  <li class="nav-item ">
                    <a href="/dashboard" class="nav-link">
                      <i class="nav-icon bi bi-speedometer"></i>
                      <p>
                        Dashboard
                       
                      </p>
                    </a>
                   
                  </li>
                  @if(auth()->check() && auth()->user()->userRole->name === 'super_admin')
                  <li class="nav-item">
                    <a class="nav-link "  href="#companyMenu"  >
                        <i class="fas fa-money-check-alt"></i>  <p>
                            Company
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/company" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p>Company List</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/company/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register Company</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a href="/users" class="nav-link ">
                        <i class="fas fa-users"></i> 
                        <p>Users</p>
                    </a>
                </li>
                <li class="nav-item" >
                    <a class="nav-link "  href="#masterMenu" >
                        <i class="fas fa-money-check-alt"></i>  <p>
                            Master
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/master/countries" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p>Countries</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/master/states" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>States</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/master/cities" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Cities</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/master/goods-types" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Goods Types</p>
                            </a>
                          </li>
                    </ul>
                  </li>
    
                  {{-- end master --}}
                    
                 
                  @endif
                  @if(auth()->check() && auth()->user()->userRole->name !== 'super_admin')
                  
                 
                  <li class="nav-item">
                    <a class="nav-link" href="#transporterMenu">
                        <i class="fas fa-truck"></i> 
                        <p> Transporter </p>
                        <i class="nav-arrow bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/transporters" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p>Transporter List</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/transporters/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/transporter_bank_accounts/create" class="nav-link">
                              <i class="fas fa-university"></i> 
                            <p>Add Bank Details</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link "  href="#truckMenu"  >
                        <i class="fas fa-truck-moving"></i> <p>
                            Truck
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/trucks" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p>Truck List</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/trucks/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register Truck</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/drivers/create" class="nav-link">
                                <i class="fas fa-user-tie"></i>
                            <p>Add Driver</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link "  href="#customerMenu"  >
                        <i class="fas fa-users"></i><p>
                            Customer
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/customers" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p>Customer List</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/customers/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link "  href="#consigneeMenu"  >
                        <i class="fas fa-people-carry"></i><p>
                            Consignee
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/consignees" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p>Consignee List</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/consignees/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link "  href="#lorryReceiptMenu"  >
                        <i class="fas fa-file-alt"></i><p>
                            Lorry Receipts
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/lorry_receipts" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p> Listing</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/lorry_receipts/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link "  href="#freightBookingMenu"  >
                        <i class="fas fa-shipping-fast"></i><p>
                            Freight Bookings
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/freight_bookings" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p> Listing</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/freight_bookings/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link "  href="#voucherMenu"  >
                        <i class="fas fa-file-invoice"></i><p>
                            Vouchers
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/vouchers" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p> Listing</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/vouchers/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                    </ul>
                  </li>
    
                  <li class="nav-item">
                    <a class="nav-link "  href="#voucherPaymentMenu"  >
                        <i class="fas fa-money-bill-wave"></i><p>
                            Voucher Payments
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/voucher_payments" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p> Listing</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/voucher_payments/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link "  href="#invoiceMenu"  >
                        <i class="fas fa-file-invoice-dollar"></i><p>
                            Invoices
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/invoices" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p> Listing</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/invoices/create" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Register</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  <li class="nav-item" >
                    <a class="nav-link "  href="#masterMenu" >
                        <i class="fas fa-money-check-alt"></i>  <p>
                            Master
                            <i class="nav-arrow bi bi-chevron-right"></i>
                          </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/master/countries" class="nav-link">
                                <i class="fas fa-list"></i> 
                              <p>Countries</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/master/states" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>States</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/master/cities" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Cities</p>
                            </a>
                          </li>
                          <li class="nav-item">
                            <a href="/master/goods-types" class="nav-link">
                              <i class="fas fa-plus"></i> 
                            <p>Goods Types</p>
                            </a>
                          </li>
                    </ul>
                  </li>
                  
                  <li class="nav-item">
                    <a href="/reports" class="nav-link">
                        <i class="fas fa-chart-bar"></i> 
                      <p>Reports</p>
                    </a>
                  </li> 
                 
               
                
               
               
                  
                 
                   
                
                
    
                  @endif
                </ul>
                <!--end::Sidebar Menu-->
              </nav>
            </div>
    
            <!--end::Sidebar Wrapper-->
          </aside>
          <main class="app-main">
            <!--begin::App Content Header-->
            <div class="app-content-header">
              <!--begin::Container-->
              <div class="container-fluid">
                <!--begin::Row-->
                @yield('content')
                  
                <!-- /.row (main row) -->
              </div>
              <!--end::Container-->
            </div>
            <!--end::App Content-->
          </main>

           <!--begin::Footer-->
      <footer class="app-footer">
        <!--begin::To the end-->
        <div class="float-end d-none d-sm-inline"> Taldar Tech Consultancy </div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        <strong>
          Copyright &copy; 2025&nbsp;
         
        </strong>
  
        <!--end::Copyright-->
      </footer>
      <!--end::Footer-->

        </div>
          

        
          
         
   
      
      <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
      integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
      integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
      crossorigin="anonymous"
    ></script>
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
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
    <!-- jQuery (required for AdminLTE) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Popper.js (required for AdminLTE v4 sidebar) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <!-- Bootstrap JS (recommended for dropdowns, tooltips, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->    
    <script src="../../dist/js/adminlte.js"></script>
    <script>
        // Initialize AdminLTE
        $(document).ready(function () {
            // Initialize layout fix
            $.AdminLTE.layout.fix();
            $.AdminLTE.layout.fixSidebar();
            
            // Initialize dropdowns
            $.AdminLTE.dropdown.init();
            
            // Initialize all dropdowns
            $('.dropdown-toggle').dropdown();
            
            // Add click handler for dropdowns
            $('.dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                $(this).dropdown('toggle');
            });
            
            // Initialize tooltips and popovers
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
    </script>
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

<script
src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8="
crossorigin="anonymous"
></script>
<!-- ChartJS -->
<script>
// NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
// IT'S ALL JUST JUNK FOR DEMO
// ++++++++++++++++++++++++++++++++++++++++++

const sales_chart_options = {
  series: [
    {
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

const sales_chart = new ApexCharts(
  document.querySelector('#revenue-chart'),
  sales_chart_options,
);
sales_chart.render();
</script>

<!-- jsvectormap -->
<script
src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"
integrity="sha256-/t1nN2956BT869E6H4V1dnt0X5pAQHPytli+1nTZm2Y="
crossorigin="anonymous"
></script>
<script
src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"
integrity="sha256-XPpPaZlU8S/HWf7FZLAncLg2SAkP8ScUTII89x9D3lY="
crossorigin="anonymous"
></script>
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

// World map by jsVectorMap
const map = new jsVectorMap({
  selector: '#world-map',
  map: 'world',
});

// Sparkline charts
const option_sparkline1 = {
  series: [
    {
      data: [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021],
    },
  ],
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

const sparkline1 = new ApexCharts(document.querySelector('#sparkline-1'), option_sparkline1);
sparkline1.render();

const option_sparkline2 = {
  series: [
    {
      data: [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921],
    },
  ],
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

const sparkline2 = new ApexCharts(document.querySelector('#sparkline-2'), option_sparkline2);
sparkline2.render();

const option_sparkline3 = {
  series: [
    {
      data: [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21],
    },
  ],
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

const sparkline3 = new ApexCharts(document.querySelector('#sparkline-3'), option_sparkline3);
sparkline3.render();
</script>
<!--end::Script-->
    <script src="/js/dynamic-dropdowns.js"></script>
    <!-- AdminLTE JS -->
    <script src="/theme/dist/js/adminlte.min.js"></script>
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
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
</body>
</html>
