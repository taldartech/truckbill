<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->foreignId('transporter_id')->nullable()->change();
            $table->string('eway_bill_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->foreignId('transporter_id')->nullable(false)->change();
            $table->string('eway_bill_number')->nullable(false)->change();
        });
    }
};
