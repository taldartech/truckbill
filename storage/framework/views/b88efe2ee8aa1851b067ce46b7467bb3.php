<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Transporter Bank Accounts</h2>
    <a href="<?php echo e(route('transporter_bank_accounts.create')); ?>" class="btn btn-primary mb-3">Add Bank Account</a>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Transporter</th>
                        <th>Bank Name</th>
                        <th>Account Number</th>
                        <th>IFSC Code</th>
                        <th>Beneficiary Name</th>
                        <th>Bulk Payment ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($account->id); ?></td>
                            <td><?php echo e($account->transporter->name); ?></td>
                            <td><?php echo e($account->bank_name); ?></td>
                            <td><?php echo e($account->account_number); ?></td>
                            <td><?php echo e($account->ifsc_code); ?></td>
                            <td><?php echo e($account->beneficiary_name); ?></td>
                            <td><?php echo e($account->bulk_payment_id); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo e(route('transporter_bank_accounts.edit', $account)); ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="<?php echo e(route('transporter_bank_accounts.destroy', $account)); ?>" method="POST" style="display:inline-block;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8">No bank accounts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php echo e($accounts->links()); ?>

    
    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/transporter_bank_accounts/index.blade.php ENDPATH**/ ?>