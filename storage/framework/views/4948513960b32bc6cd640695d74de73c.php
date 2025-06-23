<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Truck Report</h2>
    <form method="GET" action="<?php echo e(route('trucks.report')); ?>" class="row g-3 mb-3">
        <div class="col-md-2">
            <select name="truck_type" class="form-select">
                <option value="">All Types</option>
                <?php $__currentLoopData = $truckTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($type); ?>" <?php echo e(request('truck_type') == $type ? 'selected' : ''); ?>><?php echo e($type); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="transporter_id" class="form-select">
                <option value="">All Transporters</option>
                <?php $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($transporter->id); ?>" <?php echo e(request('transporter_id') == $transporter->id ? 'selected' : ''); ?>><?php echo e($transporter->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="driver_id" class="form-select">
                <option value="">All Drivers</option>
                <?php $__currentLoopData = $drivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($driver->id); ?>" <?php echo e(request('driver_id') == $driver->id ? 'selected' : ''); ?>><?php echo e($driver->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="registration_date_from" class="form-control" placeholder="From Date" value="<?php echo e(request('registration_date_from')); ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="registration_date_to" class="form-control" placeholder="To Date" value="<?php echo e(request('registration_date_to')); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?php echo e(route('trucks.report')); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
        </div>
    </form>
    <div class="mb-3">
        <strong>Total Trucks: <?php echo e($total); ?></strong>
    </div>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Truck Number</th>
                <th>Truck Type</th>
                <th>Transporter</th>
                <th>Driver</th>
                <th>Registration Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $trucks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $truck): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($truck->id); ?></td>
                    <td><?php echo e($truck->truck_number); ?></td>
                    <td><?php echo e($truck->truck_type); ?></td>
                    <td><?php echo e($truck->transporter ? $truck->transporter->name : '-'); ?></td>
                    <td><?php echo e($truck->driver ? $truck->driver->name : '-'); ?></td>
                    <td><?php echo e($truck->registration_date); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="text-center">No trucks found for the selected filters.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/trucks/report.blade.php ENDPATH**/ ?>