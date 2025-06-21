<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_booking_id')->constrained('freight_bookings')->onDelete('cascade');
            $table->foreignId('truck_id')->constrained('trucks')->onDelete('cascade');
            $table->foreignId('transporter_id')->constrained('transporters')->onDelete('cascade');
            $table->boolean('pan_verified')->default(false);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
