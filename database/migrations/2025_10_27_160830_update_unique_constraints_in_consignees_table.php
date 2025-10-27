<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->unique(['company_id', 'gst_number'], 'consignees_company_gst_unique');
            $table->unique(['company_id', 'pan_number'], 'consignees_company_pan_unique');
            $table->unique(['company_id', 'email'], 'consignees_company_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->dropUnique('consignees_company_gst_unique');
            $table->dropUnique('consignees_company_pan_unique');
            $table->dropUnique('consignees_company_email_unique');
        });
    }
};
