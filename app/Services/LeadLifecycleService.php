<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Validation\ValidationException;

class LeadLifecycleService
{
    public function ensureMutable(Lead $lead): void
    {
        if (in_array($lead->status, [Lead::STATUS_ACTIVATED, Lead::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages([
                'status' => 'No further actions are allowed for this lead.',
            ]);
        }
    }

    public function transition(Lead $lead, string $toStatus, ?string $rejectionReason = null): Lead
    {
        $allowedTransitions = [
            Lead::STATUS_LEAD_CREATED => [Lead::STATUS_DEMO_SCHEDULED, Lead::STATUS_REJECTED],
            Lead::STATUS_DEMO_SCHEDULED => [Lead::STATUS_DEMO_COMPLETED, Lead::STATUS_REJECTED],
            Lead::STATUS_DEMO_COMPLETED => [Lead::STATUS_ACTIVATED, Lead::STATUS_REJECTED],
            Lead::STATUS_ACTIVATED => [],
            Lead::STATUS_REJECTED => [],
        ];

        if (!in_array($toStatus, $allowedTransitions[$lead->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status transition from ' . $lead->status . ' to ' . $toStatus . '.',
            ]);
        }

        $lead->status = $toStatus;
        if ($toStatus === Lead::STATUS_REJECTED) {
            $lead->rejection_reason = $rejectionReason;
        }

        $lead->save();

        return $lead;
    }
}
