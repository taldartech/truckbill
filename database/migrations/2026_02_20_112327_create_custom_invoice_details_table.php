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
        Schema::create('custom_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('lr_id')->constrained('lorry_receipts')->onDelete('cascade');
            $table->decimal('portal_rate', 12, 2)->nullable();
            $table->decimal('diff', 12, 2)->nullable();
            $table->decimal('damage_qty', 10, 2)->nullable();
            $table->decimal('damage_rate', 12, 2)->nullable();
            $table->decimal('shortage_qty', 10, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->text('remark')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_invoice_details');
    }
};
