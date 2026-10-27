<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'master_admin',
            'super_admin',
            'admin',
            'moderator',
            'accountant',
        ];

        foreach ($roles as $role) {
            DB::table('user_roles')->updateOrInsert([
                'name' => $role,
            ]);
        }
    }
}
