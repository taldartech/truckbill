<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Insert Adjust in Advance payment mode
        DB::table('payment_modes')->insert([
            'id' => 4,
            'name' => 'Adjust in Advance',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down()
    {
        // Remove Adjust in Advance payment mode
        DB::table('payment_modes')->where('id', 4)->delete();
    }
};
