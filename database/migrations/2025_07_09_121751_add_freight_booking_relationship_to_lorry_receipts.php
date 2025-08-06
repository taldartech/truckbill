<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('lorry_receipts', 'freight_booking_id')) {
            Schema::table('lorry_receipts', function (Blueprint $table) {
                // Add foreign key constraint to existing freight_booking_id column
                $table->foreign('freight_booking_id')->references('id')->on('freight_bookings')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Check if foreign key exists before dropping
            $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('lorry_receipts');
            $foreignKeyExists = false;
            
            foreach ($foreignKeys as $foreignKey) {
                if (in_array('freight_booking_id', $foreignKey->getLocalColumns())) {
                    $foreignKeyExists = true;
                    break;
                }
            }
            
            if ($foreignKeyExists) {
                $table->dropForeign(['freight_booking_id']);
            }
        });
    }
};
