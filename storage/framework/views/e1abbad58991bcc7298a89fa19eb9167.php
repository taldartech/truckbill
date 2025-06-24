<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add Bank Account</h2>
    <form action="<?php echo e(route('transporter_bank_accounts.store')); ?>" method="POST">
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
                <label for="bank_name" class="form-label">Bank Name *</label>
                <input type="text" class="form-control" id="bank_name" name="bank_name" required value="<?php echo e(old('bank_name')); ?>">
                <?php $__errorArgs = ['bank_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="account_number" class="form-label">Account Number *</label>
                <input type="text" class="form-control" id="account_number" name="account_number" required value="<?php echo e(old('account_number')); ?>">
                <?php $__errorArgs = ['account_number'];
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
                <label for="ifsc_code" class="form-label">IFSC Code *</label>
                <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" required value="<?php echo e(old('ifsc_code')); ?>">
                <?php $__errorArgs = ['ifsc_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="beneficiary_name" class="form-label">Beneficiary Name *</label>
                <input type="text" class="form-control" id="beneficiary_name" name="beneficiary_name" required value="<?php echo e(old('beneficiary_name')); ?>">
                <?php $__errorArgs = ['beneficiary_name'];
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
                <label for="bulk_payment_id" class="form-label">Bulk Payment ID</label>
                <input type="text" class="form-control" id="bulk_payment_id" name="bulk_payment_id" value="<?php echo e(old('bulk_payment_id')); ?>">
                <?php $__errorArgs = ['bulk_payment_id'];
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
                <a href="<?php echo e(route('transporter_bank_accounts.index')); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/transporter_bank_accounts/create.blade.php ENDPATH**/ ?>