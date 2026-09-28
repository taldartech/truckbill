<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropIndex('freight_bookings_company_eway_expiry_idx');
            $table->dropColumn([
                'eway_bill_number',
                'eway_bill_expiry',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->string('eway_bill_number')->nullable();
            $table->date('eway_bill_expiry')->nullable();
        });
    }
};