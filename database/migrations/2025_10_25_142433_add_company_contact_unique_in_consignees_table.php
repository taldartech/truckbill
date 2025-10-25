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
        Schema::table('consignees', function (Blueprint $table) {
            // Drop any global unique index first
            if (Schema::hasColumn('consignees', 'contact_number')) {
                $table->dropUnique('consignees_contact_number_unique');
            }

            // Add composite unique constraint for company_id + contact_number
            $table->unique(['company_id', 'contact_number'], 'consignees_company_contact_unique');
        });
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->dropUnique('consignees_company_contact_unique');
            $table->unique('contact_number');
        });
    }
};
