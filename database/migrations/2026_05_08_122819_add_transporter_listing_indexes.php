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
        Schema::table('transporters', function (Blueprint $table) {
            $table->index(['company_id', 'is_owner', 'deleted_at'], 'transporters_company_owner_deleted_idx');
            $table->index('transporter_master_id', 'transporters_master_id_idx');
            $table->index('mobile_number', 'transporters_mobile_number_idx');
            $table->index('short_name', 'transporters_short_name_idx');
            $table->index('beneficiary_id', 'transporters_beneficiary_id_idx');
            $table->index('created_at', 'transporters_created_at_idx');
        });

        Schema::table('transporter_masters', function (Blueprint $table) {
            $table->index('name', 'transporter_masters_name_idx');
            $table->index('pan_number', 'transporter_masters_pan_number_idx');
            $table->index('city_id', 'transporter_masters_city_id_idx');
            $table->index('deleted_at', 'transporter_masters_deleted_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->dropIndex('transporters_company_owner_deleted_idx');
            $table->dropIndex('transporters_master_id_idx');
            $table->dropIndex('transporters_mobile_number_idx');
            $table->dropIndex('transporters_short_name_idx');
            $table->dropIndex('transporters_beneficiary_id_idx');
            $table->dropIndex('transporters_created_at_idx');
        });

        Schema::table('transporter_masters', function (Blueprint $table) {
            $table->dropIndex('transporter_masters_name_idx');
            $table->dropIndex('transporter_masters_pan_number_idx');
            $table->dropIndex('transporter_masters_city_id_idx');
            $table->dropIndex('transporter_masters_deleted_at_idx');
        });
    }
};
