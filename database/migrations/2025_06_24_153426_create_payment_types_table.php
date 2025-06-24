<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Insert initial payment types
        \DB::table('payment_types')->insert([
            ['name' => 'advance'],
            ['name' => 'final'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_types');
    }
};
