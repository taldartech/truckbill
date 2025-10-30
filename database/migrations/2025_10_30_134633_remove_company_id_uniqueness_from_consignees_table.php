<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Drop the existing unique constraints that include company_id
            $table->dropUnique('consignees_company_contact_unique');
            $table->dropUnique('consignees_company_email_unique');
            $table->dropUnique('consignees_company_gst_unique');
           
        });
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Re-add the constraints if rollback is needed
            $table->unique(['company_id', 'contact_number'], 'consignees_company_contact_unique');
            $table->unique(['company_id', 'email'], 'consignees_company_email_unique');
            $table->unique(['company_id', 'gst_number'], 'consignees_company_gst_unique');
            
        });
    }
};
