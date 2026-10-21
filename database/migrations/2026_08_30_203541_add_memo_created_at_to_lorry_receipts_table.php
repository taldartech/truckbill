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
    Schema::table('lorry_receipts', function (Blueprint $table) {
        $table->timestamp('memo_created_at')
            ->nullable()
            ->after('memo_id');
    });
}

public function down(): void
{
    Schema::table('lorry_receipts', function (Blueprint $table) {
        $table->dropColumn('memo_created_at');
    });
}
};
