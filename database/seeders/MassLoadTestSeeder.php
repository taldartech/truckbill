<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Company;
use Database\Seeders\Support\PanGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bulk inserts synthetic rows for load testing (default 50k per domain).
 *
 * Targets operational tables: transporter_masters/transporters, consignees, drivers, trucks,
 * lorry_receipts + freight_bookings, voucher_payments + voucher_linkeds, advance_payments,
 * invoices + invoice_payments, payment_ledgers, branches, leads.
 * Lookup/system tables (countries, roles, sessions, jobs, etc.) are intentionally skipped.
 *
 * Env:
 * - LOAD_TEST_ROW_COUNT (default 100000 — 1 lakh per main chain table)
 * - LOAD_TEST_BATCH_SIZE (default 2000, capped at 2000)
 * - LOAD_TEST_COMPANY_ID (required unless a company row exists)
 */
class MassLoadTestSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $targetCount = max((int) env('LOAD_TEST_ROW_COUNT', 100_000), 1);
        $requestedBatch = max((int) env('LOAD_TEST_BATCH_SIZE', 2000), 1);
        $batchSize = min($requestedBatch, 2000);

        if ($requestedBatch !== $batchSize && $this->command) {
            $this->command->warn("LOAD_TEST_BATCH_SIZE {$requestedBatch} capped to {$batchSize}.");
        }

        $companyId = (int) env('LOAD_TEST_COMPANY_ID', 0);
        if ($companyId <= 0) {
            $companyId = (int) Company::query()->value('id');
        }
        if ($companyId <= 0) {
            throw new RuntimeException('No company found. Seed companies first or set LOAD_TEST_COMPANY_ID.');
        }

        $city = City::query()->with(['state.country'])->first();
        if ($city === null) {
            throw new RuntimeException('No city row found; run geo seeders first.');
        }

        $cityId = (int) $city->id;
        $stateId = (int) ($city->state_id ?? $city->state?->id ?? 0);
        $countryId = (int) ($city->country_id ?? $city->state?->country_id ?? 0);

        $userId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 0);
        $truckType = (string) (DB::table('truck_types')->orderBy('id')->value('name') ?? 'General');

        $runToken = now()->format('YmdHis');
        $now = now();

        $nextVoucher = (int) DB::table('freight_bookings')->max('voucher_number');

        $inserted = 0;
        while ($inserted < $targetCount) {
            $chunk = min($batchSize, $targetCount - $inserted);
            $baseSeq = $inserted + 1;

            $this->seedChunk(
                $companyId,
                $cityId,
                $stateId,
                $countryId,
                $userId,
                $truckType,
                $runToken,
                $now,
                $baseSeq,
                $chunk,
                $nextVoucher,
            );

            $inserted += $chunk;

            if ($this->command) {
                $this->command->info("Mass load progress: {$inserted}/{$targetCount}");
            }
        }
    }

    private function seedChunk(
        int $companyId,
        int $cityId,
        int $stateId,
        int $countryId,
        int $userId,
        string $truckType,
        string $runToken,
        \Illuminate\Support\Carbon $now,
        int $baseSeq,
        int $chunk,
        int &$nextVoucher,
    ): void {
        DB::transaction(function () use (
            $companyId,
            $cityId,
            $stateId,
            $countryId,
            $userId,
            $truckType,
            $runToken,
            $now,
            $baseSeq,
            $chunk,
            &$nextVoucher,
        ): void {
            $masterEmails = [];
            $mastersPayload = [];

            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $email = "masslt_{$runToken}_{$seq}@example.test";
                $masterEmails[] = $email;

                $mastersPayload[] = [
                    'name' => "Mass Load Transporter {$seq}",
                    'email' => $email,
                    'pan_number' => PanGenerator::forSequence($seq, 'tm_'.$runToken),
                    'gst_number' => null,
                    'gst_type' => null,
                    'pan_active' => true,
                    'address' => "Mass load address {$seq}",
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

            DB::table('transporters')->insert($transportersPayload);

            $transporterIds = DB::table('transporters')
                ->whereIn('transporter_master_id', $orderedMasterIds)
                ->orderBy('transporter_master_id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $consigneePans = [];
            $consigneesPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $pan = PanGenerator::forSequence($seq + 50_000_000, 'cg_'.$runToken);
                $consigneePans[] = $pan;

                $consigneesPayload[] = [
                    'company_id' => $companyId,
                    'name' => "Mass Load Consignee {$seq}",
                    'address' => "Mass load consignee address {$seq}",
                    'contact_number' => sprintf('98%08d', $seq % 100_000_000),
                    'gst_number' => null,
                    'pan_number' => $pan,
                    'email' => "masscg_{$runToken}_{$seq}@example.test",
                    'city_id' => $cityId > 0 ? $cityId : null,
                    'state_id' => $stateId > 0 ? $stateId : null,
                    'country_id' => $countryId > 0 ? $countryId : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('consignees')->insert($consigneesPayload);

            $consigneeRows = DB::table('consignees')
                ->where('company_id', $companyId)
                ->whereIn('pan_number', $consigneePans)
                ->get(['id', 'pan_number']);

            $consigneeIdByPan = [];
            foreach ($consigneeRows as $row) {
                $consigneeIdByPan[(string) $row->pan_number] = (int) $row->id;
            }

            $consigneeIds = [];
            foreach ($consigneePans as $pan) {
                $consigneeIds[] = $consigneeIdByPan[$pan];
            }

            $driverLicenses = [];
            $driversPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $license = sprintf('DL-ML-%s-%012d', $runToken, $seq);
                $driverLicenses[] = $license;

                $driversPayload[] = [
                    'company_id' => $companyId,
                    'name' => "Mass Load Driver {$seq}",
                    'mobile' => sprintf('91%09d', $seq % 1_000_000_000),
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

            $truckNumbers = [];
            $trucksPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $truckNo = sprintf('MH-ML-%s-%012d', $runToken, $seq);
                $truckNumbers[] = $truckNo;

                $trucksPayload[] = [
                    'transporter_id' => $transporterIds[$i],
                    'company_id' => $companyId,
                    'truck_number' => $truckNo,
                    'truck_type' => $truckType,
                    'driver_id' => $driverIds[$i],
                    'registration_date' => $now->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('trucks')->insert($trucksPayload);

            $truckRows = DB::table('trucks')
                ->where('company_id', $companyId)
                ->whereIn('truck_number', $truckNumbers)
                ->orderBy('truck_number')
                ->get(['id', 'truck_number']);

            $truckIdByNumber = [];
            foreach ($truckRows as $row) {
                $truckIdByNumber[(string) $row->truck_number] = (int) $row->id;
            }

            $truckIds = [];
            foreach ($truckNumbers as $truckNo) {
                $truckIds[] = $truckIdByNumber[$truckNo];
            }

            $lrNumbers = [];
            $lrPayload = [];

            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $lrNumber = sprintf('LR%s%012d', $runToken, $seq);
                $lrNumbers[] = $lrNumber;

                $tid = $transporterIds[$i];
                $cid = $consigneeIds[$i];

                $lrPayload[] = [
                    'company_id' => $companyId,
                    'gr_date' => $now->toDateString(),
                    'lr_number' => $lrNumber,
                    'gr_number' => sprintf('GR%s%012d', $runToken, $seq),
                    'lane' => 'Road',
                    'truck_id' => $truckIds[$i],
                    'delivery_status' => 'Pending',
                    'transporter_id' => $tid,
                    'reference_by' => $tid,
                    'consignor_id' => $cid,
                    'customer_id' => $cid,
                    'consignor_contact_number' => '9999999999',
                    'consignee_id' => $cid,
                    'consignee_contact_number' => '9999999998',
                    'source_city' => 'Source',
                    'destination_city' => 'Destination',
                    'source_state' => 'State A',
                    'destination_state' => 'State B',
                    'unloading_station' => null,
                    'weight' => 10.0,
                    'unloading_weight' => 10.0,
                    'no_of_units' => 1,
                    'good_type' => 'Goods',
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

            DB::table('lorry_receipts')->insert($lrPayload);

            $lrRows = DB::table('lorry_receipts')
                ->where('company_id', $companyId)
                ->whereIn('lr_number', $lrNumbers)
                ->orderBy('lr_number')
                ->get(['id', 'lr_number']);

            $lrIdByNumber = [];
            foreach ($lrRows as $row) {
                $lrIdByNumber[$row->lr_number] = (int) $row->id;
            }

            $orderedLrIds = [];
            foreach ($lrNumbers as $lrNumber) {
                $orderedLrIds[] = $lrIdByNumber[$lrNumber];
            }

            $fbPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $nextVoucher++;
                $seq = $baseSeq + $i;
                $tid = $transporterIds[$i];
                $lrId = $orderedLrIds[$i];

                $fbPayload[] = [
                    'company_id' => $companyId,
                    'transporter_id' => $tid,
                    'transporter_name' => 'Mass Load',
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
                    'good_type' => 'Goods',
                    'detention' => null,
                    'deviation' => null,
                    'misc_expense' => null,
                    'remark' => "mass seed {$seq}",
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

            foreach ($orderedLrIds as $lrId) {
                DB::table('lorry_receipts')->where('id', $lrId)->update([
                    'freight_booking_id' => $fbIdByLrId[$lrId],
                    'updated_at' => $now,
                ]);
            }

            $fbIdsOrdered = [];
            foreach ($orderedLrIds as $lrId) {
                $fbIdsOrdered[] = $fbIdByLrId[$lrId];
            }

            $fbIdListSql = implode(',', array_map(static fn ($id): int => (int) $id, $fbIdsOrdered));

            $fbsForVp = DB::table('freight_bookings')
                ->whereIn('id', $fbIdsOrdered)
                ->orderByRaw('FIELD(id,'.$fbIdListSql.')')
                ->get(['id', 'voucher_number']);

            $vpPayload = [];
            foreach ($fbsForVp as $fb) {
                $vpPayload[] = [
                    'date' => $now->toDateString(),
                    'pump_id' => null,
                    'company_id' => $companyId,
                    'voucher_number' => (string) $fb->voucher_number,
                    'payment_type' => 'advance',
                    'payment_type_id' => 1,
                    'payment_mode' => 'cash',
                    'payment_mode_id' => 7,
                    'amount' => 100.0,
                    'reference_transaction_id' => null,
                    'remark' => 'mass seed',
                    'payment_status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                    'freight_booking_id' => (int) $fb->id,
                    'deleted_at' => null,
                    'linked_voucher_id' => null,
                    'created_by' => $userId > 0 ? $userId : null,
                    'updated_by' => $userId > 0 ? $userId : null,
                ];
            }

            DB::table('voucher_payments')->insert($vpPayload);

            $vpRows = DB::table('voucher_payments')
                ->whereIn('freight_booking_id', $fbIdsOrdered)
                ->orderByRaw('FIELD(freight_booking_id,'.$fbIdListSql.')')
                ->get(['id', 'freight_booking_id']);

            $vlPayload = [];
            foreach ($vpRows as $vp) {
                $vlPayload[] = [
                    'date' => $now->toDateString(),
                    'voucher_ids' => json_encode([(int) $vp->id]),
                    'pump_id' => null,
                    'company_id' => $companyId,
                    'payment_type' => 'advance',
                    'payment_type_id' => 1,
                    'payment_mode' => 'cash',
                    'payment_mode_id' => 7,
                    'amount' => 100.0,
                    'reference_transaction_id' => null,
                    'remark' => 'mass seed',
                    'payment_status' => 'pending',
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'freight_booking_id' => (int) $vp->freight_booking_id,
                    'created_by' => $userId > 0 ? $userId : null,
                    'updated_by' => $userId > 0 ? $userId : null,
                ];
            }

            DB::table('voucher_linkeds')->insert($vlPayload);

            $advancePayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $advancePayload[] = [
                    'company_id' => $companyId,
                    'transporter_id' => $transporterIds[$i],
                    'advance_amount' => 0,
                    'payment_date' => $now->toDateString(),
                    'remark' => "mass seed {$seq}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('advance_payments')->insert($advancePayload);

            $invoiceNumbers = [];
            $invoicePayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $invoiceNumbers[] = sprintf('ML-%s-%012d', $runToken, $seq);
                $invoicePayload[] = [
                    'invoice_number' => sprintf('ML-%s-%012d', $runToken, $seq),
                    'manual_invoice_number' => null,
                    'company_id' => $companyId,
                    'invoice_type' => 'Forward',
                    'custom_invoice' => null,
                    'generated_date' => $now->toDateString(),
                    'customer_id' => $consigneeIds[$i],
                    'total_amount' => 1000.0,
                    'base_amount' => 1000.0,
                    'sgst' => null,
                    'cgst' => null,
                    'igst' => null,
                    'net_amount' => null,
                    'payment_status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                    'memo_ids' => null,
                    'lr_ids' => null,
                    'created_by' => $userId > 0 ? $userId : null,
                    'updated_by' => $userId > 0 ? $userId : null,
                    'deleted_at' => null,
                ];
            }

            DB::table('invoices')->insert($invoicePayload);

            $invoiceRows = DB::table('invoices')
                ->where('company_id', $companyId)
                ->whereIn('invoice_number', $invoiceNumbers)
                ->orderBy('invoice_number')
                ->get(['id', 'invoice_number']);

            $invoiceIdByNumber = [];
            foreach ($invoiceRows as $row) {
                $invoiceIdByNumber[(string) $row->invoice_number] = (int) $row->id;
            }

            $invoicePaymentsPayload = [];
            foreach ($invoiceNumbers as $invNum) {
                $invoicePaymentsPayload[] = [
                    'invoice_id' => $invoiceIdByNumber[$invNum],
                    'payment_date' => $now->toDateString(),
                    'mode' => 'cash',
                    'amount' => 100.0,
                    'remark' => 'mass seed',
                    'created_by' => $userId > 0 ? $userId : null,
                    'updated_by' => $userId > 0 ? $userId : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('invoice_payments')->insert($invoicePaymentsPayload);

            $ledgerPayload = [];
            $branchPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $ledgerPayload[] = [
                    'company_id' => $companyId,
                    'razorpay_order_id' => 'ord_'.substr(hash('sha256', $runToken.'|'.$seq), 0, 48),
                    'razorpay_payment_id' => null,
                    'razorpay_signature' => null,
                    'payment_type' => 'renewal',
                    'truck_count' => 1,
                    'lr_credits' => 0,
                    'addon_truck_count' => null,
                    'addon_lr_credits' => null,
                    'tenure' => 1,
                    'truck_cost' => 0,
                    'lr_credits_cost' => 0,
                    'annual_maintenance_cost' => 0,
                    'subtotal' => 100.0,
                    'promo_code_id' => null,
                    'promo_discount' => 0,
                    'gst_amount' => 0,
                    'total_amount' => 100.0,
                    'status' => 'completed',
                    'razorpay_response' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $branchPayload[] = [
                    'company_id' => $companyId,
                    'branch_name' => 'Mass Branch '.$seq,
                    'branch_address' => 'Mass branch address '.$seq,
                    'branch_contact_number' => substr(hash('sha256', $runToken.'|'.$seq), 0, 20),
                    'head_office' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            DB::table('payment_ledgers')->insert($ledgerPayload);
            DB::table('branches')->insert($branchPayload);

            $leadsPayload = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $baseSeq + $i;
                $leadsPayload[] = [
                    'name' => "Mass Lead {$seq}",
                    'mobile' => sprintf('97%08d', $seq % 100_000_000),
                    'email' => "masslead_{$runToken}_{$seq}@example.test",
                    'company_name' => "Mass Lead Co {$seq}",
                    'business_type' => 'Transport',
                    'gst_number' => null,
                    'city' => 'Test City',
                    'status' => 'LEAD_CREATED',
                    'rejection_reason' => null,
                    'accepted_terms_at' => null,
                    'source' => 'manual',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('leads')->insert($leadsPayload);
        });
    }
}
