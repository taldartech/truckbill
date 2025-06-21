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
        Schema::create('transporters', function (Blueprint $table) {
            $table->id();
$table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
$table->string('name');
$table->string('gst_number')->nullable();
$table->enum('gst_type', ['composition', 'regular']);
$table->string('pan_number')->nullable();
$table->boolean('is_owner')->default(false);
$table->boolean('declaration_given')->default(false);
$table->integer('total_trucks')->default(0);
$table->decimal('advance_amount', 12, 2)->default(0);
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transporters');
    }
};
