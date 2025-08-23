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
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Allow no_of_units to be nullable and default to null
            $table->integer('no_of_units')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Revert to NOT NULL with default 0 (or whatever it was before)
            $table->integer('no_of_units')->nullable(false)->default(0)->change();
        });
    }
};
