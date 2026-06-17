<?php

namespace App\Services;

use App\Models\OnboardingProvisioningLog;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingProvisioningService
{
    public function createUser(array $payload): array
    {
        $existingLog = OnboardingProvisioningLog::query()
            ->where('idempotency_key', $payload['idempotency_key'])
            ->first();

        if ($existingLog !== null && $existingLog->response_payload !== null) {
            $response = $existingLog->response_payload;

            if (($response['status'] ?? null) === 'created') {
                $response['status'] = 'already_provisioned';
            }

            return $response;
        }

        if (User::query()->where('email', $payload['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'The email has already been taken.',
            ]);
        }

        return DB::transaction(function () use ($payload): array {
            $role = UserRole::firstOrCreate(['name' => 'super_admin']);

            $user = User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password'],
                'user_role_id' => $role->id,
                'company_id' => null,
                'branch_id' => null,
                'email_verified_at' => $payload['email_verified_at'] ?? null,
                'policy_accepted_at' => $payload['policy_accepted_at'] ?? null,
                'policy_version' => $payload['policy_version'] ?? null,
                'referral_code' => $this->generateUniqueReferralCode(),
            ]);

            $response = [
                'status' => 'created',
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => 'super_admin',
            ];

            OnboardingProvisioningLog::query()->updateOrCreate(
                ['idempotency_key' => $payload['idempotency_key']],
                [
                    'action' => 'create_user',
                    'user_id' => $user->id,
                    'request_payload' => $this->redactPayload($payload),
                    'response_payload' => $response,
                ]
            );

            return $response;
        });
    }

    public function updateUser(array $payload): array
    {
        $user = User::query()
            ->with('userRole')
            ->where('email', $payload['email'])
            ->first();

        if ($user === null || $user->userRole?->name !== 'super_admin') {
            throw ValidationException::withMessages([
                'email' => 'Super admin user not found.',
            ]);
        }

        $updates = collect($payload)
            ->only(['name', 'email_verified_at', 'policy_accepted_at', 'policy_version'])
            ->filter(fn ($value) => $value !== null)
            ->all();

        if (! empty($payload['password'])) {
            $updates['password'] = $payload['password'];
        }

        if ($updates !== []) {
            $user->update($updates);
        }

        return $this->statusFor($user->fresh(['userRole', 'ownedCompanies']));
    }

    public function statusFor(?User $user): array
    {
        if ($user === null) {
            return [
                'exists' => false,
                'email' => null,
            ];
        }

        return [
            'exists' => true,
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->userRole?->name,
            'email_verified' => $user->email_verified_at !== null,
            'policy_accepted' => $user->policy_accepted_at !== null,
            'policy_version' => $user->policy_version,
            'company_count' => $user->ownedCompanies()->count(),
            'status' => $this->resolveStatus($user),
        ];
    }

    public function statusByEmail(string $email): array
    {
        $user = User::query()
            ->with(['userRole', 'ownedCompanies'])
            ->where('email', $email)
            ->first();

        if ($user === null) {
            return [
                'exists' => false,
                'email' => $email,
            ];
        }

        return $this->statusFor($user);
    }

    private function resolveStatus(User $user): string
    {
        if ($user->userRole?->name !== 'super_admin') {
            return 'inactive';
        }

        if ($user->is_blocked) {
            return 'blocked';
        }

        if ($user->email_verified_at === null) {
            return 'pending_email_verification';
        }

        if ($user->policy_accepted_at === null) {
            return 'pending_policy_acceptance';
        }

        return 'active';
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactPayload(array $payload): array
    {
        unset($payload['password']);

        return $payload;
    }
}
