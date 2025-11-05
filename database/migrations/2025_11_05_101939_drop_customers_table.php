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
        Schema::dropIfExists('customers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the customers table as it existed prior to being dropped.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // Company relation (added 2025_06_27_000000_add_company_id_to_customers_table)
            $table->foreignId('company_id')->nullable();

            // Base columns (from 2025_06_20_012000_create_customers_table)
            $table->string('name');
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();

            // Location FKs (finalized by 2025_07_09_103711_add_location_columns_to_customers_table)
            $table->foreignId('country_id')->nullable();
            $table->foreignId('state_id')->nullable();
            $table->foreignId('city_id')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
        });
    }
};
