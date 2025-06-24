<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add Voucher Payment</h2>
    <form method="POST" action="<?php echo e(route('voucher_payments.store')); ?>">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="voucher_id" class="form-label">Voucher *</label>
                <select name="voucher_id" id="voucher_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($voucher->id); ?>" <?php echo e(old('voucher_id') == $voucher->id ? 'selected' : ''); ?>><?php echo e($voucher->id); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['voucher_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="payment_type" class="form-label">Payment Type *</label>
                <select name="payment_type" id="payment_type" class="form-select" required>
                    <option value="">Select Payment Type</option>
                    <?php $__currentLoopData = $paymentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($paymentType->name); ?>" <?php echo e(old('payment_type') == $paymentType->name ? 'selected' : ''); ?>>
                            <?php echo e($paymentType->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['payment_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="payment_mode" class="form-label">Payment Mode *</label>
                <select name="payment_mode" id="payment_mode" class="form-select" required>
                    <option value="">Select Payment Mode</option>
                    <?php $__currentLoopData = $paymentModes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentMode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($paymentMode->name); ?>" <?php echo e(old('payment_mode') == $paymentMode->name ? 'selected' : ''); ?>>
                            <?php echo e($paymentMode->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['payment_mode'];
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
            <div class="col-md-4 mb-3">
                <label for="transaction_id" class="form-label">Transaction ID *</label>
                <input type="text" class="form-control" name="transaction_id" id="transaction_id" value="<?php echo e(old('transaction_id')); ?>" required>
                <?php $__errorArgs = ['transaction_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="remark" class="form-label">Remark</label>
                <textarea name="remark" id="remark" class="form-control"><?php echo e(old('remark')); ?></textarea>
                <?php $__errorArgs = ['remark'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="payment_status" class="form-label">Payment Status *</label>
                <select name="payment_status" id="payment_status" class="form-select" required>
                    <option value="pending" <?php echo e(old('payment_status') == 'pending' ? 'selected' : ''); ?>>Pending</option>
                    <option value="in-progress" <?php echo e(old('payment_status') == 'in-progress' ? 'selected' : ''); ?>>In-Progress</option>
                    <option value="paid" <?php echo e(old('payment_status') == 'paid' ? 'selected' : ''); ?>>Paid</option>
                </select>
                <?php $__errorArgs = ['payment_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="<?php echo e(route('voucher_payments.index')); ?>" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/voucher_payments/create.blade.php ENDPATH**/ ?>