<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Consignees</h2>
    <a href="<?php echo e(route('consignees.create')); ?>" class="btn btn-primary mb-3">Add Consignee</a>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>City</th>
                <th>State</th>
                <th>Country</th>
                <th>Contact Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $consignees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consignee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($consignee->id); ?></td>
                <td><?php echo e($consignee->name); ?></td>
                <td><?php echo e($consignee->city); ?></td>
                <td><?php echo e($consignee->state); ?></td>
                <td><?php echo e($consignee->country); ?></td>
                <td><?php echo e($consignee->contact_number); ?></td>
                <td>
                    
                        <a href="<?php echo e(route('consignees.edit', $consignee->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?php echo e(route('consignees.destroy', $consignee->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="text-center">No consignees found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php echo e($consignees->links()); ?>

    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/consignees/index.blade.php ENDPATH**/ ?>