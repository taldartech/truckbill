<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('transporter_id')->constrained('companies')->nullOnDelete();
            $table->index('company_id');
        });

        DB::table('trucks')
            ->whereNotNull('transporter_id')
            ->orderBy('id')
            ->select(['id', 'transporter_id'])
            ->chunkById(100, function ($trucks): void {
                foreach ($trucks as $truck) {
                    $companyId = DB::table('transporters')
                        ->where('id', $truck->transporter_id)
                        ->value('company_id');

                    DB::table('trucks')
                        ->where('id', $truck->id)
                        ->update(['company_id' => $companyId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
