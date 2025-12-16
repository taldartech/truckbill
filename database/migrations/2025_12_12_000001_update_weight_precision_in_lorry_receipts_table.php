<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Increase weight precision to 3 decimal places
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->decimal('weight', 10, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 2 decimal places (original definition)
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->decimal('weight', 10, 2)->change();
        });
    }
};


