<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove 'advance' payment mode
        \DB::table('payment_modes')->where('name', 'advance')->delete();
    }

    public function down(): void
    {
        // Add 'advance' payment mode back
        \DB::table('payment_modes')->insert([
            ['name' => 'advance']
        ]);
    }
};
