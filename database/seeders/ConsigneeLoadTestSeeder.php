<?php

namespace Database\Seeders;

use App\Models\Company;
use Database\Seeders\Support\PanGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConsigneeLoadTestSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $targetCount = max((int) env('LOAD_TEST_CONSIGNEE_COUNT', 1_000_000), 1);
        $requestedBatchSize = max((int) env('LOAD_TEST_BATCH_SIZE', 5000), 1);
        $batchSize = min($requestedBatchSize, 5000);

        $companyId = (int) env('LOAD_TEST_COMPANY_ID', 0);
        if ($companyId <= 0) {
            $companyId = (int) Company::query()->value('id');
        }
        if ($companyId <= 0) {
            throw new RuntimeException('No company found. Seed a company first or set LOAD_TEST_COMPANY_ID.');
        }

        $cityId = (int) DB::table('cities')->value('id');
        $stateId = (int) DB::table('states')->value('id');
        $countryId = (int) DB::table('countries')->value('id');
        $runToken = now()->format('YmdHis');

        $inserted = 0;
        while ($inserted < $targetCount) {
            $chunk = min($batchSize, $targetCount - $inserted);
            $now = now();
            $payload = [];

            for ($i = 1; $i <= $chunk; $i++) {
                $sequence = $inserted + $i;
                $payload[] = [
                    'company_id' => $companyId,
                    'name' => "Mass Consignee {$sequence}",
                    'address' => "Mass consignee address {$sequence}",
                    'contact_number' => sprintf('97%08d', $sequence % 100_000_000),
                    'gst_number' => null,
                    'pan_number' => PanGenerator::forSequence($sequence + 700_000_000, 'cg_'.$runToken),
                    'email' => "consignee_{$runToken}_{$sequence}@example.test",
                    'city_id' => $cityId > 0 ? $cityId : null,
                    'state_id' => $stateId > 0 ? $stateId : null,
                    'country_id' => $countryId > 0 ? $countryId : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('consignees')->insert($payload);
            $inserted += $chunk;

            if ($this->command) {
                $this->command->info("Consignees inserted: {$inserted}/{$targetCount}");
            }
        }
    }
}
