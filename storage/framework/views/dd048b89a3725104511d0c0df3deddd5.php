<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Voucher Payments</h2>
        <a href="<?php echo e(route('voucher_payments.create')); ?>" class="btn btn-primary">Add New Payment</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Voucher #</th>
                            <th>Payment Type</th>
                            <th>Payment Mode</th>
                            <th>Transaction ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($payment->voucher->voucher_number); ?></td>
                                <td><?php echo e(ucfirst($payment->payment_type)); ?></td>
                                <td><?php echo e(ucfirst(str_replace('_', ' ', $payment->payment_mode))); ?></td>
                                <td><?php echo e($payment->transaction_id); ?></td>
                                <td>₹<?php echo e(number_format($payment->amount, 2)); ?></td>
                                <td><?php echo $payment->getPaymentStatusBadge(); ?></td>
                                <td>
                                    <a href="<?php echo e(route('voucher_payments.edit', $payment)); ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="<?php echo e(route('voucher_payments.destroy', $payment)); ?>" method="POST" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
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