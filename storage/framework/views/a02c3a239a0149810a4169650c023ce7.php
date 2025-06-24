<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .dashboard-title { margin-top: 2rem; margin-bottom: 2rem; }
        .card { min-height: 170px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center dashboard-title">Invoice & Transport Management System</h1>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col"><a href="/company" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Company Registration</h5><p class="card-text">Register and manage company details.</p></div></div></a></div>
            <div class="col"><a href="/transporters" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Transport/Truck Owner Registration</h5><p class="card-text">Register agencies and truck owners.</p></div></div></a></div>
            <div class="col"><a href="/trucks" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Truck Details Entry</h5><p class="card-text">Record truck and owner details.</p></div></div></a></div>

<div class="col"><a href="/customers" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Customer Management</h5><p class="card-text">Add, view, and manage customers for billing and invoicing.</p></div></div></a></div>
<div class="col"><a href="<?php echo e(route('consignees.index')); ?>" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Consignee Management</h5><p class="card-text">Manage consignees for freight bookings and LR generation.</p></div></div></a></div>          

<div class="col"><a href="/lorry_receipts" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">LR Generation</h5><p class="card-text">Enter and manage LRs. Validation checks included.</p></div></div></a></div>
<div class="col"><a href="/freight_bookings" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Freight Booking Entries</h5><p class="card-text">Assign bookings, manage freight rates, advances, and payments.</p></div></div></a></div>
<div class="col"><a href="/vouchers" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Voucher Generation</h5><p class="card-text">Generate and manage payment vouchers for freight bookings.</p></div></div></a></div>
<div class="col"><a href="<?php echo e(route('voucher_payments.index')); ?>" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Voucher Payment Tracking</h5><p class="card-text">Track payment status for each voucher.</p></div></div></a></div>
<div class="col"><a href="/invoices" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">Invoice Management</h5><p class="card-text">Create, view, and manage invoices and their payments.</p></div></div></a></div>


            
            
            
            
            <div class="col"><a href="/users" class="text-decoration-none"><div class="card h-100"><div class="card-body"><h5 class="card-title">User Management</h5><p class="card-text">Role-based access control and user roles.</p></div></div></a></div>
            
            
        <footer class="text-center mt-5 mb-3 text-muted">&copy; 2025 Invoice & Transport Management System</footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php /**PATH /opt/lampp/htdocs/transport/resources/views/dashboard.blade.php ENDPATH**/ ?>