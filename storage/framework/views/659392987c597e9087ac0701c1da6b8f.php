<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add New Truck</h2>
    <form action="<?php echo e(route('trucks.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="mb-3">
            <label for="transporter_id" class="form-label">Transporter *</label>
            <select class="form-select" id="transporter_id" name="transporter_id" required>
                <option value="">Select</option>
                <?php $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($transporter->id); ?>" <?php echo e(old('transporter_id') == $transporter->id ? 'selected' : ''); ?>><?php echo e($transporter->name); ?></option>
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
        <div class="mb-3">
            <label for="truck_number" class="form-label">Truck Number *</label>
            <input type="text" class="form-control" id="truck_number" name="truck_number" required value="<?php echo e(old('truck_number')); ?>">
            <?php $__errorArgs = ['truck_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="mb-3">
    <label for="truck_type" class="form-label">Truck Type *</label>
    <select class="form-select" id="truck_type" name="truck_type" required>
        <option value="">Select</option>
        <option value="Open" <?php echo e(old('truck_type') == 'Open' ? 'selected' : ''); ?>>Open</option>
        <option value="Closed" <?php echo e(old('truck_type') == 'Closed' ? 'selected' : ''); ?>>Closed</option>
        <option value="Container" <?php echo e(old('truck_type') == 'Container' ? 'selected' : ''); ?>>Container</option>
        <option value="Trailer" <?php echo e(old('truck_type') == 'Trailer' ? 'selected' : ''); ?>>Trailer</option>
        <option value="Tanker" <?php echo e(old('truck_type') == 'Tanker' ? 'selected' : ''); ?>>Tanker</option>
        <option value="Refrigerated" <?php echo e(old('truck_type') == 'Refrigerated' ? 'selected' : ''); ?>>Refrigerated</option>
        <option value="Flatbed" <?php echo e(old('truck_type') == 'Flatbed' ? 'selected' : ''); ?>>Flatbed</option>
        <option value="Tipper" <?php echo e(old('truck_type') == 'Tipper' ? 'selected' : ''); ?>>Tipper</option>
        <option value="Other" <?php echo e(old('truck_type') == 'Other' ? 'selected' : ''); ?>>Other</option>
    </select>
    <?php $__errorArgs = ['truck_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
        <div class="mb-3">
    <label for="driver_id" class="form-label">Driver</label>
    <div class="input-group">
        <select class="form-select" id="driver_id" name="driver_id">
            <option value="">Select</option>
            <?php $__currentLoopData = $drivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($driver->id); ?>" <?php echo e(old('driver_id') == $driver->id ? 'selected' : ''); ?>><?php echo e($driver->name); ?> (<?php echo e($driver->mobile); ?>)</option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <a href="<?php echo e(route('drivers.create')); ?>" class="btn btn-outline-secondary" target="_blank">Add New Driver</a>
    </div>
    <?php $__errorArgs = ['driver_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
        <div class="mb-3">
            <label for="registration_date" class="form-label">Registration Date *</label>
            <input type="date" class="form-control" id="registration_date" name="registration_date" required value="<?php echo e(old('registration_date')); ?>">
            <?php $__errorArgs = ['registration_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <button type="submit" class="btn btn-primary">Create Truck</button>
        <a href="<?php echo e(route('trucks.index')); ?>" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/trucks/create.blade.php ENDPATH**/ ?>