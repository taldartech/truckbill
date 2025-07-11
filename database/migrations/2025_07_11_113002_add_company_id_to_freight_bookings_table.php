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
            $table->foreignId('company_id')->after('id')->nullable();
        });

        // Get the default company ID (assuming there's at least one company in the system)
        $defaultCompanyId = \App\Models\Company::first()?->id;
        
        if ($defaultCompanyId) {
            // Update existing records with the default company ID
            \DB::table('freight_bookings')->update(['company_id' => $defaultCompanyId]);
        }

        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->foreign('company_id', 'freight_bookings_company_id_foreign')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
