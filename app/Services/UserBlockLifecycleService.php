<?php

namespace App\Services;

use App\Mail\UserBlockedMail;
use App\Mail\UserUnblockedMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserBlockLifecycleService
{
    public function blockOwnedAccount(User $user, User $actor, string $reason): void
    {
        $linkedCompanyIds = $this->linkedCompanyIds($user);
        $allImpactedUserIds = $this->impactedUserIds($user, $linkedCompanyIds);

        Company::query()
            ->whereIn('id', $linkedCompanyIds)
            ->update([
                'is_blocked' => true,
                'updated_at' => now(),
            ]);

        User::query()
            ->whereIn('id', $allImpactedUserIds)
            ->update([
                'is_blocked' => true,
                'block_reason' => $reason,
                'blocked_by' => $actor->id,
                'session_token' => Str::random(60),
                'updated_at' => now(),
            ]);

        DB::table('sessions')->whereIn('user_id', $allImpactedUserIds)->delete();

        if (! empty($user->email)) {
            Mail::to($user->email)->send(new UserBlockedMail(
                userName: $user->name,
                blockedByName: $actor->name,
                reason: $reason,
            ));
        }
    }

    public function unblockOwnedAccount(
        User $user,
        ?User $actor = null,
        ?string $reason = null,
        bool $sendNotification = true
    ): void {
        $linkedCompanyIds = $this->linkedCompanyIds($user);
        $allImpactedUserIds = $this->impactedUserIds($user, $linkedCompanyIds);

        Company::query()
            ->whereIn('id', $linkedCompanyIds)
            ->update([
                'is_blocked' => false,
                'updated_at' => now(),
            ]);

        User::query()
            ->whereIn('id', $allImpactedUserIds)
            ->update([
                'is_blocked' => false,
                'block_reason' => null,
                'blocked_by' => null,
                'session_token' => null,
                'updated_at' => now(),
            ]);

        DB::table('sessions')->whereIn('user_id', $allImpactedUserIds)->delete();

        if ($sendNotification && $actor !== null && $reason !== null && ! empty($user->email)) {
            Mail::to($user->email)->send(new UserUnblockedMail(
                userName: $user->name,
                unblockedByName: $actor->name,
                reason: $reason,
            ));
        }
    }

    /**
     * @return array<int, int>
     */
    private function linkedCompanyIds(User $user): array
    {
        return Company::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, int>  $linkedCompanyIds
     * @return array<int, int>
     */
    private function impactedUserIds(User $user, array $linkedCompanyIds): array
    {
        $linkedUserIds = User::query()
            ->whereIn('company_id', $linkedCompanyIds)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge([$user->id], $linkedUserIds)));
    }
}
