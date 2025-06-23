<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Driver Management</h2>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" action="<?php echo e(route('drivers.index')); ?>" class="row g-3 mb-3">
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Name" value="<?php echo e(request('name')); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="mobile" class="form-control" placeholder="Mobile" value="<?php echo e(request('mobile')); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="license_number" class="form-control" placeholder="License Number" value="<?php echo e(request('license_number')); ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="<?php echo e(route('drivers.index')); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="<?php echo e(route('drivers.create')); ?>" class="btn btn-primary ms-2">Add New Driver</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>License Number</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $drivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($driver->id); ?></td>
                    <td><?php echo e($driver->name); ?></td>
                    <td><?php echo e($driver->mobile); ?></td>
                    <td><?php echo e($driver->license_number); ?></td>
                    <td><?php echo e($driver->address); ?></td>
                    <td>
                        <a href="<?php echo e(route('drivers.edit', $driver->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?php echo e(route('drivers.destroy', $driver->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="text-center">No drivers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/drivers/index.blade.php ENDPATH**/ ?>