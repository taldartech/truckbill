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
        Schema::table('pumps', function (Blueprint $table) {
            if (!Schema::hasColumn('pumps', 'pump_name')) {
                $table->string('pump_name');
            }
            if (!Schema::hasColumn('pumps', 'contact_name')) {
                $table->string('contact_name');
            }
            if (!Schema::hasColumn('pumps', 'contact_number')) {
                $table->string('contact_number')->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pumps', function (Blueprint $table) {
            $columnsToCheck = ['pump_name', 'contact_name', 'contact_number'];
            $columnsToDrop = [];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('pumps', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

};
