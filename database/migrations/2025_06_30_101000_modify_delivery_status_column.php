<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Drop the current enum column
            $table->dropColumn('delivery_status');
            
            // Add a new nullable enum column
            $table->enum('delivery_status', ['Pending', 'Delivered', 'Cancelled'])->nullable()->after('truck_id');
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
            $table->enum('delivery_status', ['Pending', 'Delivered', 'Cancelled'])->default('Pending');
        });
    }
};
