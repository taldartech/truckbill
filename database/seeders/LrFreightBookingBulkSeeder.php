<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bulk-inserts paired lorry_receipts + freight_bookings (default >1 lakh each) for load testing.
 * Reuses existing transporters, trucks, and consignees for the company (unlike MassLoadTestSeeder).
 *
 * Env:
 * - LOAD_TEST_LR_FB_COUNT (default 125000 — override e.g. 200000 for 2 lakh pairs)
 * - LOAD_TEST_BATCH_SIZE (default 1000, capped at 1000 for MySQL insert packet limits)
 * - LOAD_TEST_COMPANY_ID (optional; first company id if unset)
 *
 * Run:
 * php artisan db:seed --class=LrFreightBookingBulkSeeder --no-interaction
 */
class LrFreightBookingBulkSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $targetCount = max((int) env('LOAD_TEST_LR_FB_COUNT', 125_000), 1);
        $requestedBatchSize = max((int) env('LOAD_TEST_BATCH_SIZE', 1000), 1);
        $batchSize = min($requestedBatchSize, 1000);

        $companyId = (int) env('LOAD_TEST_COMPANY_ID', 0);
        if ($companyId <= 0) {
            $companyId = (int) Company::query()->value('id');
        }
        if ($companyId <= 0) {
            throw new RuntimeException('No company found. Seed a company first or set LOAD_TEST_COMPANY_ID.');
        }

        $transporterIds = DB::table('transporters')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $truckIds = DB::table('trucks')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $consigneeIds = DB::table('consignees')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($transporterIds === [] || $truckIds === [] || $consigneeIds === []) {
            throw new RuntimeException('Need transporters, trucks, and consignees for lorry_receipts seeding.');
        }

        $userId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 0);
        $runToken = now()->format('YmdHis');
        $nextVoucher = (int) DB::table('freight_bookings')->max('voucher_number');
        $inserted = 0;

        while ($inserted < $targetCount) {
            $chunk = min($batchSize, $targetCount - $inserted);
            $now = now();
            $lrPayload = [];
            $lrNumbers = [];
            $transporterIdPerRow = [];

            for ($i = 1; $i <= $chunk; $i++) {
                $sequence = $inserted + $i;
                $transporterId = $transporterIds[$sequence % count($transporterIds)];
                $truckId = $truckIds[$sequence % count($truckIds)];
                $consigneeId = $consigneeIds[$sequence % count($consigneeIds)];
                $lrNumber = "LR-{$runToken}-{$sequence}";
                $lrNumbers[] = $lrNumber;
                $transporterIdPerRow[] = $transporterId;

                $lrPayload[] = [
                    'company_id' => $companyId,
                    'gr_date' => $now->toDateString(),
                    'lr_number' => $lrNumber,
                    'gr_number' => "GR-{$runToken}-{$sequence}",
                    'lane' => 'Road',
                    'truck_id' => $truckId,
                    'delivery_status' => 'Pending',
                    'transporter_id' => $transporterId,
                    'reference_by' => $transporterId,
                    'consignor_id' => $consigneeId,
                    'customer_id' => $consigneeId,
                    'consignor_contact_number' => sprintf('91%09d', $sequence % 1_000_000_000),
                    'consignee_id' => $consigneeId,
                    'consignee_contact_number' => sprintf('92%09d', $sequence % 1_000_000_000),
                    'source_city' => 'Source City',
                    'destination_city' => 'Destination City',
                    'source_state' => 'Source State',
                    'destination_state' => 'Destination State',
                    'unloading_station' => null,
                    'weight' => 10.0,
                    'unloading_weight' => 10.0,
                    'no_of_units' => 1,
                    'good_type' => 'General Goods',
                    'goods_value' => 1000.0,
                    'fr_rate' => 100.0,
                    'is_fixed_amount' => false,
                    'amount' => 1000.0,
                    'actual_amount' => 1000.0,
                    'invoice_generated' => false,
                    'linked_lr_id' => null,
                    'memo_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'damage_amount' => null,
                    'shortage_amount' => null,
                    'freight_booking_id' => null,
                    'transporter_type' => 'agency',
                    'deleted_at' => null,
                    'invoice_number' => null,
                    'created_by' => $userId > 0 ? $userId : null,
                    'updated_by' => $userId > 0 ? $userId : null,
                ];
            }

            DB::transaction(function () use (
                $lrPayload,
                $lrNumbers,
                $companyId,
                $transporterIdPerRow,
                $userId,
                $now,
                &$nextVoucher,
            ): void {
                DB::table('lorry_receipts')->insert($lrPayload);

                $lrRows = DB::table('lorry_receipts')
                    ->where('company_id', $companyId)
                    ->whereIn('lr_number', $lrNumbers)
                    ->orderBy('lr_number')
                    ->get(['id', 'lr_number']);

                $lrIdByNumber = [];
                foreach ($lrRows as $row) {
                    $lrIdByNumber[(string) $row->lr_number] = (int) $row->id;
                }

                $orderedLrIds = [];
                foreach ($lrNumbers as $lrNumber) {
                    $orderedLrIds[] = $lrIdByNumber[$lrNumber];
                }

                $fbPayload = [];
                foreach ($orderedLrIds as $idx => $lrId) {
                    $nextVoucher++;
                    $tid = $transporterIdPerRow[$idx];

                    $fbPayload[] = [
                        'company_id' => $companyId,
                        'transporter_id' => $tid,
                        'transporter_name' => 'Bulk seed',
                        'transporter_tds_percentage' => 0,
                        'voucher_number' => $nextVoucher,
                        'lr_id' => $lrId,
                        'linked_lr_id' => null,
                        'voucher_printed' => false,
                        'voucher_last_printed_at' => null,
                        'tr_fr_rate' => 100.0,
                        'is_fixed_amount' => false,
                        'tr_amount' => 1000.0,
                        'advance_amount' => 0,
                        'balance_amount' => 1000.0,
                        'negative_adjusted_amount' => 0,
                        'tds_amount' => 0,
                        'good_type' => 'General Goods',
                        'detention' => null,
                        'deviation' => null,
                        'misc_expense' => null,
                        'remark' => 'LrFreightBookingBulkSeeder',
                        'is_delivered' => false,
                        'is_cancelled' => false,
                        'linked_freight_booking_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'damage_amount' => null,
                        'shortage_amount' => null,
                        'pan_verified' => true,
                        'eway_bill_number' => null,
                        'eway_bill_expiry' => $now->copy()->addDays(30)->toDateString(),
                        'created_by' => $userId > 0 ? $userId : null,
                        'updated_by' => $userId > 0 ? $userId : null,
                    ];
                }

                DB::table('freight_bookings')->insert($fbPayload);

                $fbRows = DB::table('freight_bookings')
                    ->where('company_id', $companyId)
                    ->whereIn('lr_id', $orderedLrIds)
                    ->orderBy('lr_id')
                    ->get(['id', 'lr_id']);

                $fbIdByLrId = [];
                foreach ($fbRows as $row) {
                    $fbIdByLrId[(int) $row->lr_id] = (int) $row->id;
                }

                $fbIdsOrdered = [];
                foreach ($orderedLrIds as $lrId) {
                    $fbIdsOrdered[] = $fbIdByLrId[$lrId];
                }

                $caseParts = [];
                $bindings = [];
                foreach ($orderedLrIds as $k => $lrId) {
                    $caseParts[] = 'WHEN ? THEN ?';
                    $bindings[] = $lrId;
                    $bindings[] = $fbIdsOrdered[$k];
                }
                $bindings[] = $now;
                foreach ($orderedLrIds as $lrId) {
                    $bindings[] = $lrId;
                }

                $inPlaceholders = implode(',', array_fill(0, count($orderedLrIds), '?'));
                $caseSql = implode(' ', $caseParts);
                DB::update(
                    "UPDATE lorry_receipts SET freight_booking_id = CASE id {$caseSql} END, updated_at = ? WHERE id IN ({$inPlaceholders})",
                    $bindings,
                );
            });

            $inserted += $chunk;

            if ($this->command) {
                $this->command->info("LR + freight_booking pairs inserted: {$inserted}/{$targetCount}");
            }
        }
    }
}
