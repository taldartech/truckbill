<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Freight Bookings</h2>
    <a href="<?php echo e(route('freight_bookings.create')); ?>" class="btn btn-primary mb-3">Add Freight Booking</a>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label for="lr_id" class="form-label">LR</label>
            <select name="lr_id" id="lr_id" class="form-select">
                <option value="">All</option>
                <?php $__currentLoopData = $lrs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($lr->id); ?>" <?php echo e(request('lr_id') == $lr->id ? 'selected' : ''); ?>><?php echo e($lr->gr_number); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="is_delivered" class="form-label">Delivered?</label>
            <select name="is_delivered" id="is_delivered" class="form-select">
                <option value="">All</option>
                <option value="1" <?php echo e(request('is_delivered') === '1' ? 'selected' : ''); ?>>Yes</option>
                <option value="0" <?php echo e(request('is_delivered') === '0' ? 'selected' : ''); ?>>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="is_cancelled" class="form-label">Cancelled?</label>
            <select name="is_cancelled" id="is_cancelled" class="form-select">
                <option value="">All</option>
                <option value="1" <?php echo e(request('is_cancelled') === '1' ? 'selected' : ''); ?>>Yes</option>
                <option value="0" <?php echo e(request('is_cancelled') === '0' ? 'selected' : ''); ?>>No</option>
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
                <th>LR No</th>
                <th>TR Rate</th>
                <th>TR Amount</th>
                <th>Advance</th>
                <th>Balance</th>
                <th>Delivered?</th>
                <th>Cancelled?</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($booking->id); ?></td>
                <td><?php echo e($booking->lr ? $booking->lr->gr_number : '-'); ?></td>
                <td><?php echo e($booking->tr_fr_rate); ?></td>
                <td><?php echo e($booking->tr_amount); ?></td>
                <td><?php echo e($booking->advance_amount); ?></td>
                <td><?php echo e($booking->balance_amount); ?></td>
                <td><?php echo e($booking->is_delivered ? 'Yes' : 'No'); ?></td>
                <td><?php echo e($booking->is_cancelled ? 'Yes' : 'No'); ?></td>
                <td>
                    <a href="<?php echo e(route('freight_bookings.edit', $booking->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="<?php echo e(route('freight_bookings.destroy', $booking->id)); ?>" method="POST" style="display:inline-block;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this booking?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="9">No bookings found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($bookings->links()); ?>

    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/freight_bookings/index.blade.php ENDPATH**/ ?>