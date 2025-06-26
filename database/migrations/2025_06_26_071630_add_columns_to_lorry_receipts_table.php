<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Add lane column after id
            $table->string('lane')->after('id');
            
            // Add damage related columns
            $table->decimal('damage_qty', 10, 2)->nullable()->after('lane');
            $table->decimal('damage_rate', 10, 2)->nullable()->after('damage_qty');
            
            // Add shortage related columns
            $table->decimal('shortage_qty', 10, 2)->nullable()->after('damage_rate');
            $table->decimal('shortage_rate', 10, 2)->nullable()->after('shortage_qty');
            
            // Add tax related columns
            $table->decimal('service_tax', 10, 2)->nullable()->after('shortage_rate');
            $table->decimal('swachh_bharat_cess', 10, 2)->nullable()->after('service_tax');
            
            // Add LR number column
            $table->string('lr_number')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'lane', 
                'damage_qty', 
                'damage_rate', 
                'shortage_qty', 
                'shortage_rate', 
                'service_tax', 
                'swachh_bharat_cess', 
                'lr_number'
            ]);
        });
    }
}; 