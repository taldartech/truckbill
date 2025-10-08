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
            // Convert memo_id from bigint to string
            $table->string('memo_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Revert back to bigint if needed
            $table->bigInteger('memo_id')->nullable()->change();
        });
    }
};
