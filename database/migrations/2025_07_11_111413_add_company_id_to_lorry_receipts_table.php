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
        // 1. Add the company_id column first if it doesn't exist
        if (!Schema::hasColumn('lorry_receipts', 'company_id')) {
            Schema::table('lorry_receipts', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        // 2. Set the default company_id for existing records
        $defaultCompanyId = \App\Models\Company::first()?->id;
        if ($defaultCompanyId) {
            \DB::table('lorry_receipts')->update(['company_id' => $defaultCompanyId]);
        }

        // 3. Add the foreign key constraint
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->foreign('company_id', 'lorry_receipts_company_id_foreign')
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
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
