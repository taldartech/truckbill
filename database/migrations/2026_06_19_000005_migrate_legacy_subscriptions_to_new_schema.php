<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_subscriptions')) {
            return;
        }

        $includedBranches = 2;
        $usersPerBranch = 3;
        $includedLrCredits = 3600;
        $today = now()->toDateString();

        $companies = DB::table('companies')->orderBy('id')->get();

        foreach ($companies as $company) {
            if (DB::table('company_subscriptions')->where('company_id', $company->id)->exists()) {
                continue;
            }

            $branchCount = DB::table('branches')
                ->where('company_id', $company->id)
                ->whereNull('deleted_at')
                ->count();

            $usersPerBranchCounts = DB::table('users')
                ->where('company_id', $company->id)
                ->whereNotNull('branch_id')
                ->whereNull('deleted_at')
                ->select('branch_id', DB::raw('count(*) as cnt'))
                ->groupBy('branch_id')
                ->pluck('cnt');

            $addonBranches = max(0, $branchCount - $includedBranches);
            $addonUsers = (int) $usersPerBranchCounts->sum(fn ($cnt) => max(0, $cnt - $usersPerBranch));

            $lrRemaining = (int) ($company->lr_credits ?? 0);
            $addonLrCredits = max(0, $lrRemaining - $includedLrCredits);

            $createdDate = $company->created_at
                ? \Carbon\Carbon::parse($company->created_at)->toDateString()
                : $today;

            $planEndDate = $company->plan_end_date ?? null;
            $status = 'trial';
            $startsOn = $createdDate;
            $endsOn = \Carbon\Carbon::parse($createdDate)->addDays(15)->toDateString();
            $trialEndsOn = $endsOn;

            if ($planEndDate !== null) {
                $endsOn = $planEndDate;
                $startsOn = \Carbon\Carbon::parse($planEndDate)->subYear()->addDay()->toDateString();
                if ($startsOn < $createdDate) {
                    $startsOn = $createdDate;
                }
                $trialEndsOn = null;

                if ($planEndDate >= $today) {
                    $status = 'active';
                } else {
                    $status = 'expired';
                }
            }

            $subscriptionId = DB::table('company_subscriptions')->insertGetId([
                'company_id' => $company->id,
                'status' => $status,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'trial_ends_on' => $trialEndsOn,
                'included_branches' => $includedBranches,
                'users_per_branch' => $usersPerBranch,
                'included_lr_credits' => $includedLrCredits,
                'included_trucks' => 0,
                'addon_branches' => $addonBranches,
                'addon_users' => $addonUsers,
                'addon_trucks' => 0,
                'addon_lr_credits' => $addonLrCredits,
                'lr_credits_remaining' => $lrRemaining,
                'last_renewed_at' => $status === 'active' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (isset($company->truck_credits) && $company->truck_credits !== null) {
                DB::table('subscription_events')->insert([
                    'company_id' => $company->id,
                    'event_type' => 'legacy_migration',
                    'metadata' => json_encode(['truck_credits' => $company->truck_credits]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            unset($subscriptionId);
        }

        if (Schema::hasTable('payment_ledgers')) {
            $ledgerMap = [];

            $ledgers = DB::table('payment_ledgers')->orderBy('id')->get();
            foreach ($ledgers as $ledger) {
                if (DB::table('subscription_payments')->where('legacy_payment_ledger_id', $ledger->id)->exists()) {
                    $ledgerMap[$ledger->id] = DB::table('subscription_payments')
                        ->where('legacy_payment_ledger_id', $ledger->id)
                        ->value('id');

                    continue;
                }

                $promoCode = null;
                if (Schema::hasColumn('payment_ledgers', 'promo_code_id') && $ledger->promo_code_id) {
                    $promoCode = DB::table('promo_codes')->where('id', $ledger->promo_code_id)->value('code');
                } elseif (property_exists($ledger, 'promo_code')) {
                    $promoCode = $ledger->promo_code ?? null;
                }

                $newId = DB::table('subscription_payments')->insertGetId([
                    'company_id' => $ledger->company_id,
                    'type' => $ledger->payment_type,
                    'status' => $ledger->status,
                    'razorpay_order_id' => $ledger->razorpay_order_id,
                    'razorpay_payment_id' => $ledger->razorpay_payment_id,
                    'razorpay_signature' => $ledger->razorpay_signature,
                    'promo_code' => $promoCode,
                    'quantities' => json_encode([
                        'truck_count' => $ledger->truck_count,
                        'lr_credits' => $ledger->lr_credits,
                        'addon_truck_count' => $ledger->addon_truck_count,
                        'addon_lr_credits' => $ledger->addon_lr_credits,
                        'tenure' => $ledger->tenure,
                    ]),
                    'subtotal_paise' => (int) round(((float) $ledger->subtotal) * 100),
                    'promo_discount_paise' => (int) round(((float) $ledger->promo_discount) * 100),
                    'gst_amount_paise' => (int) round(((float) $ledger->gst_amount) * 100),
                    'total_amount_paise' => (int) round(((float) $ledger->total_amount) * 100),
                    'catalog_snapshot' => json_encode(['migrated' => true, 'legacy' => true]),
                    'razorpay_response' => $ledger->razorpay_response ? json_decode($ledger->razorpay_response, true) : null,
                    'legacy_payment_ledger_id' => $ledger->id,
                    'created_at' => $ledger->created_at,
                    'updated_at' => $ledger->updated_at,
                ]);

                $ledgerMap[$ledger->id] = $newId;
            }

            if (Schema::hasTable('upcoming_plans')) {
                $upcoming = DB::table('upcoming_plans')->where('status', 'pending')->get();
                foreach ($upcoming as $plan) {
                    $ledger = DB::table('payment_ledgers')->where('id', $plan->payment_ledger_id)->first();
                    if (! $ledger || $ledger->status !== 'completed') {
                        continue;
                    }

                    $paymentId = $ledgerMap[$plan->payment_ledger_id] ?? null;
                    if (! $paymentId) {
                        continue;
                    }

                    if (DB::table('upcoming_subscriptions')->where('payment_id', $paymentId)->exists()) {
                        continue;
                    }

                    DB::table('upcoming_subscriptions')->insert([
                        'company_id' => $plan->company_id,
                        'payment_id' => $paymentId,
                        'starts_on' => $plan->plan_start_date,
                        'ends_on' => $plan->plan_end_date,
                        'included_branches' => $includedBranches,
                        'users_per_branch' => $usersPerBranch,
                        'included_lr_credits' => $includedLrCredits,
                        'included_trucks' => 0,
                        'addon_branches' => 0,
                        'addon_users' => 0,
                        'addon_trucks' => 0,
                        'addon_lr_credits' => 0,
                        'status' => 'pending',
                        'catalog_snapshot' => json_encode(['migrated' => true, 'truck_count' => $plan->truck_count]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_subscriptions') && Schema::hasTable('companies')) {
            $companies = DB::table('companies')->orderBy('id')->get();

            foreach ($companies as $company) {
                $subscription = DB::table('company_subscriptions')
                    ->where('company_id', $company->id)
                    ->first();

                if ($subscription === null) {
                    continue;
                }

                $planEndDate = $subscription->trial_ends_on !== null ? null : $subscription->ends_on;

                $truckCredits = 0;
                if (Schema::hasTable('subscription_events')) {
                    $event = DB::table('subscription_events')
                        ->where('company_id', $company->id)
                        ->where('event_type', 'legacy_migration')
                        ->orderByDesc('id')
                        ->first();

                    if ($event !== null) {
                        $metadata = json_decode($event->metadata, true);
                        $truckCredits = (int) ($metadata['truck_credits'] ?? 0);
                    }
                }

                $updates = [];

                if (Schema::hasColumn('companies', 'plan_end_date')) {
                    $updates['plan_end_date'] = $planEndDate;
                }

                if (Schema::hasColumn('companies', 'lr_credits')) {
                    $updates['lr_credits'] = (int) $subscription->lr_credits_remaining;
                }

                if (Schema::hasColumn('companies', 'truck_credits')) {
                    $updates['truck_credits'] = $truckCredits;
                }

                if ($updates !== []) {
                    DB::table('companies')->where('id', $company->id)->update($updates);
                }
            }
        }

        $paymentToLedgerMap = [];

        if (Schema::hasTable('subscription_payments') && Schema::hasTable('payment_ledgers')) {
            $payments = DB::table('subscription_payments')->orderBy('id')->get();

            foreach ($payments as $payment) {
                $snapshot = $payment->catalog_snapshot
                    ? json_decode($payment->catalog_snapshot, true)
                    : [];

                $isLegacyPayment = ($snapshot['migrated'] ?? false) || $payment->legacy_payment_ledger_id;

                if (! $isLegacyPayment) {
                    continue;
                }

                if (
                    $payment->legacy_payment_ledger_id
                    && DB::table('payment_ledgers')->where('id', $payment->legacy_payment_ledger_id)->exists()
                ) {
                    $paymentToLedgerMap[$payment->id] = $payment->legacy_payment_ledger_id;

                    continue;
                }

                $quantities = $payment->quantities
                    ? json_decode($payment->quantities, true)
                    : [];

                $amounts = $this->resolvePaymentAmounts($payment);

                $ledgerId = DB::table('payment_ledgers')->insertGetId([
                    'company_id' => $payment->company_id,
                    'razorpay_order_id' => $payment->razorpay_order_id,
                    'razorpay_payment_id' => $payment->razorpay_payment_id,
                    'razorpay_signature' => $payment->razorpay_signature,
                    'payment_type' => $payment->type,
                    'truck_count' => $quantities['truck_count'] ?? null,
                    'lr_credits' => $quantities['lr_credits'] ?? null,
                    'addon_truck_count' => $quantities['addon_truck_count'] ?? null,
                    'addon_lr_credits' => $quantities['addon_lr_credits'] ?? null,
                    'tenure' => $quantities['tenure'] ?? 1,
                    'truck_cost' => 0,
                    'lr_credits_cost' => 0,
                    'annual_maintenance_cost' => 0,
                    'subtotal' => $amounts['subtotal'],
                    'promo_code' => $payment->promo_code,
                    'promo_discount' => $amounts['promo_discount'],
                    'gst_amount' => $amounts['gst_amount'],
                    'total_amount' => $amounts['total_amount'],
                    'status' => $payment->status,
                    'razorpay_response' => $payment->razorpay_response,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ]);

                $paymentToLedgerMap[$payment->id] = $ledgerId;
            }
        }

        if (Schema::hasTable('upcoming_subscriptions') && Schema::hasTable('upcoming_plans')) {
            $hasUpcomingDates = Schema::hasColumn('upcoming_subscriptions', 'starts_on')
                && Schema::hasColumn('upcoming_subscriptions', 'ends_on');

            if ($hasUpcomingDates) {
                $upcomingSubscriptions = DB::table('upcoming_subscriptions')
                    ->where('status', 'pending')
                    ->orderBy('id')
                    ->get();

                foreach ($upcomingSubscriptions as $upcoming) {
                    $snapshot = $upcoming->catalog_snapshot
                        ? json_decode($upcoming->catalog_snapshot, true)
                        : [];

                    if (! ($snapshot['migrated'] ?? false)) {
                        continue;
                    }

                    $ledgerId = $paymentToLedgerMap[$upcoming->payment_id] ?? null;

                    if ($ledgerId === null) {
                        continue;
                    }

                    if (DB::table('upcoming_plans')->where('payment_ledger_id', $ledgerId)->exists()) {
                        continue;
                    }

                    DB::table('upcoming_plans')->insert([
                        'company_id' => $upcoming->company_id,
                        'payment_ledger_id' => $ledgerId,
                        'plan_start_date' => $upcoming->starts_on,
                        'plan_end_date' => $upcoming->ends_on,
                        'truck_count' => $snapshot['truck_count'] ?? null,
                        'tenure' => 1,
                        'status' => 'pending',
                        'created_at' => $upcoming->created_at,
                        'updated_at' => $upcoming->updated_at,
                    ]);
                }
            }
        }

        if (Schema::hasTable('subscription_events')) {
            DB::table('subscription_events')->delete();
        }

        if (Schema::hasTable('upcoming_subscriptions')) {
            DB::table('upcoming_subscriptions')->delete();
        }

        if (Schema::hasTable('subscription_payments')) {
            DB::table('subscription_payments')->delete();
        }

        if (Schema::hasTable('company_subscriptions')) {
            DB::table('company_subscriptions')->delete();
        }
    }

    /**
     * @return array{subtotal: float, promo_discount: float, gst_amount: float, total_amount: float}
     */
    private function resolvePaymentAmounts(object $payment): array
    {
        if (Schema::hasColumn('subscription_payments', 'subtotal')) {
            return [
                'subtotal' => (float) $payment->subtotal,
                'promo_discount' => (float) $payment->promo_discount,
                'gst_amount' => (float) $payment->gst_amount,
                'total_amount' => (float) $payment->total_amount,
            ];
        }

        return [
            'subtotal' => round(((int) $payment->subtotal_paise) / 100, 2),
            'promo_discount' => round(((int) $payment->promo_discount_paise) / 100, 2),
            'gst_amount' => round(((int) $payment->gst_amount_paise) / 100, 2),
            'total_amount' => round(((int) $payment->total_amount_paise) / 100, 2),
        ];
    }
};
