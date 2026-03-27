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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 32);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_label')->nullable();
            $table->string('subject_type_name', 80);
            $table->text('summary');
            /** @var array<int, array{attribute: string, label: string, old: mixed, new: mixed}>|null */
            $table->json('changes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
