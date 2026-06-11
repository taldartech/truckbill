<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transporter_masters', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        DB::table('transporter_masters')
            ->where('email', 'like', '%@transporter.placeholder')
            ->orWhere('email', 'like', '%@truckowner.placeholder')
            ->orWhere('email', 'like', '%@placeholder.local')
            ->update(['email' => null]);
    }

    public function down(): void
    {
        DB::table('transporter_masters')
            ->whereNull('email')
            ->update([
                'email' => DB::raw("CONCAT(LOWER(pan_number), '@transporter.placeholder')"),
            ]);

        Schema::table('transporter_masters', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
