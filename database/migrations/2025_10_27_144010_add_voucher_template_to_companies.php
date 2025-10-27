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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('template_jurisdictional_boundary')->nullable();
            $table->string('template_header')->nullable();
            $table->string('template_name');
            $table->text('template_address')->nullable();
            $table->text('template_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'template_jurisdictional_boundary',
                'template_header',
                'template_name',
                'template_address',
                'template_note'
            ]);
        });
    }
};
