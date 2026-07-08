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
        if (Schema::hasTable('freight_booking_custom_amounts')) {
            Schema::table('freight_booking_custom_amounts', function (Blueprint $table) {
                if (! $this->indexExists('freight_booking_custom_amounts', 'fb_custom_amounts_booking_company_idx')) {
                    $table->index(['freight_booking_id', 'company_id'], 'fb_custom_amounts_booking_company_idx');
                }
            });

            return;
        }

        Schema::create('freight_booking_custom_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_booking_id')->constrained('freight_bookings')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['freight_booking_id', 'company_id'], 'fb_custom_amounts_booking_company_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_booking_custom_amounts');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};
