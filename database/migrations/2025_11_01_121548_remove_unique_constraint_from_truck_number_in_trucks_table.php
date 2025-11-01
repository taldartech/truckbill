<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            // Remove unique index from truck_number (if exists)
            $table->dropUnique(['truck_number']);
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            // Restore the unique constraint if you rollback
            $table->unique('truck_number');
        });
    }
};
