<?php

namespace App\Contracts;

interface Auditable
{
    /**
     * Attribute names to include in audit diffs (model attribute keys).
     *
     * @return list<string>
     */
    public function auditTrackedAttributes(): array;

    /**
     * Short human identifier for the record (e.g. invoice number, LR number).
     */
    public function auditSubjectLabel(): string;

    /**
     * Display name for the entity type (e.g. "Invoice", "Lorry receipt").
     */
    public function auditSubjectTypeName(): string;

    /**
     * Company scope for filtering; null when not applicable.
     */
    public function resolveAuditCompanyId(): ?int;
}
