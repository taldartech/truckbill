<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LorryReceiptLoadTestSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $targetCount = max((int) env('LOAD_TEST_LR_COUNT', 1_000_000), 1);
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
        $inserted = 0;

        while ($inserted < $targetCount) {
            $chunk = min($batchSize, $targetCount - $inserted);
            $now = now();
            $payload = [];

            for ($i = 1; $i <= $chunk; $i++) {
                $sequence = $inserted + $i;
                $transporterId = $transporterIds[$sequence % count($transporterIds)];
                $truckId = $truckIds[$sequence % count($truckIds)];
                $consigneeId = $consigneeIds[$sequence % count($consigneeIds)];

                $payload[] = [
                    'company_id' => $companyId,
                    'gr_date' => $now->toDateString(),
                    'lr_number' => "LR-{$runToken}-{$sequence}",
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

            DB::table('lorry_receipts')->insert($payload);
            $inserted += $chunk;

            if ($this->command) {
                $this->command->info("Lorry receipts inserted: {$inserted}/{$targetCount}");
            }
        }
    }
}
