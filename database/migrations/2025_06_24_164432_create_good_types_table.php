<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('good_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Insert initial good types
        \DB::table('good_types')->insert([
            ['name' => 'cement'],
            ['name' => 'sand'],
            ['name' => 'aggregates'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('good_types');
    }
};
