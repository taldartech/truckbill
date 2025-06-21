<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Truck Details</h2>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" action="<?php echo e(route('trucks.index')); ?>" class="row g-3 mb-3">
        <div class="col-md-3">
            <input type="text" name="truck_number" class="form-control" placeholder="Truck Number" value="<?php echo e(request('truck_number')); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="truck_type" class="form-control" placeholder="Truck Type" value="<?php echo e(request('truck_type')); ?>">
        </div>
        <div class="col-md-3">
            <select name="driver_id" class="form-select">
                <option value="">All Drivers</option>
                <?php $__currentLoopData = $drivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($driver->id); ?>" <?php echo e(request('driver_id') == $driver->id ? 'selected' : ''); ?>><?php echo e($driver->name); ?> (<?php echo e($driver->mobile); ?>)</option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="transporter_id" class="form-select">
                <option value="">All Transporters</option>
                <?php $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($transporter->id); ?>" <?php echo e(request('transporter_id') == $transporter->id ? 'selected' : ''); ?>><?php echo e($transporter->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="<?php echo e(route('trucks.index')); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="<?php echo e(route('trucks.create')); ?>" class="btn btn-primary ms-2">Add New Truck</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Truck Number</th>
                <th>Truck Type</th>
                <th>Driver Name</th>
                <th>Registration Date</th>
                <th>Transporter</th>
                <th>Driver</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $trucks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $truck): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($truck->id); ?></td>
                    <td><?php echo e($truck->truck_number); ?></td>
                    <td><?php echo e($truck->truck_type); ?></td>
                    <td><?php echo e($truck->driver_name); ?></td>
                    <td><?php echo e($truck->registration_date); ?></td>
                    <td><?php echo e($truck->transporter ? $truck->transporter->name : '-'); ?></td>
                    <td><?php echo e($truck->driver ? $truck->driver->name : '-'); ?></td>
                    <td>
                        <a href="<?php echo e(route('trucks.edit', $truck->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?php echo e(route('trucks.destroy', $truck->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="text-center">No trucks found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/trucks/index.blade.php ENDPATH**/ ?>