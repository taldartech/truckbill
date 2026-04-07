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
        if (! Schema::hasTable('email_dispatch_logs')) {
            return;
        }

        if (! Schema::hasColumn('email_dispatch_logs', 'triggered_by_name')) {
            Schema::table('email_dispatch_logs', function (Blueprint $table) {
                $table->string('triggered_by_name')->nullable()->after('triggered_by_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('email_dispatch_logs')) {
            return;
        }

        if (Schema::hasColumn('email_dispatch_logs', 'triggered_by_name')) {
            Schema::table('email_dispatch_logs', function (Blueprint $table) {
                $table->dropColumn('triggered_by_name');
            });
        }
    }
};
