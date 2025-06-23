<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Voucher Payments</h2>
        <a href="<?php echo e(route('voucher_payments.create')); ?>" class="btn btn-primary">Add Payment</a>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-3">
        <?php
            $totalPayments = $payments->total();
            $pendingCount = $payments->where('payment_status', 'pending')->count();
            $inProgressCount = $payments->where('payment_status', 'in-progress')->count();
            $paidCount = $payments->where('payment_status', 'paid')->count();
        ?>
        <div class="col-md-3">
            <div class="card text-bg-primary mb-2">
                <div class="card-body p-2">
                    Total Payments: <b><?php echo e($totalPayments); ?></b>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-warning mb-2">
                <div class="card-body p-2">
                    Pending: <b><?php echo e($pendingCount); ?></b>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-info mb-2">
                <div class="card-body p-2">
                    In Progress: <b><?php echo e($inProgressCount); ?></b>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success mb-2">
                <div class="card-body p-2">
                    Paid: <b><?php echo e($paidCount); ?></b>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Voucher #</th>
                            <th>Payment Type</th>
                            <th>Payment Mode</th>
                            <th>Transaction ID</th>
                            <th>Remark</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($payment->id); ?></td>
                                <td><?php echo e($payment->voucher->voucher_number ?? 'N/A'); ?></td>
                                <td><?php echo e($payment->payment_type); ?></td>
                                <td><?php echo e($payment->payment_mode); ?></td>
                                <td><?php echo e($payment->transaction_id ?? 'N/A'); ?></td>
                                <td><?php echo e($payment->remark ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo e($payment->payment_status === 'pending' ? 'warning' : ($payment->payment_status === 'in-progress' ? 'info' : 'success')); ?>">
                                        <?php echo e($payment->payment_status); ?>

                                    </span>
                                </td>
                                <td><?php echo e($payment->created_at->format('Y-m-d H:i')); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo e(route('voucher_payments.edit', $payment)); ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form action="<?php echo e(route('voucher_payments.destroy', $payment)); ?>" method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <?php echo e($payments->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/voucher_payments/index.blade.php ENDPATH**/ ?>