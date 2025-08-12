<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('advance_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('advance_payments', 'transporter_id')) {
                $table->unsignedBigInteger('transporter_id')->after('id');
            }
            if (!Schema::hasColumn('advance_payments', 'advance_amount')) {
                $table->decimal('advance_amount', 10, 2)->after('transporter_id');
            }
            if (!Schema::hasColumn('advance_payments', 'payment_date')) {
                $table->date('payment_date')->after('advance_amount');
            }
        });

        // Add foreign key constraint separately to avoid issues
        // Check if foreign key doesn't already exist before adding
        try {
            $foreignKeys = \DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'advance_payments' 
                AND COLUMN_NAME = 'transporter_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (empty($foreignKeys) && Schema::hasColumn('advance_payments', 'transporter_id')) {
                Schema::table('advance_payments', function (Blueprint $table) {
                    $table->foreign('transporter_id')->references('id')->on('transporters')->onDelete('cascade');
                });
            }
        } catch (\Exception $e) {
            // Ignore if foreign key already exists or other issues
        }
    }
    
    public function down()
    {
        Schema::table('advance_payments', function (Blueprint $table) {
            // Try to drop foreign key if it exists
            try {
                $table->dropForeign(['transporter_id']);
            } catch (\Exception $e) {
                // Ignore if foreign key doesn't exist
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('advance_payments', 'transporter_id')) {
                $table->dropColumn('transporter_id');
            }
            if (Schema::hasColumn('advance_payments', 'advance_amount')) {
                $table->dropColumn('advance_amount');
            }
            if (Schema::hasColumn('advance_payments', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
        });
    }
    
};
