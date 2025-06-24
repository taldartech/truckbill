<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Voucher Details</h2>
    <div class="mb-3">
        <a href="<?php echo e(route('vouchers.index')); ?>" class="btn btn-secondary">Back to List</a>
        <a href="<?php echo e(route('vouchers.edit', $voucher->id)); ?>" class="btn btn-warning ms-2">Edit Voucher</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Voucher #<?php echo e($voucher->id); ?></h5>
            <p class="card-text">
                <b>Freight Booking:</b> <?php echo e($voucher->freight_booking_id); ?><br>
                <b>Truck:</b> <?php echo e($voucher->truck ? $voucher->truck->truck_number : '-'); ?><br>
                <b>Transporter:</b> <?php echo e($voucher->transporter ? $voucher->transporter->name : '-'); ?><br>
                <b>PAN Verified:</b> <?php echo e($voucher->pan_verified ? 'Yes' : 'No'); ?><br>
                <b>Status:</b> <?php echo e(ucfirst($voucher->status)); ?><br>
            </p>
        </div>
    </div>

    <?php
        $totalPayments = $voucher->payments->count();
        $paidPayments = $voucher->payments->where('payment_status', 'paid')->count();
        $pendingPayments = $voucher->payments->where('payment_status', 'pending')->count();
        $inProgressPayments = $voucher->payments->where('payment_status', 'in-progress')->count();
    ?>
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="alert alert-info p-2 mb-1">Total Payments: <b><?php echo e($totalPayments); ?></b></div>
        </div>
        <div class="col-md-3">
            <div class="alert alert-success p-2 mb-1">Paid: <b><?php echo e($paidPayments); ?></b></div>
        </div>
        <div class="col-md-3">
            <div class="alert alert-warning p-2 mb-1">In-Progress: <b><?php echo e($inProgressPayments); ?></b></div>
        </div>
        <div class="col-md-3">
            <div class="alert alert-secondary p-2 mb-1">Pending: <b><?php echo e($pendingPayments); ?></b></div>
        </div>
    </div>

    <h4>Voucher Payments</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Mode</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $voucher->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($payment->id); ?></td>
                <td><?php echo e($payment->payment_type); ?></td>
                <td><?php echo e($payment->payment_mode); ?></td>
                <td><?php echo e($payment->transaction_id); ?></td>
                <td><?php echo e(ucfirst($payment->payment_status)); ?></td>
                <td><?php echo e($payment->remark); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" class="text-center">No payments found for this voucher.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/vouchers/show.blade.php ENDPATH**/ ?>