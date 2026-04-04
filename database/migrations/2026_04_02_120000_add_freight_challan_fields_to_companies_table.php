<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'freight_challan_template')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->string('freight_challan_template')->default('classic')->after('lr_copy_template');
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            if (Schema::hasColumn('companies', 'freight_challan_template')) {
                $table->dropColumn('freight_challan_template');
            }
        });
    }
};
