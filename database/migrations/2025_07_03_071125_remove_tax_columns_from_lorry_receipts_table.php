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
            $table->dropColumn('service_tax');
            $table->dropColumn('swachh_bharat_cess');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->decimal('service_tax', 10, 2)->nullable();
            $table->decimal('swachh_bharat_cess', 10, 2)->nullable();
        });
    }
};
