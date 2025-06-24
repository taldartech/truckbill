<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add Ledger Entry</h2>
    <form action="<?php echo e(route('transporter_ledger.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select class="form-select" id="transporter_id" name="transporter_id" required>
                    <option value="">Select Transporter</option>
                    <?php $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($transporter->id); ?>" <?php echo e(old('transporter_id') == $transporter->id ? 'selected' : ''); ?>>
                            <?php echo e($transporter->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['transporter_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="amount" class="form-label">Amount *</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required value="<?php echo e(old('amount')); ?>">
                </div>
                <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="transaction_type" class="form-label">Transaction Type *</label>
                <select class="form-select" id="transaction_type" name="transaction_type" required>
                    <option value="">Select Type</option>
                    <option value="debit" <?php echo e(old('transaction_type') == 'debit' ? 'selected' : ''); ?>>Debit</option>
                    <option value="credit" <?php echo e(old('transaction_type') == 'credit' ? 'selected' : ''); ?>>Credit</option>
                </select>
                <?php $__errorArgs = ['transaction_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="remark" class="form-label">Remark</label>
                <textarea class="form-control" id="remark" name="remark" rows="3"><?php echo e(old('remark')); ?></textarea>
                <?php $__errorArgs = ['remark'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo e(route('transporter_ledger.index')); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/transporter_ledger/create.blade.php ENDPATH**/ ?>