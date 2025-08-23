<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Insert initial payment modes
        \DB::table('payment_modes')->insert([
            ['name' => 'cash'],
            ['name' => 'bank transfer'],
            ['name' => 'cheque'],
            ['name' => 'upi/qr'],
            ['name' => 'adjust in advance'],
            ['name' => 'fuel(paid to pump)'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_modes');
    }
};
