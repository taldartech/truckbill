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
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->foreignId('linked_voucher_id')->nullable()->constrained('voucher_linkeds')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->dropForeign(['linked_voucher_id']);
            $table->dropColumn('linked_voucher_id');
        });
    }
};
