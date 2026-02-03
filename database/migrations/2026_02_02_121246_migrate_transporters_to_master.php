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
        if (! Schema::hasColumn('transporters', 'transporter_master_id')) {
            Schema::table('transporters', function (Blueprint $table) {
                $table->unsignedBigInteger('transporter_master_id')->nullable()->after('company_id');
            });
        }

        $panToMasterId = [];
        $transporters = DB::table('transporters')->whereNull('transporter_master_id')->orderBy('id')->get();

        foreach ($transporters as $t) {
            $pan = $t->pan_number ? strtoupper(trim($t->pan_number)) : null;
            if (!$pan) {
                continue;
            }

            if (!isset($panToMasterId[$pan])) {
                $email = strtolower($pan) . '@transporter.placeholder';
                $masterId = DB::table('transporter_masters')->insertGetId([
                    'name' => $t->name ?? 'Unknown',
                    'email' => $email,
                    'pan_number' => $pan,
                    'gst_number' => $t->gst_number ?? null,
                    'gst_type' => $t->gst_type ?? null,
                    'pan_active' => (bool) ($t->pan_active ?? false),
                    'address' => $t->address ?? null,
                    'city_id' => $t->city_id ?? null,
                    'state_id' => $t->state_id ?? null,
                    'country_id' => $t->country_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $panToMasterId[$pan] = $masterId;
            }

            DB::table('transporters')
                ->where('id', $t->id)
                ->update(['transporter_master_id' => $panToMasterId[$pan]]);
        }

        $transportersWithoutPan = DB::table('transporters')->whereNull('transporter_master_id')->get();
        foreach ($transportersWithoutPan as $t) {
            $masterId = DB::table('transporter_masters')->insertGetId([
                'name' => $t->name ?? 'Unknown',
                'email' => 'transporter_' . $t->id . '@placeholder.local',
                'pan_number' => 'PLACEHOLDER' . str_pad($t->id, 5, '0', STR_PAD_LEFT),
                'gst_number' => $t->gst_number ?? null,
                'gst_type' => $t->gst_type ?? null,
                'pan_active' => (bool) ($t->pan_active ?? false),
                'address' => $t->address ?? null,
                'city_id' => $t->city_id ?? null,
                'state_id' => $t->state_id ?? null,
                'country_id' => $t->country_id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('transporters')->where('id', $t->id)->update(['transporter_master_id' => $masterId]);
        }

        Schema::table('transporters', function (Blueprint $table) {
            $table->unsignedBigInteger('transporter_master_id')->nullable(false)->change();
        });
        try {
            Schema::table('transporters', function (Blueprint $table) {
                $table->foreign('transporter_master_id')->references('id')->on('transporter_masters')->onDelete('cascade');
            });
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }

        $columnsToDrop = array_filter([
            'name', 'gst_number', 'pan_number', 'pan_active', 'address',
            'city_id', 'state_id', 'country_id',
        ], fn ($col) => Schema::hasColumn('transporters', $col));

        if (! empty($columnsToDrop)) {
            $fkColumns = ['country_id', 'state_id', 'city_id'];
            foreach ($fkColumns as $col) {
                if (in_array($col, $columnsToDrop)) {
                    try {
                        Schema::table('transporters', fn (Blueprint $t) => $t->dropForeign([$col]));
                    } catch (\Throwable $e) {
                        // Foreign key may not exist
                    }
                }
            }
            Schema::table('transporters', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->string('name')->nullable()->after('company_id');
            $table->string('gst_number')->nullable();
            $table->enum('gst_type', ['composition', 'regular'])->nullable();
            $table->string('pan_number')->nullable();
            $table->boolean('pan_active')->default(false);
            $table->text('address')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
        });

        $transporters = DB::table('transporters')->get();
        foreach ($transporters as $t) {
            $master = DB::table('transporter_masters')->find($t->transporter_master_id);
            if ($master) {
                DB::table('transporters')->where('id', $t->id)->update([
                    'name' => $master->name,
                    'gst_number' => $master->gst_number,
                    'gst_type' => $master->gst_type,
                    'pan_number' => $master->pan_number,
                    'pan_active' => $master->pan_active,
                    'address' => $master->address,
                    'city_id' => $master->city_id,
                    'state_id' => $master->state_id,
                    'country_id' => $master->country_id,
                ]);
            }
        }

        Schema::table('transporters', function (Blueprint $table) {
            $table->dropForeign(['transporter_master_id']);
        });
        Schema::table('transporters', function (Blueprint $table) {
            $table->dropColumn('transporter_master_id');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
        });
    }
};
