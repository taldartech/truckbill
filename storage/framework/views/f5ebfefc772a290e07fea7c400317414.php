<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Consignors</h2>
        <a href="<?php echo e(route('consignors.create')); ?>" class="btn btn-primary">Add Consignor</a>
    </div>
    <?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
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
                        <?php $__currentLoopData = $consignors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consignor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($consignor->id); ?></td>
                                <td><?php echo e($consignor->name); ?></td>
                                <td><?php echo e($consignor->city); ?></td>
                                <td><?php echo e($consignor->state); ?></td>
                                <td><?php echo e($consignor->country); ?></td>
                                <td><?php echo e($consignor->contact_number); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo e(route('consignors.edit', $consignor)); ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form action="<?php echo e(route('consignors.destroy', $consignor)); ?>" method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                
            </div>
            <?php echo e($consignors->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/consignors/index.blade.php ENDPATH**/ ?>