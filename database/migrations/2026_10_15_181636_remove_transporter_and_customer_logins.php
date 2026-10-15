<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PORTAL_ROLES = ['transporter', 'customer'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $portalRoleIds = DB::table('user_roles')->whereIn('name', self::PORTAL_ROLES)->pluck('id');

        if ($portalRoleIds->isNotEmpty()) {
            $portalUserIds = DB::table('users')->whereIn('user_role_id', $portalRoleIds)->pluck('id');

            foreach ($portalUserIds->chunk(500) as $userIds) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
        }

        Schema::dropIfExists('consignee_user');

        if (Schema::hasColumn('users', 'transporter_master_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['transporter_master_id']);
                $table->dropColumn('transporter_master_id');
            });
        }

        DB::table('user_roles')->whereIn('name', self::PORTAL_ROLES)->delete();

        if (Schema::hasTable('email_templates')) {
            DB::table('email_templates')->where('key', 'TRANSPORTER_ACCOUNT_CREATED')->delete();
        }
    }

    /**
     * Reverse the migrations. Deleted portal users cannot be restored.
     */
    public function down(): void
    {
        foreach (self::PORTAL_ROLES as $role) {
            DB::table('user_roles')->updateOrInsert(['name' => $role]);
        }

        if (! Schema::hasColumn('users', 'transporter_master_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('transporter_master_id')->nullable()->after('user_role_id')
                    ->constrained('transporter_masters')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('consignee_user')) {
            Schema::create('consignee_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('consignee_id')->constrained('consignees')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'consignee_id']);
            });
        }
    }
};
