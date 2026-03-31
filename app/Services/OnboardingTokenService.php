<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\OnboardingToken;
use Illuminate\Support\Str;

class OnboardingTokenService
{
    public function generateForLead(Lead $lead, int $createdBy): array
    {
        // Keep only one active token per lead.
        // Regenerating link invalidates previous unused links.
        OnboardingToken::query()
            ->where('lead_id', $lead->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $plainToken = Str::random(64);

        $token = OnboardingToken::create([
            'lead_id' => $lead->id,
            'token_hash' => $this->hashToken($plainToken),
            'created_by' => $createdBy,
        ]);

        return [
            'plain_token' => $plainToken,
            'record' => $token,
        ];
    }

    public function findActiveByPlainToken(string $plainToken): ?OnboardingToken
    {
        return OnboardingToken::query()
            ->with('lead')
            ->where('token_hash', $this->hashToken($plainToken))
            ->whereNull('used_at')
            ->first();
    }

    public function markUsed(OnboardingToken $token): void
    {
        $token->used_at = now();
        $token->save();
    }

    public function hashToken(string $plainToken): string
    {
        return hash_hmac('sha256', $plainToken, (string) config('app.key'));
    }
}
