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
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('transporter_id')->nullable()->after('company_id');
            $table->string('transporter_name')->nullable()->after('transporter_id');
            $table->decimal('transporter_tds_percentage', 5, 2)->nullable()->after('transporter_name');
            
            $table->foreign('transporter_id')->references('id')->on('transporters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropForeign(['transporter_id']);
            $table->dropColumn(['transporter_id', 'transporter_name', 'transporter_tds_percentage']);
        });
    }
};
