<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Registered Companies</h2>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" action="<?php echo e(route('company.index')); ?>" class="row g-3 mb-3">
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Company Name" value="<?php echo e(request('name')); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="gst_number" class="form-control" placeholder="GST Number" value="<?php echo e(request('gst_number')); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="pan_number" class="form-control" placeholder="PAN Number" value="<?php echo e(request('pan_number')); ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="<?php echo e(route('company.index')); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
        </div>
    </form>
    <a href="<?php echo e(route('company.create')); ?>" class="btn btn-primary mb-3">Add New Company</a>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>GST Number</th>
                <th>PAN Number</th>
                <th>GST Scheme</th>
                <th>GST Type</th>
                <th>Address</th>
                <th>Contact Person</th>
                <th>Contact Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($company->id); ?></td>
                    <td><?php echo e($company->name); ?></td>
                    <td><?php echo e($company->gst_number); ?></td>
                    <td><?php echo e($company->pan_number); ?></td>
                    <td><?php echo e($company->gst_scheme); ?></td>
                    <td><?php echo e($company->gst_type); ?></td>
                    <td><?php echo e($company->address); ?></td>
                    <td><?php echo e($company->contact_person); ?></td>
                    <td><?php echo e($company->contact_number); ?></td>
                    <td>
                        <a href="<?php echo e(route('company.edit', $company->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?php echo e(route('company.destroy', $company->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="10" class="text-center">No companies found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/company/index.blade.php ENDPATH**/ ?>