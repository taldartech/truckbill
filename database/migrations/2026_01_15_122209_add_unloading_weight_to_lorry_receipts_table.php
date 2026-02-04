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
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->decimal('unloading_weight', 10, 3)->nullable()->after('weight');
        });

        // Set unloading_weight = weight for existing records
        DB::statement('UPDATE lorry_receipts SET unloading_weight = weight WHERE unloading_weight IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropColumn('unloading_weight');
        });
    }
};
