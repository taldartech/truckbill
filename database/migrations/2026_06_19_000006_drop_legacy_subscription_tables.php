<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'plan_end_date')) {
                $table->dropColumn('plan_end_date');
            }
            if (Schema::hasColumn('companies', 'lr_credits')) {
                $table->dropColumn('lr_credits');
            }
            if (Schema::hasColumn('companies', 'truck_credits')) {
                $table->dropColumn('truck_credits');
            }
        });

        Schema::dropIfExists('upcoming_plans');
        Schema::dropIfExists('payment_ledgers');
        Schema::dropIfExists('promo_codes');
    }

    public function down(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->enum('discount_type', ['percentage', 'absolute']);
            $table->decimal('discount_value', 8, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('max_uses')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

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

        Schema::table('payment_ledgers', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->nullOnDelete();
        });

        Schema::create('upcoming_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('payment_ledger_id')->constrained('payment_ledgers')->onDelete('cascade');
            $table->date('plan_start_date');
            $table->date('plan_end_date');
            $table->integer('truck_count')->nullable();
            $table->integer('tenure')->default(1);
            $table->enum('status', ['pending', 'active', 'applied'])->default('pending');
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->date('plan_end_date')->nullable();
            $table->integer('lr_credits')->default(0);
            $table->integer('truck_credits')->default(0);
        });
    }
};
