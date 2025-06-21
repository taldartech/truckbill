<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Edit Voucher</h2>
    <form method="POST" action="<?php echo e(route('vouchers.update', $voucher->id)); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="freight_booking_id" class="form-label">Freight Booking *</label>
                <select name="freight_booking_id" id="freight_booking_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $freightBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($fb->id); ?>" <?php echo e(old('freight_booking_id', $voucher->freight_booking_id) == $fb->id ? 'selected' : ''); ?>><?php echo e($fb->id); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['freight_booking_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="truck_id" class="form-label">Truck *</label>
                <select name="truck_id" id="truck_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $trucks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $truck): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($truck->id); ?>" <?php echo e(old('truck_id', $voucher->truck_id) == $truck->id ? 'selected' : ''); ?>><?php echo e($truck->truck_number); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['truck_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="transporter_id" class="form-label">Transporter *</label>
                <select name="transporter_id" id="transporter_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($transporter->id); ?>" <?php echo e(old('transporter_id', $voucher->transporter_id) == $transporter->id ? 'selected' : ''); ?>><?php echo e($transporter->name); ?></option>
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
            <div class="col-md-4 mb-3">
                <label for="pan_verified" class="form-label">PAN Verified?</label>
                <select name="pan_verified" id="pan_verified" class="form-select">
                    <option value="0" <?php echo e(old('pan_verified', $voucher->pan_verified) == '0' ? 'selected' : ''); ?>>No</option>
                    <option value="1" <?php echo e(old('pan_verified', $voucher->pan_verified) == '1' ? 'selected' : ''); ?>>Yes</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="status" class="form-label">Status *</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="pending" <?php echo e(old('status', $voucher->status) == 'pending' ? 'selected' : ''); ?>>Pending</option>
                    <option value="completed" <?php echo e(old('status', $voucher->status) == 'completed' ? 'selected' : ''); ?>>Completed</option>
                </select>
                <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="<?php echo e(route('vouchers.index')); ?>" class="btn btn-secondary ms-2">Back to List</a>
    </form>

<hr>
<h4 class="mt-5">Voucher Payments</h4>
<!-- Add Payment Form -->
<form method="POST" action="<?php echo e(route('voucher_payments.store')); ?>" class="border p-3 mb-4 bg-light">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="voucher_id" value="<?php echo e($voucher->id); ?>">
    <div class="row">
        <div class="col-md-2 mb-2">
            <input type="text" name="payment_type" class="form-control" placeholder="Payment Type" required value="<?php echo e(old('payment_type')); ?>">
        </div>
        <div class="col-md-2 mb-2">
            <input type="text" name="payment_mode" class="form-control" placeholder="Payment Mode" required value="<?php echo e(old('payment_mode')); ?>">
        </div>
        <div class="col-md-2 mb-2">
            <input type="number" name="amount" class="form-control" placeholder="Amount" step="0.01" min="0" required value="<?php echo e(old('amount')); ?>">
        </div>
        <div class="col-md-2 mb-2">
            <input type="text" name="transaction_id" class="form-control" placeholder="Transaction ID" required value="<?php echo e(old('transaction_id')); ?>">
        </div>
        <div class="col-md-2 mb-2">
            <select name="payment_status" class="form-select" required>
                <option value="pending">Pending</option>
                <option value="in-progress">In-Progress</option>
                <option value="paid">Paid</option>
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <input type="text" name="remark" class="form-control" placeholder="Remark" value="<?php echo e(old('remark')); ?>">
        </div>
        <div class="col-md-1 mb-2">
            <button type="submit" class="btn btn-primary w-100">Add</button>
        </div>
    </div>
    <?php if($errors->any()): ?>
        <div class="alert alert-danger mt-2 mb-0 p-2">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>
</form>
<div id="payments-section">
    <?php echo $__env->make('vouchers._payments_section', ['voucher' => $voucher], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>

        <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Mode</th>
            <th>Amount</th>
            <th>Transaction ID</th>
            <th>Status</th>
            <th>Remark</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $voucher->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td><?php echo e($payment->id); ?></td>
            <td><?php echo e($payment->payment_type); ?></td>
            <td><?php echo e($payment->payment_mode); ?></td>
            <td>&#8377;<?php echo e(number_format($payment->amount, 2)); ?></td>
            <td><?php echo e($payment->transaction_id); ?></td>
            <td><?php echo e(ucfirst($payment->payment_status)); ?></td>
            <td><?php echo e($payment->remark); ?></td>
            <td>
                <a href="<?php echo e(route('voucher_payments.edit', $payment->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                <form action="<?php echo e(route('voucher_payments.destroy', $payment->id)); ?>" method="POST" style="display:inline-block;">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="7" class="text-center">No payments found for this voucher.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php $__env->startSection('scripts'); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    $('form[action="<?php echo e(route('voucher_payments.store')); ?>"]').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();
        form.find('button[type=submit]').prop('disabled', true);
        $.post(url, data)
            .done(function() {
                // Reload the payments section (table + summary)
                // Reload only the payments section via AJAX
$.get("/vouchers/<?php echo e($voucher->id); ?>/payments-section", function(html) {
    $('#payments-section').html(html);
    form[0].reset();
});
            })
            .fail(function(xhr) {
                var errors = xhr.responseJSON.errors;
                var html = '<div class="alert alert-danger mt-2 mb-0 p-2"><ul class="mb-0">';
                $.each(errors, function(k, v) {
                    html += '<li>' + v[0] + '</li>';
                });
                html += '</ul></div>';
                form.find('.alert-danger').remove();
                form.append(html);
            })
            .always(function() {
                form.find('button[type=submit]').prop('disabled', false);
            });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/vouchers/edit.blade.php ENDPATH**/ ?>