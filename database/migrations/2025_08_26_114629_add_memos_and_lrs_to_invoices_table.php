<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('memo_ids')->nullable();  
            $table->json('lr_ids')->nullable();       
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['memo_ids', 'lr_ids']);
        });
    }
};
