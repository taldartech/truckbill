<?php

namespace App\Models\Concerns;

use App\Contracts\Auditable;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait HasHybridAuditLog
{
    public static function bootHasHybridAuditLog(): void
    {
        static::created(function (Model $model): void {
            if ($model instanceof Auditable) {
                app(AuditLogger::class)->logCreated($model);
            }
        });

        static::updated(function (Model $model): void {
            if ($model instanceof Auditable) {
                app(AuditLogger::class)->logUpdated($model);
            }
        });

        static::deleted(function (Model $model): void {
            if ($model instanceof Auditable) {
                app(AuditLogger::class)->logDeleted($model);
            }
        });
    }

    /**
     * Default company scope when the model has a company_id column.
     */
    public function resolveAuditCompanyId(): ?int
    {
        if (isset($this->attributes['company_id'])) {
            $v = $this->attributes['company_id'];

            return $v !== null ? (int) $v : null;
        }

        return null;
    }
}
