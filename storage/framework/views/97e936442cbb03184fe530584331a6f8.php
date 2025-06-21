<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Lorry Receipts</h2>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" action="<?php echo e(route('lorry_receipts.index')); ?>" class="row g-3 mb-3">
        <div class="col-md-2">
            <input type="text" name="gr_number" class="form-control" placeholder="GR Number" value="<?php echo e(request('gr_number')); ?>">
        </div>
        <div class="col-md-2">
            <select name="truck_id" class="form-select">
                <option value="">All Trucks</option>
                <?php $__currentLoopData = $trucks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $truck): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($truck->id); ?>" <?php echo e(request('truck_id') == $truck->id ? 'selected' : ''); ?>><?php echo e($truck->truck_number); ?></option>
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
            <select name="delivery_status" class="form-select">
                <option value="">All Statuses</option>
                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($status); ?>" <?php echo e(request('delivery_status') == $status ? 'selected' : ''); ?>><?php echo e($status); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="gr_date_from" class="form-control" value="<?php echo e(request('gr_date_from')); ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="gr_date_to" class="form-control" value="<?php echo e(request('gr_date_to')); ?>">
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="<?php echo e(route('lorry_receipts.index')); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="<?php echo e(route('lorry_receipts.create')); ?>" class="btn btn-primary ms-2">Add New LR</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>GR Number</th>
                <th>Truck</th>
                <th>Transporter</th>
                <th>Consignor</th>
                <th>Consignee</th>
                <th>Status</th>
                <th>GR Date</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $lrs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($lr->id); ?></td>
                    <td><?php echo e($lr->gr_number); ?></td>
                    <td><?php echo e($lr->truck ? $lr->truck->truck_number : '-'); ?></td>
                    <td><?php echo e($lr->transporter ? $lr->transporter->name : '-'); ?></td>
                    <td><?php echo e($lr->consignor_id); ?></td>
                    <td><?php echo e($lr->consignee_id); ?></td>
                    <td><?php echo e($lr->delivery_status); ?></td>
                    <td><?php echo e($lr->gr_date); ?></td>
                    <td><?php echo e($lr->amount); ?></td>
                    <td>
                        <a href="<?php echo e(route('lorry_receipts.edit', $lr->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?php echo e(route('lorry_receipts.destroy', $lr->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="10" class="text-center">No Lorry Receipts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/lorry_receipts/index.blade.php ENDPATH**/ ?>