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
        Schema::create('lorry_receipts', function (Blueprint $table) {
            $table->id();
$table->foreignId('truck_id')->constrained('trucks');
$table->foreignId('transporter_id')->constrained('transporters');
$table->string('eway_bill_number', 100)->nullable();
$table->enum('delivery_status', ['Pending', 'Delivered', 'Cancelled'])->default('Pending');
$table->unsignedBigInteger('consignor_id'); // FK to parties/companies if needed later
$table->unsignedBigInteger('consignee_id'); // FK to parties/companies if needed later
$table->string('consignee_contact_number', 20)->nullable();
$table->string('source_city', 100);
$table->string('destination_city', 100);
$table->string('source_state', 100);
$table->string('destination_state', 100);
$table->decimal('weight', 10, 2);
$table->integer('no_of_units');
$table->string('good_type', 100);
$table->decimal('fr_rate', 10, 2);
$table->decimal('amount', 12, 2);
$table->unsignedBigInteger('linked_lr_id')->nullable();
$table->date('gr_date');
$table->string('gr_number', 100)->unique();
$table->unsignedBigInteger('memo_id')->nullable();
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lorry_receipts');
    }
};
