<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatesSeeder extends Seeder
{
    public function run(): void
    {
        $india = DB::table('countries')->where('name', 'India')->first();
        
        // Check if states already exist before inserting
        $states = ['Rajasthan', 'Gujarat'];
        
        foreach ($states as $stateName) {
            $existingState = DB::table('states')->where('name', $stateName)->first();
            
            if (!$existingState) {
                DB::table('states')->insert([
                    'country_id' => $india->id,
                    'name' => $stateName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
