<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('consignees')
                  ->onDelete('set null'); // or 'cascade', 'restrict', etc., as needed
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
    }
};

