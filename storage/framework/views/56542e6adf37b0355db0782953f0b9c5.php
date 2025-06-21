<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add New Transporter</h2>
    <form action="<?php echo e(route('transporters.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="mb-3">
            <label for="company_id" class="form-label">Company *</label>
            <select class="form-select" id="company_id" name="company_id" required>
                <option value="">Select</option>
                <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($company->id); ?>" <?php echo e(old('company_id') == $company->id ? 'selected' : ''); ?>><?php echo e($company->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['company_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="name" name="name" required value="<?php echo e(old('name')); ?>">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label for="gst_number" class="form-label">GST Number</label>
            <input type="text" class="form-control" id="gst_number" name="gst_number" value="<?php echo e(old('gst_number')); ?>">
            <?php $__errorArgs = ['gst_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label for="gst_type" class="form-label">GST Type *</label>
            <select class="form-select" id="gst_type" name="gst_type" required>
                <option value="">Select</option>
                <option value="composition" <?php echo e(old('gst_type') == 'composition' ? 'selected' : ''); ?>>Composition</option>
                <option value="regular" <?php echo e(old('gst_type') == 'regular' ? 'selected' : ''); ?>>Regular</option>
            </select>
            <?php $__errorArgs = ['gst_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label for="pan_number" class="form-label">PAN Number</label>
            <input type="text" class="form-control" id="pan_number" name="pan_number" value="<?php echo e(old('pan_number')); ?>">
            <?php $__errorArgs = ['pan_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Type *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="owner" value="1" <?php echo e(old('is_owner') === '1' ? 'checked' : ''); ?> required>
                <label class="form-check-label" for="owner">Owner</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="is_owner" id="agency" value="0" <?php echo e(old('is_owner') === '0' ? 'checked' : ''); ?> required>
                <label class="form-check-label" for="agency">Agency</label>
            </div>
            <?php $__errorArgs = ['is_owner'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Declaration Given? *</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_yes" value="1" <?php echo e(old('declaration_given') === '1' ? 'checked' : ''); ?> required>
                <label class="form-check-label" for="decl_yes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="declaration_given" id="decl_no" value="0" <?php echo e(old('declaration_given') === '0' ? 'checked' : ''); ?> required>
                <label class="form-check-label" for="decl_no">No</label>
            </div>
            <?php $__errorArgs = ['declaration_given'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label for="total_trucks" class="form-label">Total Trucks *</label>
            <input type="number" class="form-control" id="total_trucks" name="total_trucks" min="0" required value="<?php echo e(old('total_trucks', 0)); ?>">
            <?php $__errorArgs = ['total_trucks'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
            <label for="advance_amount" class="form-label">Advance Amount</label>
            <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="<?php echo e(old('advance_amount', 0)); ?>">
            <?php $__errorArgs = ['advance_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <button type="submit" class="btn btn-primary">Create Transporter</button>
        <a href="<?php echo e(route('transporters.index')); ?>" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/transporters/create.blade.php ENDPATH**/ ?>