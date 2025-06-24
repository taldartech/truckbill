<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->string('consignor_contact_number', 20)->nullable()->after('consignor_id');
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropColumn('consignor_contact_number');
        });
    }
};
