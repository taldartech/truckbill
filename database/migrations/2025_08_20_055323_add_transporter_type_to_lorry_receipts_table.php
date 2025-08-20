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
            $table->string('transporter_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropColumn('transporter_type');
        });
    }
};
