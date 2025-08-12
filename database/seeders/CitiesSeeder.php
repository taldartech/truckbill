<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        // Get country and state IDs
        $india = DB::table('countries')->where('name', 'India')->first();
        $rajasthan = DB::table('states')->where('name', 'Rajasthan')->first();
        $gujarat = DB::table('states')->where('name', 'Gujarat')->first();
        
        // Define cities with their states
        $cities = [
            ['name' => 'Udaipur', 'state_id' => $rajasthan->id],
            ['name' => 'Jaipur', 'state_id' => $rajasthan->id],
            ['name' => 'Ajmer', 'state_id' => $rajasthan->id],
            ['name' => 'Jodhpur', 'state_id' => $rajasthan->id],
            ['name' => 'Himmatnagar', 'state_id' => $gujarat->id],
            ['name' => 'Ahmedabad', 'state_id' => $gujarat->id],
            ['name' => 'Surat', 'state_id' => $gujarat->id],
        ];
        
        // Insert cities only if they don't already exist
        foreach ($cities as $city) {
            $existingCity = DB::table('cities')->where('name', $city['name'])->first();
            
            if (!$existingCity) {
                DB::table('cities')->insert([
                    'state_id' => $city['state_id'],
                    'country_id' => $india->id,
                    'name' => $city['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
