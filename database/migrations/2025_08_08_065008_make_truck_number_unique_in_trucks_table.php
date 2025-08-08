<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            // First, ensure the column exists before making it unique
            if (Schema::hasColumn('trucks', 'truck_number')) {
                $table->string('truck_number')->unique()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            if (Schema::hasColumn('trucks', 'truck_number')) {
                $table->string('truck_number')->change(); // removes unique constraint
            }
        });
    }
};
