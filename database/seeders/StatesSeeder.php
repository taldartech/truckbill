<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatesSeeder extends Seeder
{
    public function run(): void
    {
        $india = DB::table('countries')->where('name', 'India')->first();
        
        DB::table('states')->insert([
            [
                'country_id' => $india->id,
                'name' => 'Rajasthan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'country_id' => $india->id,
                'name' => 'Gujarat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
