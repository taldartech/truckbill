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
            // Remove GR number field
            $table->dropColumn('gr_number');
            $table->dropColumn('gr_date');

            // Remove damage and shortage related fields
            $table->dropColumn('damage_rate');
            $table->dropColumn('damage_qty');
            $table->dropColumn('shortage_rate');
            $table->dropColumn('shortage_qty');

            // Add new damage and shortage amount fields
            $table->decimal('damage_amount', 10, 2)->nullable();
            $table->decimal('shortage_amount', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Add back the removed columns
            $table->string('gr_number')->nullable();
            $table->date('gr_date')->nullable();

            $table->decimal('damage_rate', 10, 2)->nullable();
            $table->decimal('damage_qty', 10, 2)->nullable();
            $table->decimal('shortage_rate', 10, 2)->nullable();
            $table->decimal('shortage_qty', 10, 2)->nullable();

            // Remove the new columns
            $table->dropColumn('damage_amount');
            $table->dropColumn('shortage_amount');
        });
    }
};
