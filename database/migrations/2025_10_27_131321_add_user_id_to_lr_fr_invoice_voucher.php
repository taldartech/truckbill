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
    $tables = ['lorry_receipts', 'freight_bookings', 'voucher_payments', 'voucher_linkeds', 'invoices'];

    foreach ($tables as $tableName) {
      Schema::table($tableName, function (Blueprint $table) {
        $table->unsignedBigInteger('created_by')->nullable();
        $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    $tables = ['lorry_receipts', 'freight_bookings', 'voucher_payments', 'voucher_linkeds', 'invoices'];

    foreach ($tables as $tableName) {
      Schema::table($tableName, function (Blueprint $table) {
        $table->dropForeign(['created_by']);
        $table->dropColumn('created_by');
        $table->dropForeign(['updated_by']);
        $table->dropColumn('updated_by');
      });
    }
  }
};
