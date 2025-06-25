<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Transporter Ledger</h2>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?php echo e(route('transporter_ledger.create')); ?>" class="btn btn-primary">Add Ledger Entry</a>
        <div class="d-flex gap-2">
            <select id="transporterFilter" class="form-select">
                <option value="">All Transporters</option>
                <?php $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($transporter->id); ?>"><?php echo e($transporter->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <div class="balance-display">
                <span class="badge bg-info">Balance: <span id="balanceAmount">0.00</span></span>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Transporter</th>
                        <th>Amount</th>
                        <th>Transaction Type</th>
                        <th>Remark</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $ledgers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ledger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($ledger->id); ?></td>
                            <td><?php echo e($ledger->transporter->name); ?></td>
                            <td class="<?php echo e($ledger->transaction_type === 'debit' ? 'text-success' : 'text-danger'); ?>">
                                <?php echo e($ledger->transaction_type === 'debit' ? '+' : '-'); ?><?php echo e($ledger->formatted_amount); ?>

                            </td>
                            <td><?php echo e($ledger->transaction_type); ?></td>
                            <td><?php echo e($ledger->remark); ?></td>
                            <td><?php echo e($ledger->created_at->format('Y-m-d H:i')); ?></td>
                            <td>
                                
                                    <a href="<?php echo e(route('transporter_ledger.edit', $ledger)); ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="<?php echo e(route('transporter_ledger.destroy', $ledger)); ?>" method="POST" style="display:inline-block;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8">No ledger entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php echo e($ledgers->links()); ?>

        </div>
    </div>
    <div class="mt-3">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const transporterFilter = document.getElementById('transporterFilter');
    const balanceAmount = document.getElementById('balanceAmount');
    const tableBody = document.querySelector('tbody');

    function updateBalance(transporterId) {
        fetch(`/transporter_ledger/balance/${transporterId}`)
            .then(response => response.json())
            .then(data => {
                balanceAmount.textContent = data.formatted_balance;
            });
    }

    transporterFilter.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('transporter_id', this.value);
        window.location.href = url.toString();
    });

    // Initial balance update
    updateBalance(transporterFilter.value);
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/transporter_ledger/index.blade.php ENDPATH**/ ?>