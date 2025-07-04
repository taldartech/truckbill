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
    <!-- AdminLTE Theme CSS -->
    <link href="/theme/dist/css/adminlte.min.css" rel="stylesheet">
    <!-- You can add more theme CSS here if needed -->
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
                <li class="nav-item dropdown user-menu">
                  <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fa-lg"></i>
                    @auth
                        <span class="d-none d-md-inline">{{ Auth::user()->name ?? Auth::user()->email }}</span>
                    @endauth
                  </a>
                  <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                  
                   
                    <!--begin::Menu Footer-->
                    <li class="user-footer">
                      <a href="/profile" class="btn btn-default btn-flat">Profile</a>
                      <a href="#" class="btn btn-default btn-flat float-end" id="logout-link">Sign out</a>
                      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    </li>
                    <!--end::Menu Footer-->
                  </ul>
                </li>
                <!--end::User Menu Dropdown-->
              </ul>
              <!--end::End Navbar Links-->
            </div>
            <!--end::Container-->
          </nav>
          
          <!--end::Header-->
          <!--begin sidebar -->
           <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="#" class="brand-link">
           
            <!--begin::Brand Text-->
            <span class="brand-text fw-light" style="font-size: 1.2em;"> Transport Management</span>
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
              <li class="nav-item menu-open">
                <a href="/dashboard" class="nav-link text-white">
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
                        <p>Register Transporter</p>
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
      <!--end::Sidebar-->
   
        <div style="margin-left:220px;flex:1;padding-top:56px;">
            <div class="p-4">
                @yield('content')
            </div>
        </div>
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
