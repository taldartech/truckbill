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
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE lorry_receipts MODIFY delivery_status ENUM("Pending", "Delivered", "Cancelled", "In-Progress") DEFAULT "Pending";');
            return;
        }

        // SQLite (and other drivers) do not support enum ALTER statements; relax to string
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->string('delivery_status')->default('Pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE lorry_receipts MODIFY delivery_status ENUM("Pending", "Delivered", "Cancelled") DEFAULT "Pending";');
            return;
        }

        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->string('delivery_status')->default('Pending')->change();
        });
    }
};
