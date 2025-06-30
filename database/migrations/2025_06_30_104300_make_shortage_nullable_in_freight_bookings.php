<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->decimal('shortage', 12, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->decimal('shortage', 12, 2)->default(0)->change();
        });
    }
};
