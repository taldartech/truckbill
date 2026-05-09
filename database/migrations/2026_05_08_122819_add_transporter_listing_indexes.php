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
            $table->index(['company_id', 'is_owner', 'created_at', 'deleted_at'], 'transporters_company_owner_created_deleted_idx');
            $table->index(['transporter_master_id', 'company_id', 'is_owner'], 'transporters_master_company_owner_idx');
            $table->index(['company_id', 'is_owner', 'mobile_number', 'deleted_at'], 'transporters_company_owner_mobile_deleted_idx');
            $table->index(['company_id', 'is_owner', 'short_name', 'deleted_at'], 'transporters_company_owner_short_name_deleted_idx');
        });

        Schema::table('transporter_masters', function (Blueprint $table) {
            $table->index('pan_number', 'transporter_masters_pan_number_idx');
            $table->index('name', 'transporter_masters_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->dropIndex('transporters_company_owner_deleted_idx');
            $table->dropIndex('transporters_company_owner_created_deleted_idx');
            $table->dropIndex('transporters_master_company_owner_idx');
            $table->dropIndex('transporters_company_owner_mobile_deleted_idx');
            $table->dropIndex('transporters_company_owner_short_name_deleted_idx');
        });

        Schema::table('transporter_masters', function (Blueprint $table) {
            $table->dropIndex('transporter_masters_pan_number_idx');
            $table->dropIndex('transporter_masters_name_idx');
        });
    }
};
