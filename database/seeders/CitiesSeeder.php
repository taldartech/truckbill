<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        // Get state IDs
        $rajasthan = DB::table('states')->where('name', 'Rajasthan')->first();
        $gujarat = DB::table('states')->where('name', 'Gujarat')->first();
        
        // Rajasthan cities
        DB::table('cities')->insert([
            [
                'state_id' => $rajasthan->id,
                'name' => 'Udaipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'state_id' => $rajasthan->id,
                'name' => 'Jaipur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'state_id' => $rajasthan->id,
                'name' => 'Ajmer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'state_id' => $rajasthan->id,
                'name' => 'Jodhpur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Gujarat cities
        DB::table('cities')->insert([
            [
                'state_id' => $gujarat->id,
                'name' => 'Himmatnagar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'state_id' => $gujarat->id,
                'name' => 'Ahmedabad',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'state_id' => $gujarat->id,
                'name' => 'Surat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
