<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // This payment mode is now added in the initial migration
        // No action needed here
    }

    public function down()
    {
        // No action needed as the payment mode is managed in the initial migration
    }
};

