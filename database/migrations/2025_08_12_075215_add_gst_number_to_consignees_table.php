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
        Schema::table('consignees', function (Blueprint $table) {
            $table->string('gst_number')
                ->nullable()
                ->unique()
                ->after('id'); // Change 'id' to whichever column you want it after
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->dropUnique(['gst_number']);
            $table->dropColumn('gst_number');
        });
    }
};
