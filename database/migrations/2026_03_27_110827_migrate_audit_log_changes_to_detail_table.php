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
        Schema::create('audit_log_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_log_id')->constrained('audit_logs')->cascadeOnDelete();
            $table->string('attribute_name', 120);
            $table->string('field_label', 180);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('audit_log_id');
        });

        if (Schema::hasColumn('audit_logs', 'changes')) {
            DB::table('audit_logs')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $log) {
                    $raw = $log->changes;
                    if ($raw === null || $raw === '') {
                        continue;
                    }
                    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
                    if (! is_array($decoded)) {
                        continue;
                    }
                    $sort = 0;
                    foreach ($decoded as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        DB::table('audit_log_changes')->insert([
                            'audit_log_id' => $log->id,
                            'attribute_name' => (string) ($row['attribute'] ?? ''),
                            'field_label' => (string) ($row['label'] ?? ''),
                            'old_value' => isset($row['old']) ? (string) $row['old'] : null,
                            'new_value' => isset($row['new']) ? (string) $row['new'] : null,
                            'sort_order' => $sort,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $sort++;
                    }
                }
            });

            Schema::table('audit_logs', function (Blueprint $table): void {
                $table->dropColumn('changes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('audit_logs', 'changes')) {
            Schema::table('audit_logs', function (Blueprint $table): void {
                $table->json('changes')->nullable();
            });
        }

        if (Schema::hasTable('audit_log_changes')) {
            DB::table('audit_logs')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $log) {
                    $childRows = DB::table('audit_log_changes')
                        ->where('audit_log_id', $log->id)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get();
                    if ($childRows->isEmpty()) {
                        continue;
                    }
                    $payload = [];
                    foreach ($childRows as $c) {
                        $payload[] = [
                            'attribute' => $c->attribute_name,
                            'label' => $c->field_label,
                            'old' => $c->old_value,
                            'new' => $c->new_value,
                        ];
                    }
                    DB::table('audit_logs')->where('id', $log->id)->update([
                        'changes' => json_encode($payload),
                    ]);
                }
            });
        }

        Schema::dropIfExists('audit_log_changes');
    }
};
