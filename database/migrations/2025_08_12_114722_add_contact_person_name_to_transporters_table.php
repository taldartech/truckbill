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
        Schema::table('transporters', function (Blueprint $table) {
            if (!Schema::hasColumn('transporters', 'contact_person_name')) {
                $table->string('contact_person_name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            if (Schema::hasColumn('transporters', 'contact_person_name')) {
                $table->dropColumn('contact_person_name');
            }
        });
    }
};
