<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('session_token');
            }

            if (! Schema::hasColumn('users', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('is_blocked');
            }

            if (! Schema::hasColumn('users', 'blocked_by')) {
                $table->foreignId('blocked_by')->nullable()->after('block_reason')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'blocked_by')) {
                $table->dropForeign(['blocked_by']);
                $table->dropColumn('blocked_by');
            }
            if (Schema::hasColumn('users', 'block_reason')) {
                $table->dropColumn('block_reason');
            }
            if (Schema::hasColumn('users', 'is_blocked')) {
                $table->dropColumn('is_blocked');
            }
        });
    }
};
