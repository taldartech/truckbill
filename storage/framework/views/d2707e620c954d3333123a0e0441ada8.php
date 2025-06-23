<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Consignees</h2>
            
        </div>
        <a href="<?php echo e(route('consignees.create')); ?>" class="btn btn-primary">Add Consignee</a>
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
                        <?php $__currentLoopData = $consignees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consignee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($consignee->id); ?></td>
                                <td><?php echo e($consignee->name); ?></td>
                                <td><?php echo e($consignee->city); ?></td>
                                <td><?php echo e($consignee->state); ?></td>
                                <td><?php echo e($consignee->country); ?></td>
                                <td><?php echo e($consignee->contact_number); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo e(route('consignees.edit', $consignee)); ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form action="<?php echo e(route('consignees.destroy', $consignee)); ?>" method="POST" class="d-inline">
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
            <?php echo e($consignees->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/consignees/index.blade.php ENDPATH**/ ?>