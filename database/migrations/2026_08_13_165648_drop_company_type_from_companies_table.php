<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'company_type')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->dropColumn('company_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'company_type')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->enum('company_type', ['Broker', 'Agency', 'Both'])
                    ->default('Both');
            });
        }
    }
};
