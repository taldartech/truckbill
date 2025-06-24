<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Vouchers</h2>
    <a href="<?php echo e(route('vouchers.create')); ?>" class="btn btn-primary mb-3">Add Voucher</a>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All</option>
                <option value="pending" <?php echo e(request('status') == 'pending' ? 'selected' : ''); ?>>Pending</option>
                <option value="completed" <?php echo e(request('status') == 'completed' ? 'selected' : ''); ?>>Completed</option>
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
                <th>Freight Booking</th>
                <th>Truck</th>
                <th>Transporter</th>
                <th>PAN Verified</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($voucher->id); ?></td>
                <td><?php echo e($voucher->freightBooking ? $voucher->freightBooking->id : '-'); ?></td>
                <td><?php echo e($voucher->truck ? $voucher->truck->truck_number : '-'); ?></td>
                <td><?php echo e($voucher->transporter ? $voucher->transporter->name : '-'); ?></td>
                <td><?php echo e($voucher->pan_verified ? 'Yes' : 'No'); ?></td>
                <td><?php echo e(ucfirst($voucher->status)); ?></td>
                <td>
                    <a href="<?php echo e(route('vouchers.edit', $voucher->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="<?php echo e(route('vouchers.destroy', $voucher->id)); ?>" method="POST" style="display:inline-block;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this voucher?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7">No vouchers found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($vouchers->links()); ?>

    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/vouchers/index.blade.php ENDPATH**/ ?>