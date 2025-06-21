<?php $__env->startSection('content'); ?>
<div class="container mt-4">
    <h2>User Management</h2>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <form method="GET" action="<?php echo e(route('users.index')); ?>" class="row g-3 mb-3">
        <div class="col-md-2">
            <input type="text" name="name" class="form-control" placeholder="Name" value="<?php echo e(request('name')); ?>">
        </div>
        <div class="col-md-2">
            <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo e(request('email')); ?>">
        </div>
        <div class="col-md-2">
            <select name="role" class="form-select">
                <option value="">All Roles</option>
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($role); ?>" <?php echo e(request('role') == $role ? 'selected' : ''); ?>><?php echo e(ucfirst($role)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="company_id" class="form-select">
                <option value="">All Companies</option>
                <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($company->id); ?>" <?php echo e(request('company_id') == $company->id ? 'selected' : ''); ?>><?php echo e($company->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="<?php echo e(route('users.index')); ?>" class="btn btn-outline-secondary ms-2">Reset</a>
        </div>
    </form>
    <a href="<?php echo e(route('users.create')); ?>" class="btn btn-primary mb-3">Add New User</a>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Company</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($user->id); ?></td>
                    <td><?php echo e($user->name); ?></td>
                    <td><?php echo e($user->email); ?></td>
                    <td><?php echo e(ucfirst($user->role)); ?></td>
                    <td><?php echo e($user->company ? $user->company->name : '-'); ?></td>
                    <td>
                        <a href="<?php echo e(route('users.edit', $user->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <form action="<?php echo e(route('users.destroy', $user->id)); ?>" method="POST" style="display:inline-block;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" class="text-center">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="/dashboard" class="btn btn-secondary mt-2">Back to Dashboard</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ragnar/Development/transport/resources/views/users/index.blade.php ENDPATH**/ ?>