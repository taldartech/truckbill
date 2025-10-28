<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Safely drop old unique constraints (only for those that exist)
            try { $table->dropUnique('consignees_gst_number_unique'); } catch (\Exception $e) {}
            try { $table->dropUnique('consignees_pan_number_unique'); } catch (\Exception $e) {}

            // Add company-wise unique constraints
            $table->unique(['company_id', 'gst_number'], 'consignees_company_gst_unique');
            $table->unique(['company_id', 'pan_number'], 'consignees_company_pan_unique');
            $table->unique(['company_id', 'email'], 'consignees_company_email_unique'); // newly added
        });
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Drop company-wise unique constraints
            $table->dropUnique('consignees_company_gst_unique');
            $table->dropUnique('consignees_company_pan_unique');
            $table->dropUnique('consignees_company_email_unique');

            // Restore only those that originally existed
            $table->unique('gst_number', 'consignees_gst_number_unique');
            $table->unique('pan_number', 'consignees_pan_number_unique');
           
        });
    }
};
