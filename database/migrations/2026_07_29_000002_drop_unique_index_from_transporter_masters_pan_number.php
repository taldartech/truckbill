<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transporter_masters', function (Blueprint $table): void {
            // Allow multiple transporter_masters rows for the same dummy PAN.
            // Real PAN uniqueness is enforced at the request-validation layer.
            $table->dropUnique('transporter_masters_pan_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('transporter_masters', function (Blueprint $table): void {
            $table->unique('pan_number');
        });
    }
};

