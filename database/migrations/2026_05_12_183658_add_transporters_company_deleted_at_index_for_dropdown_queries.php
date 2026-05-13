<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->index(['company_id', 'deleted_at'], 'transporters_company_id_deleted_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->dropIndex('transporters_company_id_deleted_at_idx');
        });
    }
};
