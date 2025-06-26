<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>Add New Lorry Receipt</h2>
    <form action="<?php echo e(route('lorry_receipts.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="truck_id" class="form-label">Truck *</label>
                <select class="form-select" id="truck_id" name="truck_id" disabled>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $trucks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $truck): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($truck->id); ?>" <?php echo e(old('truck_id') == $truck->id ? 'selected' : ''); ?>><?php echo e($truck->truck_number); ?></option>
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
                <label for="consignor_id" class="form-label">Consignor *</label>
                <select class="form-select" id="consignor_id" name="consignor_id" required>
                    <option value="">Select Consignor</option>
                    <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($customer->id); ?>" <?php echo e(old('consignor_id') == $customer->id ? 'selected' : ''); ?>>
                            <?php echo e($customer->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="consignor_contact_number" class="form-label">Consignor Contact Number</label>
                <input type="text" class="form-control" id="consignor_contact_number" name="consignor_contact_number" readonly>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="consignee_id" class="form-label">Consignee *</label>
                <select class="form-select" id="consignee_id" name="consignee_id" required>
                    <option value="">Select Consignee</option>
                    <?php $__currentLoopData = $consignees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consignee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($consignee->id); ?>" <?php echo e(old('consignee_id') == $consignee->id ? 'selected' : ''); ?>>
                            <?php echo e($consignee->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="consignee_contact_number" class="form-label">Consignee Contact Number</label>
                <input type="text" class="form-control" id="consignee_contact_number" name="consignee_contact_number" readonly>
            </div>
            <div class="col-md-4 mb-3">
                <label for="delivery_status" class="form-label">Delivery Status *</label>
                <select class="form-select" id="delivery_status" name="delivery_status" required>
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($status); ?>" <?php echo e(old('delivery_status') == $status ? 'selected' : ''); ?>><?php echo e($status); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['delivery_status'];
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
                <label for="customer_id" class="form-label">Customer *</label>
                <select class="form-select" id="customer_id" name="customer_id" required>
                    <option value="">Select</option>
                    <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($customer->id); ?>" <?php echo e(old('customer_id') == $customer->id ? 'selected' : ''); ?>><?php echo e($customer->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['customer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="gr_date" class="form-label">GR Date *</label>
                <input type="date" class="form-control" id="gr_date" name="gr_date" required value="<?php echo e(old('gr_date')); ?>">
                <?php $__errorArgs = ['gr_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="source_city" class="form-label">Source City *</label>
                <input type="text" class="form-control" id="source_city" name="source_city" required value="<?php echo e(old('source_city')); ?>">
                <?php $__errorArgs = ['source_city'];
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
                <label for="destination_city" class="form-label">Destination City *</label>
                <input type="text" class="form-control" id="destination_city" name="destination_city" required value="<?php echo e(old('destination_city')); ?>">
                <?php $__errorArgs = ['destination_city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="source_state" class="form-label">Source State *</label>
                <input type="text" class="form-control" id="source_state" name="source_state" required value="<?php echo e(old('source_state')); ?>">
                <?php $__errorArgs = ['source_state'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="destination_state" class="form-label">Destination State *</label>
                <input type="text" class="form-control" id="destination_state" name="destination_state" required value="<?php echo e(old('destination_state')); ?>">
                <?php $__errorArgs = ['destination_state'];
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
                <label for="lane" class="form-label">Lane *</label>
                <input type="text" class="form-control" id="lane" name="lane" value="Road" readonly required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="good_type" class="form-label">Goods Type *</label>
                <select class="form-select" id="good_type" name="good_type" required>
                    <option value="">Select Goods Type</option>
                    <?php $__currentLoopData = $goodsTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $goodsType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($goodsType->name); ?>" <?php echo e(old('good_type') == $goodsType->name ? 'selected' : ''); ?>>
                            <?php echo e($goodsType->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
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
                <label for="weight" class="form-label">Weight *</label>
                <input type="number" step="0.01" class="form-control" id="weight" name="weight" required value="<?php echo e(old('weight')); ?>">
                <?php $__errorArgs = ['weight'];
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
                <label for="no_of_units" class="form-label">No. of Units *</label>
                <input type="number" class="form-control" id="no_of_units" name="no_of_units" required value="<?php echo e(old('no_of_units')); ?>">
                <?php $__errorArgs = ['no_of_units'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="fr_rate" class="form-label">Freight Rate *</label>
                <input type="number" step="0.01" class="form-control" id="fr_rate" name="fr_rate" required value="<?php echo e(old('fr_rate')); ?>">
                <?php $__errorArgs = ['fr_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="amount" class="form-label">Amount *</label>
                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required value="<?php echo e(old('amount')); ?>">
                <?php $__errorArgs = ['amount'];
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
                <label for="gr_number" class="form-label">GR Number *</label>
                <input type="text" class="form-control" id="gr_number" name="gr_number" required value="<?php echo e(old('gr_number')); ?>">
                <?php $__errorArgs = ['gr_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_qty" class="form-label">Damage Quantity</label>
                <input type="number" step="0.01" class="form-control" id="damage_qty" name="damage_qty" value="<?php echo e(old('damage_qty')); ?>" disabled>
                <?php $__errorArgs = ['damage_qty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="damage_rate" class="form-label">Damage Rate</label>
                <input type="number" step="0.01" class="form-control" id="damage_rate" name="damage_rate" value="<?php echo e(old('damage_rate')); ?>" disabled>
                <?php $__errorArgs = ['damage_rate'];
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
                <label for="shortage_qty" class="form-label">Shortage Quantity</label>
                <input type="number" step="0.01" class="form-control" id="shortage_qty" name="shortage_qty" value="<?php echo e(old('shortage_qty')); ?>" disabled>
                <?php $__errorArgs = ['shortage_qty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="shortage_rate" class="form-label">Shortage Rate</label>
                <input type="number" step="0.01" class="form-control" id="shortage_rate" name="shortage_rate" value="<?php echo e(old('shortage_rate')); ?>" disabled>
                <?php $__errorArgs = ['shortage_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="service_tax" class="form-label">Service Tax</label>
                <input type="number" step="0.01" class="form-control" id="service_tax" name="service_tax" value="<?php echo e(old('service_tax')); ?>" disabled>
                <?php $__errorArgs = ['service_tax'];
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
                <label for="swachh_bharat_cess" class="form-label">Swachh Bharat Cess</label>
                <input type="number" step="0.01" class="form-control" id="swachh_bharat_cess" name="swachh_bharat_cess" value="<?php echo e(old('swachh_bharat_cess')); ?>" disabled>
                <?php $__errorArgs = ['swachh_bharat_cess'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="lr_number" class="form-label">LR Number *</label>
                <input type="text" class="form-control" id="lr_number" name="lr_number" required value="<?php echo e(old('lr_number')); ?>">
                <?php $__errorArgs = ['lr_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="memo_id" class="form-label">Memo ID</label>
                <input type="number" class="form-control" id="memo_id" name="memo_id" value="<?php echo e(old('memo_id')); ?>">
                <?php $__errorArgs = ['memo_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="text-danger"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Create LR</button>
        <a href="<?php echo e(route('lorry_receipts.index')); ?>" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const consignorSelect = document.getElementById('consignor_id');
        const consignorContact = document.getElementById('consignor_contact_number');
        const sourceCity = document.getElementById('source_city');
        const sourceState = document.getElementById('source_state');
    
        consignorSelect.addEventListener('change', function () {
            const consignorId = this.value;
    
            if (!consignorId) {
                consignorContact.value = '';
                sourceCity.value = '';
                sourceState.value = '';
                return;
            }
    
            fetch(`/customers/details/${consignorId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to fetch consignor details');
                return response.json();
            })
            .then(data => {
                consignorContact.value = data.contact_number || '';
                sourceCity.value = data.source_city || '';
                sourceState.value = data.source_state || '';
            })
            .catch(error => {
                console.error('❌ Error:', error);
                consignorContact.value = '';
                sourceCity.value = '';
                sourceState.value = '';
            });
        });
    });
    </script>
    
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const consigneeSelect = document.getElementById('consignee_id');
        const consigneeContact = document.getElementById('consignee_contact_number');
        const destinationCity = document.getElementById('destination_city');
        const destinationState = document.getElementById('destination_state');
    
        consigneeSelect.addEventListener('change', function () {
            const consigneeId = this.value;
    
            if (!consigneeId) {
                consigneeContact.value = '';
                destinationCity.value = '';
                destinationState.value = '';
                return;
            }
    
            fetch(`/consignees/details/${consigneeId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to fetch consignee details');
                return response.json();
            })
            .then(data => {
                consigneeContact.value = data.contact_number || '';
                destinationCity.value = data.destination_city || '';
                destinationState.value = data.destination_state || '';
            })
            .catch(error => {
                console.error('❌ Error:', error);
                consigneeContact.value = '';
                destinationCity.value = '';
                destinationState.value = '';
            });
        });
    });

    </script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const weightInput = document.getElementById('weight');
        const frRateInput = document.getElementById('fr_rate');
        const amountInput = document.getElementById('amount');
    
        function calculateAmount() {
            const weight = parseFloat(weightInput.value);
            const frRate = parseFloat(frRateInput.value);
    
            if (!isNaN(weight) && !isNaN(frRate)) {
                const amount = (weight * frRate).toFixed(2);
                amountInput.value = amount;
            } else {
                amountInput.value = '';
            }
        }
    
        weightInput.addEventListener('input', calculateAmount);
        frRateInput.addEventListener('input', calculateAmount);
    });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /opt/lampp/htdocs/transport/resources/views/lorry_receipts/create.blade.php ENDPATH**/ ?>