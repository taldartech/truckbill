<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRoleId = DB::table('user_roles')->where('name', 'super_admin')->value('id');

        DB::table('users')->updateOrInsert([
            'email' => 'admin@transport.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('12345678'),
            'user_role_id' => $superAdminRoleId,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
