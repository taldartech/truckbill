<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ledgers') && ! Schema::hasColumn('ledgers', 'opening_balance_reference')) {
            Schema::table('ledgers', function (Blueprint $table): void {
                $table->string('opening_balance_reference', 100)->nullable()->after('opening_balance_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ledgers') && Schema::hasColumn('ledgers', 'opening_balance_reference')) {
            Schema::table('ledgers', function (Blueprint $table): void {
                $table->dropColumn('opening_balance_reference');
            });
        }
    }
};
