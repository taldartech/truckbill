<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'contact_number_secondary')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->dropColumn('contact_number_secondary');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'contact_number_secondary')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->string('contact_number_secondary', 20)->nullable()->after('contact_number');
            });
        }
    }
};
