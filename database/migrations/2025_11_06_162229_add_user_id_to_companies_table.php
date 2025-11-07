<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Add user_id column after id (or adjust placement as needed)
            $table->unsignedBigInteger('user_id')->nullable()->after('id');

            // Add foreign key constraint
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Deletes company if user is deleted
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Drop foreign key and column on rollback
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
