<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Make column nullable first
        Schema::table('consignees', function (Blueprint $table) {
            $table->string('contact_number')->nullable()->change();
        });

        // 2️⃣ Clean duplicates (keep the first, set others to NULL)
        DB::statement("
            UPDATE consignees
            SET contact_number = NULL
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MIN(id) AS id
                    FROM consignees
                    WHERE contact_number IS NOT NULL
                    GROUP BY contact_number
                ) AS keepers
            )
        ");

        // 3️⃣ Now add unique constraint
        Schema::table('consignees', function (Blueprint $table) {
            $table->unique('contact_number');
        });
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->dropUnique(['contact_number']);
            $table->string('contact_number')->nullable(false)->change();
        });
    }
};
