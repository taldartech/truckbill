<?php

namespace Database\Seeders;

use App\Models\Company;
use Database\Seeders\Support\PanGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransporterLoadTestSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetCount = max((int) env('LOAD_TEST_TRANSPORTERS_COUNT', 100000), 1);
        $requestedBatchSize = max((int) env('LOAD_TEST_BATCH_SIZE', 5000), 1);
        $batchSize = min($requestedBatchSize, 3000);

        $companyId = (int) env('LOAD_TEST_COMPANY_ID', 0);
        if ($companyId <= 0) {
            $companyId = (int) Company::query()->value('id');
        }

        if ($companyId <= 0) {
            throw new RuntimeException('No company found. Seed a company first or set LOAD_TEST_COMPANY_ID.');
        }

        if ($requestedBatchSize !== $batchSize && $this->command) {
            $this->command->warn("Batch size {$requestedBatchSize} is too high for single bulk insert, using {$batchSize}.");
        }

        $cityId = (int) DB::table('cities')->value('id');
        $stateId = (int) DB::table('states')->value('id');
        $countryId = (int) DB::table('countries')->value('id');
        $runToken = now()->format('YmdHis');

        $inserted = 0;

        while ($inserted < $targetCount) {
            $currentBatch = min($batchSize, $targetCount - $inserted);
            $now = now();

            $transporterMastersPayload = [];
            $emailList = [];
            for ($index = 1; $index <= $currentBatch; $index++) {
                $sequence = $inserted + $index;
                $email = "loadtest_{$runToken}_{$sequence}@example.test";
                $emailList[] = $email;

                $transporterMastersPayload[] = [
                    'name' => "Load Test Transporter {$sequence}",
                    'email' => $email,
                    'pan_number' => PanGenerator::forSequence($sequence, 'tm_'.$runToken),
                    'gst_number' => null,
                    'gst_type' => null,
                    'pan_active' => true,
                    'address' => "Load test address {$sequence}",
                    'city_id' => $cityId > 0 ? $cityId : null,
                    'state_id' => $stateId > 0 ? $stateId : null,
                    'country_id' => $countryId > 0 ? $countryId : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('transporter_masters')->insert($transporterMastersPayload);

            $transporterMasterIds = DB::table('transporter_masters')
                ->whereIn('email', $emailList)
                ->pluck('id')
                ->all();

            $transportersPayload = [];
            foreach ($transporterMasterIds as $transporterMasterId) {
                $transportersPayload[] = [
                    'transporter_master_id' => $transporterMasterId,
                    'company_id' => $companyId,
                    'is_owner' => false,
                    'contact_person_name' => null,
                    'mobile_number' => null,
                    'declaration_given' => false,
                    'total_trucks' => 0,
                    'advance_amount' => 0,
                    'short_name' => null,
                    'beneficiary_id' => null,
                    'tds_percentage' => 20,
                    'tds_adjustment_remark' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            if ($transportersPayload !== []) {
                DB::table('transporters')->insert($transportersPayload);
            }

            $inserted += $currentBatch;

            if ($this->command) {
                $this->command->line("Inserted transporters: {$inserted}/{$targetCount}");
            }
        }
    }
}
