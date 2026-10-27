<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
   public function up(): void
{
    Schema::table('transporters', function (Blueprint $table) {
        $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
        $table->text('deleted_remark')->nullable()->after('deleted_by');
    });
}

public function down(): void
{
    Schema::table('transporters', function (Blueprint $table) {
        $table->dropColumn(['deleted_by', 'deleted_remark']);
    });
}
};
