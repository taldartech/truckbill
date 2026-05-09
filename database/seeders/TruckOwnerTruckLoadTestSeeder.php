<?php

namespace Database\Seeders;

use App\Models\Company;
use Database\Seeders\Support\PanGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Inserts truck owners (transporter_masters + transporters with is_owner=1),
 * plus one driver and one truck per owner.
 *
 * Env:
 * - LOAD_TEST_TRUCK_COUNT (default 100000) — owners + trucks + drivers get this many rows
 * - LOAD_TEST_BATCH_SIZE (default 2000, max 2000)
 * - LOAD_TEST_COMPANY_ID (optional; falls back to first company)
 */
class TruckOwnerTruckLoadTestSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $targetCount = max((int) env('LOAD_TEST_TRUCK_COUNT', (int) env('LOAD_TEST_ROW_COUNT', 100_000)), 1);
        $requestedBatch = max((int) env('LOAD_TEST_BATCH_SIZE', 2000), 1);
        $batchSize = min($requestedBatch, 2000);

        if ($requestedBatch !== $batchSize && $this->command) {
            $this->command->warn("Batch size {$requestedBatch} capped to {$batchSize}.");
        }

        $companyId = (int) env('LOAD_TEST_COMPANY_ID', 0);
        if ($companyId <= 0) {
            $companyId = (int) Company::query()->value('id');
        }
        if ($companyId <= 0) {
            throw new RuntimeException('No company found. Set LOAD_TEST_COMPANY_ID or seed a company first.');
        }

        $cityId = (int) DB::table('cities')->value('id');
        $stateId = (int) DB::table('states')->value('id');
        $countryId = (int) DB::table('countries')->value('id');

        $truckType = (string) (DB::table('truck_types')->orderBy('id')->value('name') ?? 'General');

        $runToken = now()->format('YmdHis');
        $now = now();

        $inserted = 0;
        while ($inserted < $targetCount) {
            $chunk = min($batchSize, $targetCount - $inserted);
            $baseSeq = $inserted + 1;

            $masterEmails = [];
            $mastersPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $email = "truckowner_{$runToken}_{$seq}@example.test";
                $masterEmails[] = $email;

                $mastersPayload[] = [
                    'name' => "Load Truck Owner {$seq}",
                    'email' => $email,
                    'pan_number' => PanGenerator::forSequence($seq + 300_000_000, 'tow_'.$runToken),
                    'gst_number' => null,
                    'gst_type' => null,
                    'pan_active' => true,
                    'address' => "Truck owner address {$seq}",
                    'city_id' => $cityId > 0 ? $cityId : null,
                    'state_id' => $stateId > 0 ? $stateId : null,
                    'country_id' => $countryId > 0 ? $countryId : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('transporter_masters')->insert($mastersPayload);

            $masterIdByEmail = DB::table('transporter_masters')
                ->whereIn('email', $masterEmails)
                ->orderBy('id')
                ->pluck('id', 'email')
                ->all();

            $orderedMasterIds = [];
            foreach ($masterEmails as $email) {
                $orderedMasterIds[] = (int) $masterIdByEmail[$email];
            }

            $transportersPayload = [];
            foreach ($orderedMasterIds as $masterId) {
                $transportersPayload[] = [
                    'transporter_master_id' => $masterId,
                    'company_id' => $companyId,
                    'is_owner' => true,
                    'contact_person_name' => null,
                    'mobile_number' => null,
                    'declaration_given' => false,
                    'total_trucks' => 1,
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

            DB::table('transporters')->insert($transportersPayload);

            $transporterRows = DB::table('transporters')
                ->whereIn('transporter_master_id', $orderedMasterIds)
                ->orderBy('transporter_master_id')
                ->get(['id', 'transporter_master_id']);

            $transporterIdByMasterId = [];
            foreach ($transporterRows as $row) {
                $transporterIdByMasterId[(int) $row->transporter_master_id] = (int) $row->id;
            }

            $transporterIds = [];
            foreach ($orderedMasterIds as $mid) {
                $transporterIds[] = $transporterIdByMasterId[$mid];
            }

            $driverLicenses = [];
            $driversPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $license = sprintf('TO-DL-%s-%012d', $runToken, $seq);
                $driverLicenses[] = $license;

                $driversPayload[] = [
                    'company_id' => $companyId,
                    'name' => "Load Truck Driver {$seq}",
                    'mobile' => sprintf('93%09d', $seq % 1_000_000_000),
                    'license_number' => $license,
                    'address' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('drivers')->insert($driversPayload);

            $driverRows = DB::table('drivers')
                ->where('company_id', $companyId)
                ->whereIn('license_number', $driverLicenses)
                ->orderBy('license_number')
                ->get(['id', 'license_number']);

            $driverIdByLicense = [];
            foreach ($driverRows as $row) {
                $driverIdByLicense[(string) $row->license_number] = (int) $row->id;
            }

            $driverIds = [];
            foreach ($driverLicenses as $license) {
                $driverIds[] = $driverIdByLicense[$license];
            }

            $trucksPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $num = sprintf('TO-%s-%012d', $runToken, $seq);

                $trucksPayload[] = [
                    'transporter_id' => $transporterIds[$i],
                    'company_id' => $companyId,
                    'truck_number' => $num,
                    'truck_type' => $truckType,
                    'driver_id' => $driverIds[$i],
                    'registration_date' => $now->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('trucks')->insert($trucksPayload);

            $inserted += $chunk;

            if ($this->command) {
                $this->command->info("Truck owners + drivers + trucks: {$inserted}/{$targetCount}");
            }
        }
    }
}
