<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->enum('delivery_status', ['Pending', 'Delivered', 'Cancelled'])->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->enum('delivery_status', ['Pending', 'Delivered', 'Cancelled'])->default('Pending')->change();
        });
    }
};
