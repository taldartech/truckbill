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
        if (! Schema::hasColumn('email_templates', 'allowed_roles')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->json('allowed_roles')->nullable()->after('allowed_variables');
            });
        }

        if (! Schema::hasColumn('email_templates', 'send_to_transporter')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->boolean('send_to_transporter')->default(false)->after('allowed_roles');
            });
        }

        if (! Schema::hasColumn('email_templates', 'send_to_customer')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->boolean('send_to_customer')->default(false)->after('send_to_transporter');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('email_templates', 'send_to_customer')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn('send_to_customer');
            });
        }

        if (Schema::hasColumn('email_templates', 'send_to_transporter')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn('send_to_transporter');
            });
        }

        if (Schema::hasColumn('email_templates', 'allowed_roles')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn('allowed_roles');
            });
        }
    }
};
