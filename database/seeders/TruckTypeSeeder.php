<?php

namespace Database\Seeders;

use App\Models\TruckType;
use Illuminate\Database\Seeder;

class TruckTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Open',
            'Closed',
            'Container',
            'Trailer',
            'Tanker',
            'Refrigerated',
            'Flatbed',
            'Tipper',
            'Other',
        ];

        foreach ($types as $name) {
            TruckType::firstOrCreate(['name' => $name]);
        }
    }
}
