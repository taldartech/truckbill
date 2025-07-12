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
        Schema::table('transporter_bank_accounts', function (Blueprint $table) {
            $table->string('account_number')->nullable()->change();
            $table->string('ifsc_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transporter_bank_accounts', function (Blueprint $table) {
            $table->string('account_number')->nullable(false)->change();
            $table->string('ifsc_code')->nullable(false)->change();
        });
    }
};
