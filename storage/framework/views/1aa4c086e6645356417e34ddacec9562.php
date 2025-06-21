<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Voucher Payments</h2>
    <a href="<?php echo e(route('voucher_payments.create')); ?>" class="btn btn-primary mb-3">Add Voucher Payment</a>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="payment_status" class="form-label">Payment Status</label>
            <select name="payment_status" id="payment_status" class="form-select">
                <option value="">All</option>
                <option value="pending" <?php echo e(request('payment_status') == 'pending' ? 'selected' : ''); ?>>Pending</option>
                <option value="in-progress" <?php echo e(request('payment_status') == 'in-progress' ? 'selected' : ''); ?>>In-Progress</option>
                <option value="paid" <?php echo e(request('payment_status') == 'paid' ? 'selected' : ''); ?>>Paid</option>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    </form>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Voucher</th>
                <th>Type</th>
                <th>Mode</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($payment->id); ?></td>
                <td><?php echo e($payment->voucher ? $payment->voucher->id : '-'); ?></td>
                <td><?php echo e($payment->payment_type); ?></td>
                <td><?php echo e($payment->payment_mode); ?></td>
                <td><?php echo e($payment->transaction_id); ?></td>
                <td><?php echo e(ucfirst($payment->payment_status)); ?></td>
                <td>
                    <a href="<?php echo e(route('voucher_payments.edit', $payment->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="<?php echo e(route('voucher_payments.destroy', $payment->id)); ?>" method="POST" style="display:inline-block;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7">No payments found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($payments->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/voucher_payments/index.blade.php ENDPATH**/ ?>