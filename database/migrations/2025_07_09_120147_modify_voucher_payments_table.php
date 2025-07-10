<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            // Remove voucher_id foreign key and column
            $table->dropForeign(['voucher_id']);
            $table->dropColumn('voucher_id');
            
            // Add voucher_number column after id
            $table->after('id', function (Blueprint $table) {
                $table->string('voucher_number');
            });
        });
    }

    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            // Remove voucher_number column
            $table->dropColumn('voucher_number');
          
        });
    }
};
