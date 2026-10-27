<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs bulk load across major operational domains (~LOAD_TEST_ROW_COUNT each).
 *
 * 1. MassLoadTestSeeder — agencies, consignees, drivers, trucks, LR, freight bookings,
 *    advance payments, invoices, leads (+ voucher payments, invoice payments, payment_ledgers, branches).
 * 2. TruckOwnerTruckLoadTestSeeder — truck owners (is_owner=1) + trucks (same row count).
 * 3. MassLoadAuxiliaryTablesSeeder — good_types, pumps, promo_codes, email_*, reminders, audit_*, etc.
 *
 * Env (same as child seeders):
 * - LOAD_TEST_ROW_COUNT (default 100000 — 1 lakh agency chain + related rows)
 * - LOAD_TEST_TRUCK_COUNT (defaults to LOAD_TEST_ROW_COUNT if unset — 1 lakh owners + trucks)
 * - LOAD_TEST_BATCH_SIZE
 * - LOAD_TEST_COMPANY_ID
 *
 * One command for ~1 lakh rows per main table (agency + truck-owner paths):
 * php artisan db:seed --class=MassLoadAllMainTablesSeeder --no-interaction
 */
class MassLoadAllMainTablesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Starting MassLoadTestSeeder (main operational chain)...');
        $this->call(MassLoadTestSeeder::class);

        $this->command?->info('Starting TruckOwnerTruckLoadTestSeeder (owners + trucks)...');
        $this->call(TruckOwnerTruckLoadTestSeeder::class);

        $this->command?->info('Starting MassLoadAuxiliaryTablesSeeder (remaining operational tables)...');
        $this->call(MassLoadAuxiliaryTablesSeeder::class);

        $this->command?->info('Mass load of main tables finished.');
    }
}
