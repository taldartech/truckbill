<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add Invoice</h2>
    <form method="POST" action="<?php echo e(route('invoices.store')); ?>">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Manual Invoice #</label>
                <input type="text" name="manual_invoice_number" class="form-control" value="<?php echo e(old('manual_invoice_number')); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Company *</label>
                <select name="company_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($company->id); ?>" <?php echo e(old('company_id') == $company->id ? 'selected' : ''); ?>><?php echo e($company->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Invoice Type *</label>
                <select name="invoice_type" class="form-select" required>
                    <option value="Forward" <?php echo e(old('invoice_type') == 'Forward' ? 'selected' : ''); ?>>Forward</option>
                    <option value="Reverse" <?php echo e(old('invoice_type') == 'Reverse' ? 'selected' : ''); ?>>Reverse</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Generated Date *</label>
                <input type="date" name="generated_date" class="form-control" required value="<?php echo e(old('generated_date')); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Customer *</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($customer->id); ?>" <?php echo e(old('customer_id') == $customer->id ? 'selected' : ''); ?>><?php echo e($customer->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Total Amount *</label>
                <input type="number" name="total_amount" class="form-control" step="0.01" min="0" required value="<?php echo e(old('total_amount')); ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Payment Status *</label>
                <select name="payment_status" class="form-select" required>
                    <option value="pending" <?php echo e(old('payment_status') == 'pending' ? 'selected' : ''); ?>>Pending</option>
                    <option value="partial" <?php echo e(old('payment_status') == 'partial' ? 'selected' : ''); ?>>Partial</option>
                    <option value="paid" <?php echo e(old('payment_status') == 'paid' ? 'selected' : ''); ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-8 mb-3">
                <label class="form-label">Memos</label>
                <select name="memo_ids[]" class="form-select" multiple>
                    <?php $__currentLoopData = $memos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $memo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($memo->id); ?>" <?php echo e((collect(old('memo_ids'))->contains($memo->id)) ? 'selected' : ''); ?>><?php echo e($memo->memo_number); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple.</small>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="<?php echo e(route('invoices.index')); ?>" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/invoices/create.blade.php ENDPATH**/ ?>