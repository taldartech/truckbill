<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->decimal('detention', 12, 2)->nullable()->default(null)->change();
            $table->decimal('deviation', 12, 2)->nullable()->default(null)->change();
            $table->decimal('misc_expense', 12, 2)->nullable()->default(null)->change();
            $table->decimal('misc_income', 12, 2)->nullable()->default(null)->change();
            $table->text('remark')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->decimal('detention', 12, 2)->default(0)->change();
            $table->decimal('deviation', 12, 2)->default(0)->change();
            $table->decimal('misc_expense', 12, 2)->default(0)->change();
            $table->decimal('misc_income', 12, 2)->default(0)->change();
            $table->text('remark')->nullable()->change();
        });
    }
};
