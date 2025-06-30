<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->insert([
            [
                'name' => 'India',
                'code' => 'IN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
