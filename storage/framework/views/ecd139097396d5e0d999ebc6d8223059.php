<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Transporter Management</h2>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" action="<?php echo e(route('transporters.index')); ?>" class="row g-3 mb-3">
        <div class="col-md-2">
            <input type="text" name="name" class="form-control" placeholder="Name" value="<?php echo e(request('name')); ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="gst_number" class="form-control" placeholder="GST Number" value="<?php echo e(request('gst_number')); ?>">
        </div>
        <div class="col-md-2">
            <select name="gst_type" class="form-select">
                <option value="">All GST Types</option>
                <option value="composition" <?php echo e(request('gst_type') == 'composition' ? 'selected' : ''); ?>>Composition</option>
                <option value="regular" <?php echo e(request('gst_type') == 'regular' ? 'selected' : ''); ?>>Regular</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="pan_number" class="form-control" placeholder="PAN Number" value="<?php echo e(request('pan_number')); ?>">
        </div>
        <div class="col-md-2">
            <select name="is_owner" class="form-select">
                <option value="">All Types</option>
                <option value="1" <?php echo e(request('is_owner') === '1' ? 'selected' : ''); ?>>Owner</option>
                <option value="0" <?php echo e(request('is_owner') === '0' ? 'selected' : ''); ?>>Agency</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="company_id" class="form-select">
                <option value="">All Companies</option>
                <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($company->id); ?>" <?php echo e(request('company_id') == $company->id ? 'selected' : ''); ?>><?php echo e($company->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-12">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="<?php echo e(route('transporters.index')); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
            <a href="<?php echo e(route('transporters.create')); ?>" class="btn btn-primary ms-2">Add New Transporter</a>
        </div>
    </form>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>GST Number</th>
                <th>GST Type</th>
                <th>PAN Number</th>
                <th>Type</th>
                <th>Declaration Given</th>
                <th>Total Trucks</th>
                <th>Advance Amount</th>
                <th>Company</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($transporter->id); ?></td>
                    <td><?php echo e($transporter->name); ?></td>
                    <td><?php echo e($transporter->gst_number); ?></td>
                    <td><?php echo e(ucfirst($transporter->gst_type)); ?></td>
                    <td><?php echo e($transporter->pan_number); ?></td>
                    <td><?php echo e($transporter->is_owner ? 'Owner' : 'Agency'); ?></td>
                    <td><?php echo e($transporter->declaration_given ? 'Yes' : 'No'); ?></td>
                    <td><?php echo e($transporter->total_trucks); ?></td>
                    <td><?php echo e(number_format($transporter->advance_amount, 2)); ?></td>
                    <td><?php echo e($transporter->company ? $transporter->company->name : '-'); ?></td>
                    <td>
                        <a href="<?php echo e(route('transporters.edit', $transporter->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?php echo e(route('transporters.destroy', $transporter->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="11" class="text-center">No transporters found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/transporters/index.blade.php ENDPATH**/ ?>