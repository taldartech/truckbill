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
        Schema::create('payment_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('razorpay_order_id')->unique();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->enum('payment_type', ['renewal', 'addon'])->default('renewal');
            $table->integer('truck_count')->nullable();
            $table->integer('lr_credits')->nullable();
            $table->integer('addon_truck_count')->nullable();
            $table->integer('addon_lr_credits')->nullable();
            $table->integer('tenure')->default(1);
            $table->decimal('truck_cost', 12, 2)->default(0);
            $table->decimal('lr_credits_cost', 12, 2)->default(0);
            $table->decimal('annual_maintenance_cost', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('promo_code')->nullable();
            $table->decimal('promo_discount', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('razorpay_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_ledgers');
    }
};
