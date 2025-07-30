<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the column if it doesn't exist
        if (!Schema::hasColumn('voucher_payments', 'company_id')) {
            Schema::table('voucher_payments', function (Blueprint $table) {
                $table->foreignId('company_id')->after('id')->nullable();
            });
        }

        // Get the default company ID if it exists
        $company = \App\Models\Company::first();
        
        if ($company) {
            // Only update records that don't have a company_id set
            \DB::table('voucher_payments')
                ->whereNull('company_id')
                ->update(['company_id' => $company->id]);
        }

        // Add the foreign key constraint
        // We'll use a try-catch to prevent errors if it already exists
        try {
            Schema::table('voucher_payments', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Foreign key likely already exists, we can ignore this
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
