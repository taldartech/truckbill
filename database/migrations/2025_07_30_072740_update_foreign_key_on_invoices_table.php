<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeyOnInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the old foreign key (assumes the default name)
            $table->dropForeign(['customer_id']);

            // Add the new foreign key to consignees
            $table->foreign('customer_id')->references('id')->on('consignees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the foreign key to consignees
            $table->dropForeign(['customer_id']);

            // Re-add the old foreign key to customers
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }
}

