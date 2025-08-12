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
        Schema::table('transporters', function (Blueprint $table) {
            if (Schema::hasColumn('transporters', 'gst_type')) {
                $table->dropColumn('gst_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            if (!Schema::hasColumn('transporters', 'gst_type')) {
                $table->enum('gst_type', ['composition', 'regular'])->nullable()->after('gst_number');
            }
        });
    }
};
