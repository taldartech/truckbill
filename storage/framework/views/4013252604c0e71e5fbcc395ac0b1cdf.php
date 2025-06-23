<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add Freight Booking</h2>
    <form method="POST" action="<?php echo e(route('freight_bookings.store')); ?>">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="lr_id" class="form-label">LR *</label>
                <select name="lr_id" id="lr_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $lrs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($lr->id); ?>" <?php echo e(old('lr_id') == $lr->id ? 'selected' : ''); ?>><?php echo e($lr->gr_number); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['lr_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="tr_fr_rate" class="form-label">TR Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" name="tr_fr_rate" id="tr_fr_rate" value="<?php echo e(old('tr_fr_rate')); ?>" required>
                <?php $__errorArgs = ['tr_fr_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="tr_amount" class="form-label">TR Amount *</label>
                <input type="number" step="0.01" class="form-control" name="tr_amount" id="tr_amount" value="<?php echo e(old('tr_amount')); ?>" required>
                <?php $__errorArgs = ['tr_amount'];
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
                <label for="advance_amount" class="form-label">Advance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="advance_amount" id="advance_amount" value="<?php echo e(old('advance_amount')); ?>" required>
                <?php $__errorArgs = ['advance_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="balance_amount" class="form-label">Balance Amount *</label>
                <input type="number" step="0.01" class="form-control" name="balance_amount" id="balance_amount" value="<?php echo e(old('balance_amount')); ?>" required>
                <?php $__errorArgs = ['balance_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="tds_amount" class="form-label">TDS Amount</label>
                <input type="number" step="0.01" class="form-control" name="tds_amount" id="tds_amount" value="<?php echo e(old('tds_amount')); ?>">
                <?php $__errorArgs = ['tds_amount'];
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
                <label for="shortage" class="form-label">Shortage</label>
                <input type="number" step="0.01" class="form-control" name="shortage" id="shortage" value="<?php echo e(old('shortage')); ?>">
                <?php $__errorArgs = ['shortage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type</label>
                <input type="text" class="form-control" name="good_type" id="good_type" value="<?php echo e(old('good_type')); ?>">
                <?php $__errorArgs = ['good_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="detention" class="form-label">Detention</label>
                <input type="number" step="0.01" class="form-control" name="detention" id="detention" value="<?php echo e(old('detention')); ?>">
                <?php $__errorArgs = ['detention'];
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
                <label for="deviation" class="form-label">Deviation</label>
                <input type="number" step="0.01" class="form-control" name="deviation" id="deviation" value="<?php echo e(old('deviation')); ?>">
                <?php $__errorArgs = ['deviation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="misc_expense" class="form-label">Misc. Expense</label>
                <input type="number" step="0.01" class="form-control" name="misc_expense" id="misc_expense" value="<?php echo e(old('misc_expense')); ?>">
                <?php $__errorArgs = ['misc_expense'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="misc_income" class="form-label">Misc. Income</label>
                <input type="number" step="0.01" class="form-control" name="misc_income" id="misc_income" value="<?php echo e(old('misc_income')); ?>">
                <?php $__errorArgs = ['misc_income'];
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
            <div class="col-md-3 mb-3">
                <label for="is_delivered" class="form-label">Delivered?</label>
                <select name="is_delivered" id="is_delivered" class="form-select">
                    <option value="0" <?php echo e(old('is_delivered') == '0' ? 'selected' : ''); ?>>No</option>
                    <option value="1" <?php echo e(old('is_delivered') == '1' ? 'selected' : ''); ?>>Yes</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label for="is_cancelled" class="form-label">Cancelled?</label>
                <select name="is_cancelled" id="is_cancelled" class="form-select">
                    <option value="0" <?php echo e(old('is_cancelled') == '0' ? 'selected' : ''); ?>>No</option>
                    <option value="1" <?php echo e(old('is_cancelled') == '1' ? 'selected' : ''); ?>>Yes</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="linked_freight_booking_id" class="form-label">Linked Freight Booking</label>
                <select name="linked_freight_booking_id" id="linked_freight_booking_id" class="form-select">
                    <option value="">None</option>
                    <?php $__currentLoopData = $linkedBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($fb->id); ?>" <?php echo e(old('linked_freight_booking_id') == $fb->id ? 'selected' : ''); ?>>
                            #<?php echo e($fb->id); ?> (LR: <?php echo e($fb->lr ? $fb->lr->gr_number : '-'); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['linked_freight_booking_id'];
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
        <a href="<?php echo e(route('freight_bookings.index')); ?>" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/freight_bookings/create.blade.php ENDPATH**/ ?>