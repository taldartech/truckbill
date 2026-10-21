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
        if (Schema::hasTable('transporters')) {
            Schema::table('transporters', function (Blueprint $table) {
                if (! Schema::hasColumn('transporters', 'is_msme')) {
                    $table->boolean('is_msme')->default(false)->after('total_trucks');
                }
                if (! Schema::hasColumn('transporters', 'msme_reg_no')) {
                    $table->string('msme_reg_no', 50)->nullable()->after('is_msme');
                }
            });
        }

        if (Schema::hasTable('transporter_masters')) {
            Schema::table('transporter_masters', function (Blueprint $table) {
                if (! Schema::hasColumn('transporter_masters', 'is_msme')) {
                    $table->boolean('is_msme')->default(false)->after('gst_type');
                }
                if (! Schema::hasColumn('transporter_masters', 'msme_reg_no')) {
                    $table->string('msme_reg_no', 50)->nullable()->after('is_msme');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transporters')) {
            Schema::table('transporters', function (Blueprint $table) {
                if (Schema::hasColumn('transporters', 'msme_reg_no')) {
                    $table->dropColumn('msme_reg_no');
                }
                if (Schema::hasColumn('transporters', 'is_msme')) {
                    $table->dropColumn('is_msme');
                }
            });
        }

        if (Schema::hasTable('transporter_masters')) {
            Schema::table('transporter_masters', function (Blueprint $table) {
                if (Schema::hasColumn('transporter_masters', 'msme_reg_no')) {
                    $table->dropColumn('msme_reg_no');
                }
                if (Schema::hasColumn('transporter_masters', 'is_msme')) {
                    $table->dropColumn('is_msme');
                }
            });
        }
    }
};
