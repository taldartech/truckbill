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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile');
            $table->string('email');
            $table->string('company_name')->nullable();
            $table->string('business_type')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('city')->nullable();
            $table->enum('status', ['LEAD_CREATED', 'DEMO_SCHEDULED', 'DEMO_COMPLETED', 'ACTIVATED', 'REJECTED'])
                ->default('LEAD_CREATED')
                ->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('accepted_terms_at')->nullable();
            $table->enum('source', ['website', 'manual'])->default('manual')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
