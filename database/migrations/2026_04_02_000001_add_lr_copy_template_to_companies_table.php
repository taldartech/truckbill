<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'lr_copy_template')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->string('lr_copy_template')->default('classic')->after('invoice_template');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'lr_copy_template')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('lr_copy_template');
        });
    }
};
