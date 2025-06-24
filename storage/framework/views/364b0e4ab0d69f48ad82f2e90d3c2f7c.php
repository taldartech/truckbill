<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Customers</h2>
    <a href="<?php echo e(route('customers.create')); ?>" class="btn btn-primary mb-3">Add Customer</a>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>GST</th>
                <th>PAN</th>
                <th>Address</th>
                <th>Contact Person</th>
                <th>Contact Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($customer->id); ?></td>
                <td><?php echo e($customer->name); ?></td>
                <td><?php echo e($customer->gst_number); ?></td>
                <td><?php echo e($customer->pan_number); ?></td>
                <td><?php echo e($customer->address); ?></td>
                <td><?php echo e($customer->contact_person); ?></td>
                <td><?php echo e($customer->contact_number); ?></td>
                <td>
                    <a href="<?php echo e(route('customers.edit', $customer->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="<?php echo e(route('customers.destroy', $customer->id)); ?>" method="POST" style="display:inline-block;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="8" class="text-center">No customers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($customers->links()); ?>

    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/customers/index.blade.php ENDPATH**/ ?>