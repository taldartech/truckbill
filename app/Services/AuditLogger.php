<?php

namespace App\Services;

use App\Contracts\Auditable;
use App\Models\AuditLog;
use App\Models\AuditLogChange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public function logCreated(Model&Auditable $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $tracked = array_flip($model->auditTrackedAttributes());
        $snapshot = [];
        foreach ($model->getAttributes() as $key => $value) {
            if (isset($tracked[$key])) {
                $snapshot[$key] = $value;
            }
        }

        $changes = $this->buildChangeRows($model, $snapshot, null);
        $summary = $model->auditSubjectTypeName().' '.$model->auditSubjectLabel().' was created.';

        $this->persist($model, 'created', $summary, $changes);
    }

    public function logUpdated(Model&Auditable $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $tracked = array_flip($model->auditTrackedAttributes());
        $dirty = $model->getChanges();
        $relevant = [];
        foreach ($dirty as $key => $value) {
            if (isset($tracked[$key])) {
                $relevant[$key] = $value;
            }
        }

        if ($relevant === []) {
            return;
        }

        $old = [];
        foreach (array_keys($relevant) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        $changes = $this->buildChangeRows($model, $relevant, $old);
        if ($changes === []) {
            return;
        }

        $summary = $this->summarizeUpdated($model, $changes);

        $this->persist($model, 'updated', $summary, $changes);
    }

    public function logDeleted(Model&Auditable $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $summary = $model->auditSubjectTypeName().' '.$model->auditSubjectLabel().' was deleted.';

        $this->persist($model, 'deleted', $summary, null);
    }

    /**
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>|null  $oldValues
     * @return list<array{attribute: string, label: string, old: string|null, new: string|null}>
     */
    private function buildChangeRows(Model&Auditable $model, array $newValues, ?array $oldValues): array
    {
        $rows = [];
        foreach ($newValues as $attribute => $newRaw) {
            $oldRaw = $oldValues !== null ? ($oldValues[$attribute] ?? null) : null;
            if ($oldValues !== null && $this->valuesAreEquivalent($oldRaw, $newRaw)) {
                continue;
            }

            $rows[] = [
                'attribute' => $attribute,
                'label' => AuditAttributeLabels::labelFor($model, $attribute),
                'old' => $this->formatValue($model, $attribute, $oldRaw),
                'new' => $this->formatValue($model, $attribute, $newRaw),
            ];
        }

        return $rows;
    }

    private function valuesAreEquivalent(mixed $oldValue, mixed $newValue): bool
    {
        return $this->normalizeComparableValue($oldValue) === $this->normalizeComparableValue($newValue);
    }

    private function normalizeComparableValue(mixed $value): string
    {
        if ($value === null) {
            return '__NULL__';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \DateTimeInterface) {
            return (string) $value->getTimestamp();
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_numeric($value)) {
            $normalized = rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');

            return $normalized === '' ? '0' : $normalized;
        }

        return trim((string) $value);
    }

    /**
     * @param  list<array{attribute: string, label: string, old: string|null, new: string|null}>  $changes
     */
    private function summarizeUpdated(Model&Auditable $model, array $changes): string
    {
        $type = $model->auditSubjectTypeName();
        $label = $model->auditSubjectLabel();

        if (count($changes) === 1) {
            $c = $changes[0];

            return "{$type} {$label}: {$c['label']} changed from {$c['old']} to {$c['new']}.";
        }

        return "{$type} {$label} was updated (".count($changes).' fields).';
    }

    private function formatValue(Model&Auditable $model, string $attribute, mixed $value): ?string
    {
        if ($value === null) {
            return '—';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d-m-Y H:i');
        }

        if ($model instanceof Model) {
            $casts = $model->getCasts();
            if (($casts[$attribute] ?? null) === 'date' || ($casts[$attribute] ?? null) === 'datetime') {
                try {
                    return \Carbon\Carbon::parse($value)->format('d-m-Y');
                } catch (\Throwable) {
                    // fall through
                }
            }
            if (($casts[$attribute] ?? null) === 'decimal:2') {
                return number_format((float) $value, 2, '.', '');
            }
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    /**
     * @param  list<array{attribute: string, label: string, old: string|null, new: string|null}>|null  $changes
     */
    private function persist(Model&Auditable $model, string $action, string $summary, ?array $changes): void
    {
        $companyId = $model->resolveAuditCompanyId();

        DB::transaction(function () use ($companyId, $model, $action, $summary, $changes): void {
            $auditLog = AuditLog::query()->create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'action' => $action,
                'subject_type' => $model->getMorphClass(),
                'subject_id' => $model->getKey(),
                'subject_label' => $model->auditSubjectLabel(),
                'subject_type_name' => $model->auditSubjectTypeName(),
                'summary' => $summary,
                'occurred_at' => now(),
            ]);

            if ($changes === null || $changes === []) {
                return;
            }

            foreach ($changes as $index => $row) {
                AuditLogChange::query()->create([
                    'audit_log_id' => $auditLog->id,
                    'attribute_name' => $row['attribute'],
                    'field_label' => $row['label'],
                    'old_value' => $row['old'],
                    'new_value' => $row['new'],
                    'sort_order' => $index,
                ]);
            }
        });
    }
}
