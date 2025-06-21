<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('freight_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lr_id')->constrained('lorry_receipts')->onDelete('cascade');
            $table->decimal('tr_fr_rate', 12, 2);
            $table->decimal('tr_amount', 12, 2);
            $table->decimal('advance_amount', 12, 2);
            $table->decimal('balance_amount', 12, 2);
            $table->decimal('tds_amount', 12, 2)->default(0);
            $table->decimal('shortage', 12, 2)->default(0);
            $table->string('good_type')->nullable();
            $table->decimal('detention', 12, 2)->default(0);
            $table->decimal('deviation', 12, 2)->default(0);
            $table->decimal('misc_expense', 12, 2)->default(0);
            $table->decimal('misc_income', 12, 2)->default(0);
            $table->text('remark')->nullable();
            $table->boolean('is_delivered')->default(false);
            $table->boolean('is_cancelled')->default(false);
            $table->foreignId('linked_freight_booking_id')->nullable()->constrained('freight_bookings')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('freight_bookings');
    }
};
