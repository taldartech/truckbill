<?php

namespace Tests\Feature\Console;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\CompanyReminderLog;
use App\Models\Consignee;
use App\Models\EwayBillReminderLog;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminderLog;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use App\Console\Commands\BackupDatabaseToS3;
use App\Services\AccountingPostingService;
use App\Services\Notifications\CompanyLifecycleNotificationService;
use App\Services\Notifications\EwayBillNotificationService;
use App\Services\Notifications\InvoiceNotificationService;
use App\Services\SubscriptionCheckoutService;
use App\Services\TransporterTdsCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        if (! \Illuminate\Support\Facades\Schema::hasColumn('companies', 'lr_credits')) {
            \Illuminate\Support\Facades\Schema::table('companies', function ($table) {
                $table->integer('lr_credits')->nullable();
                $table->date('plan_end_date')->nullable();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('freight_bookings', 'eway_bill_number')) {
            \Illuminate\Support\Facades\Schema::table('freight_bookings', function ($table) {
                $table->string('eway_bill_number')->nullable();
                $table->date('eway_bill_expiry')->nullable();
            });
        }
    }

    protected function seedRoles(): void
    {
        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
        UserRole::firstOrCreate(['id' => 4], ['name' => 'moderator']);
        UserRole::firstOrCreate(['id' => 5], ['name' => 'accountant']);
    }

    protected function makeCompanyUser(string $name = 'Test Company'): array
    {
        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => $name,
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 50,
            'is_blocked' => false,
            'plan_end_date' => now()->addDays(15)->toDateString(),
        ]);

        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        return [$company, $admin];
    }

    // ==========================================
    // 1. ApplyUpcomingSubscriptions
    // ==========================================

    public function test_apply_upcoming_subscriptions_command(): void
    {
        $mockCheckout = Mockery::mock(SubscriptionCheckoutService::class);
        $mockCheckout->shouldReceive('applyDueUpcomingSubscriptions')->once()->andReturn(2);
        $this->app->instance(SubscriptionCheckoutService::class, $mockCheckout);

        $this->artisan('subscription:apply-upcoming')
            ->expectsOutput('Applied 2 upcoming subscription(s).')
            ->assertSuccessful();
    }

    // ==========================================
    // 2. BackfillAccountingLedgersCommand
    // ==========================================

    public function test_backfill_accounting_ledgers_when_no_companies(): void
    {
        $this->artisan('accounting:backfill', ['--company' => 999999])
            ->expectsOutput('No companies found to backfill.')
            ->assertSuccessful();
    }

    public function test_backfill_accounting_ledgers_success_with_company(): void
    {
        [$company, $admin] = $this->makeCompanyUser('Backfill Co');

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Backfill Customer',
        ]);

        $transporter = Transporter::factory()->forCompany($company->id)->create();
        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1111',
            'truck_type' => 'Flatbed',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-BF-1',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Goods',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 80,
            'tr_amount' => 800,
            'advance_amount' => 0,
            'balance_amount' => 800,
            'tds_amount' => 0,
            'company_id' => $company->id,
            'voucher_number' => 8888,
        ]);

        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 7777,
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 300,
            'payment_mode' => 'cash',
        ]);

        AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 500,
            'payment_mode' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-BF-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);

        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'mode' => 'cash',
        ]);

        $this->artisan('accounting:backfill', ['--company' => $company->id])
            ->assertSuccessful()
            ->expectsOutputToContain('Chart of accounts & default ledgers provisioned.')
            ->expectsOutputToContain('Accounting backfill completed successfully!');
    }

    public function test_backfill_accounting_ledgers_when_no_companies_found(): void
    {
        $this->artisan('accounting:backfill', ['--company' => 999999])
            ->expectsOutput('No companies found to backfill.')
            ->assertSuccessful();
    }

    public function test_backfill_accounting_ledgers_handles_exceptions_during_posting(): void
    {
        [$company] = $this->makeCompanyUser('Error Backfill Co');

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Error Customer',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-ERR-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);

        $mockPosting = Mockery::mock(AccountingPostingService::class);
        $mockPosting->shouldReceive('postInvoice')
            ->andThrow(new \RuntimeException('Posting failed on test invoice'));
        $this->app->instance(AccountingPostingService::class, $mockPosting);

        $this->artisan('accounting:backfill', ['--company' => $company->id])
            ->assertSuccessful()
            ->expectsOutputToContain('Error posting Invoice #' . $invoice->id . ': Posting failed on test invoice');
    }

    public function test_backfill_accounting_ledgers_handles_exceptions_during_fb_vp_advance_posting(): void
    {
        [$company] = $this->makeCompanyUser('Error Booking Co');
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Error Booking Customer',
        ]);
        $transporter = Transporter::factory()->forCompany($company->id)->create();
        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-ERR-1',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'transporter_id' => $transporter->id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Goods',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
        ]);
        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 10,
            'tr_amount' => 100,
            'balance_amount' => 100,
        ]);
        $vp = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 50,
            'date' => now()->toDateString(),
            'payment_type' => 'advance',
            'payment_mode' => 'cash',
        ]);
        $adv = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 50,
            'payment_date' => now()->toDateString(),
        ]);

        // Also add a booking without transporter and a VP without transporter to cover skipped branches
        $lrNoTrans = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-ERR-2',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Goods',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
        ]);
        $bookingNoTrans = FreightBooking::create([
            'lr_id' => $lrNoTrans->id,
            'company_id' => $company->id,
            'transporter_id' => null,
            'tr_fr_rate' => 10,
            'tr_amount' => 100,
            'balance_amount' => 100,
        ]);
        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $bookingNoTrans->id,
            'amount' => 20,
            'date' => now()->toDateString(),
            'payment_type' => 'advance',
            'payment_mode' => 'cash',
        ]);

        $mockPosting = Mockery::mock(AccountingPostingService::class);
        $mockPosting->shouldReceive('provisionForCompany');
        $mockPosting->shouldReceive('postFreightBooking')->andThrow(new \RuntimeException('FB error'));
        $mockPosting->shouldReceive('postVoucherPayment')->andThrow(new \RuntimeException('VP error'));
        $mockPosting->shouldReceive('postAdvancePayment')->andThrow(new \RuntimeException('Adv error'));
        $this->app->instance(AccountingPostingService::class, $mockPosting);

        $this->artisan('accounting:backfill', ['--company' => $company->id])
            ->assertSuccessful()
            ->expectsOutputToContain('Error posting Freight Booking')
            ->expectsOutputToContain('Error posting Voucher Payment')
            ->expectsOutputToContain('Error posting Advance Payment');
    }

    // ==========================================
    // 3. BackupDatabaseToS3
    // ==========================================

    public function test_backup_database_to_s3_handles_non_mysql_cleanly(): void
    {
        $this->artisan('database:backup-to-s3')
            ->expectsOutput('Database backup failed. Check logs for details.')
            ->assertFailed();
    }

    public function test_backup_database_to_s3_success_and_upload_flow(): void
    {
        Storage::fake('s3');
        $fakeBinDir = storage_path('app/testing-bin');
        File::ensureDirectoryExists($fakeBinDir);
        $batPath = $fakeBinDir . DIRECTORY_SEPARATOR . 'mysqldump.bat';
        File::put($batPath, '@php -d "xdebug.mode=off" -r "foreach ($argv as $a) { if (str_starts_with($a, \'--result-file=\')) { file_put_contents(substr($a, 14), \'-- MOCK SQL DUMP\'); } }" -- %*');

        $origPath = getenv('PATH');
        $origDefault = config('database.default');
        putenv('PATH=' . $fakeBinDir . PATH_SEPARATOR . $origPath);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'truckbill_test',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database_backup.disk' => 's3',
            'database_backup.path_prefix' => 'database-backups',
            'database_backup.retention_hours' => 24,
        ]);

        try {
            $this->artisan('database:backup-to-s3')
                ->expectsOutputToContain('Database backup uploaded to [s3:database-backups/db-backup-')
                ->assertSuccessful();
        } finally {
            putenv('PATH=' . $origPath);
            config(['database.default' => $origDefault]);
            File::deleteDirectory($fakeBinDir);
        }
    }

    public function test_backup_database_to_s3_handles_incomplete_database_config(): void
    {
        $origDefault = config('database.default');
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => '',
            'database.connections.mysql.username' => '',
        ]);

        try {
            $this->artisan('database:backup-to-s3')
                ->expectsOutput('Database backup failed. Check logs for details.')
                ->assertFailed();
        } finally {
            config(['database.default' => $origDefault]);
        }
    }

    public function test_backup_database_to_s3_prune_old_backups_logic(): void
    {
        Storage::fake('s3');
        $storage = Storage::disk('s3');

        // Old sql file
        $storage->put('database-backups/old-backup.sql', 'DUMP OLD');
        touch($storage->path('database-backups/old-backup.sql'), now()->subHours(30)->getTimestamp());

        // Recent sql file
        $storage->put('database-backups/recent-backup.sql', 'DUMP RECENT');

        // Non-sql file
        $storage->put('database-backups/notes.txt', 'NOT A SQL');

        $command = new BackupDatabaseToS3();
        $method = new \ReflectionMethod(BackupDatabaseToS3::class, 'pruneOldBackups');
        $method->setAccessible(true);

        $deletedCount = $method->invoke($command, 's3', 'database-backups', 24);

        $this->assertSame(1, $deletedCount);
        $storage->assertMissing('database-backups/old-backup.sql');
        $storage->assertExists('database-backups/recent-backup.sql');
        $storage->assertExists('database-backups/notes.txt');
    }

    public function test_backup_database_to_s3_handles_dump_process_failure(): void
    {
        Storage::fake('s3');
        $fakeBinDir = storage_path('app/testing-bin-fail');
        File::ensureDirectoryExists($fakeBinDir);
        $batPath = $fakeBinDir . DIRECTORY_SEPARATOR . 'mysqldump.bat';
        File::put($batPath, '@php -d "xdebug.mode=off" -r "fwrite(STDERR, \"dump failed error\"); exit(1);"');

        $origPath = getenv('PATH');
        $origDefault = config('database.default');
        putenv('PATH=' . $fakeBinDir . PATH_SEPARATOR . $origPath);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'truckbill_test',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
            'database_backup.disk' => 's3',
        ]);

        try {
            $this->artisan('database:backup-to-s3')
                ->expectsOutput('Database backup failed. Check logs for details.')
                ->assertFailed();
        } finally {
            putenv('PATH=' . $origPath);
            config(['database.default' => $origDefault]);
            File::deleteDirectory($fakeBinDir);
        }
    }

    // ==========================================
    // 4. MergeTransporterMasters
    // ==========================================

    public function test_merge_transporter_masters_fails_when_keep_master_not_found(): void
    {
        $this->artisan('transporter-masters:merge', ['keep-id' => 999999])
            ->expectsOutput('Transporter master #999999 not found.')
            ->assertFailed();
    }

    public function test_merge_transporter_masters_when_no_duplicates(): void
    {
        $keep = TransporterMaster::create([
            'name' => 'Original Master',
            'pan_number' => 'ABCDE1234F',
        ]);

        $this->artisan('transporter-masters:merge', ['keep-id' => $keep->id])
            ->expectsOutput('No duplicate transporter masters found to merge.')
            ->assertSuccessful();
    }

    public function test_merge_transporter_masters_dry_run_and_apply(): void
    {
        [$company] = $this->makeCompanyUser();

        $keep = TransporterMaster::create([
            'name' => 'Canonical Master',
            'pan_number' => 'ABCDE1234F',
            'mobile_number' => '9876543210',
        ]);

        $duplicate = TransporterMaster::create([
            'name' => 'Duplicate Master',
            'pan_number' => 'ABCDE1234F',
            'mobile_number' => '9876543210',
        ]);

        $transporter = Transporter::create([
            'company_id' => $company->id,
            'name' => 'Transporter Under Dup',
            'transporter_master_id' => $duplicate->id,
            'mobile_number' => '9876543210',
            'pan_number' => 'ABCDE1234F',
        ]);

        // 1. Dry run
        $this->artisan('transporter-masters:merge', [
            'keep-id' => $keep->id,
            '--merge-ids' => (string) $duplicate->id,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Merge plan (keeping master #'.$keep->id.'):')
            ->expectsOutputToContain('Dry run only');

        $this->assertSame($duplicate->id, $transporter->fresh()->transporter_master_id);

        // 2. Apply
        $this->artisan('transporter-masters:merge', [
            'keep-id' => $keep->id,
            '--merge-ids' => (string) $duplicate->id,
            '--apply' => true,
        ])
            ->expectsConfirmation('Apply this merge?', 'yes')
            ->assertSuccessful()
            ->expectsOutputToContain('Merge completed successfully.');

        $this->assertSame($keep->id, $transporter->fresh()->transporter_master_id);
        $this->assertNull(TransporterMaster::find($duplicate->id));
    }

    public function test_merge_transporter_masters_reassigns_records_when_existing_transporter_present(): void
    {
        [$company] = $this->makeCompanyUser();

        $keep = TransporterMaster::create([
            'name' => 'Primary Master',
            'pan_number' => 'ABCDE1111F',
        ]);
        $existingTransporter = Transporter::create([
            'company_id' => $company->id,
            'name' => 'Existing Transporter',
            'transporter_master_id' => $keep->id,
            'is_owner' => false,
        ]);

        $duplicate = TransporterMaster::create([
            'name' => 'Primary Master',
            'pan_number' => 'ABCDE1111F',
        ]);
        $dupTransporter = Transporter::create([
            'company_id' => $company->id,
            'name' => 'Duplicate Transporter',
            'transporter_master_id' => $duplicate->id,
            'is_owner' => false,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Merge Cust',
        ]);
        $truck = Truck::create([
            'transporter_id' => $dupTransporter->id,
            'truck_number' => 'MH12AB9999',
            'truck_type' => 'Trailer',
        ]);
        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-MG-1',
            'delivery_status' => 'Pending',
            'invoice_generated' => 0,
            'truck_id' => $truck->id,
            'transporter_id' => $dupTransporter->id,
            'source_city' => 'X',
            'source_state' => 'Y',
            'destination_city' => 'Z',
            'destination_state' => 'W',
            'lane' => 'Lane',
            'good_type' => 'Goods',
            'weight' => 5,
            'fr_rate' => 100,
            'amount' => 500,
            'gr_date' => now()->toDateString(),
        ]);
        $fb = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $dupTransporter->id,
            'tr_fr_rate' => 80,
            'tr_amount' => 400,
            'advance_amount' => 0,
            'balance_amount' => 400,
            'tds_amount' => 0,
            'company_id' => $company->id,
        ]);
        $adv = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $dupTransporter->id,
            'advance_amount' => 100,
            'payment_mode' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->artisan('transporter-masters:merge', [
            'keep-id' => $keep->id,
            '--apply' => true,
        ])
            ->expectsConfirmation('Apply this merge?', 'yes')
            ->assertSuccessful()
            ->expectsOutputToContain('Merge completed successfully.');

        $this->assertSame($existingTransporter->id, $lr->fresh()->transporter_id);
        $this->assertSame($existingTransporter->id, $truck->fresh()->transporter_id);
        $this->assertSame($existingTransporter->id, $fb->fresh()->transporter_id);
        $this->assertSame($existingTransporter->id, $adv->fresh()->transporter_id);
        $this->assertNull(Transporter::find($dupTransporter->id));
        $this->assertNull(TransporterMaster::find($duplicate->id));
    }

    public function test_merge_transporter_masters_portal_users_move_and_collision_conflict(): void
    {
        $keep = TransporterMaster::create([
            'name' => 'Keep With User',
            'pan_number' => 'PAN000001A',
        ]);
        $duplicate1 = TransporterMaster::create([
            'name' => 'Dup With User 1',
            'pan_number' => 'PAN000002B',
        ]);
        $userDup = User::factory()->create([
            'email' => 'dup-user@example.com',
            'transporter_master_id' => $duplicate1->id,
        ]);

        // Case A: keep has no user -> dup user moved to keep
        $this->artisan('transporter-masters:merge', [
            'keep-id' => $keep->id,
            '--merge-ids' => (string) $duplicate1->id,
            '--apply' => true,
        ])
            ->expectsConfirmation('Apply this merge?', 'yes')
            ->assertSuccessful();

        $this->assertSame($keep->id, $userDup->fresh()->transporter_master_id);

        // Case B: now keep has a user ($userDup). A second duplicate also has a user -> conflict blocks merge
        $duplicate2 = TransporterMaster::create([
            'name' => 'Dup With User 2',
            'pan_number' => 'PAN000003C',
        ]);
        User::factory()->create([
            'email' => 'dup2-user@example.com',
            'transporter_master_id' => $duplicate2->id,
        ]);

        $this->artisan('transporter-masters:merge', [
            'keep-id' => $keep->id,
            '--merge-ids' => (string) $duplicate2->id,
            '--apply' => true,
        ])
            ->expectsConfirmation('Apply this merge?', 'yes')
            ->assertFailed()
            ->expectsOutputToContain('both masters have portal users');
    }

    public function test_merge_transporter_masters_cancelled_by_user(): void
    {
        $keep = TransporterMaster::create([
            'name' => 'Cancel Test Keep',
            'pan_number' => 'CAN111111A',
        ]);
        $duplicate = TransporterMaster::create([
            'name' => 'Cancel Test Dup',
            'pan_number' => 'CAN222222B',
        ]);

        $this->artisan('transporter-masters:merge', [
            'keep-id' => $keep->id,
            '--merge-ids' => (string) $duplicate->id,
            '--apply' => true,
        ])
            ->expectsConfirmation('Apply this merge?', 'no')
            ->assertFailed()
            ->expectsOutputToContain('Merge cancelled.');
    }

    public function test_merge_transporter_masters_when_duplicate_has_no_actions(): void
    {
        $refProp = new \ReflectionProperty(TransporterMaster::class, 'resolvedCollectionClasses');
        $refProp->setAccessible(true);
        $orig = $refProp->getValue();
        $refProp->setValue(null, array_merge($orig, [
            TransporterMaster::class => TestEmptyActionsCollection::class,
        ]));

        try {
            $keep = TransporterMaster::create(['name' => 'Empty Action Keep', 'pan_number' => 'EAK111111A']);
            $duplicate = TransporterMaster::create(['name' => 'Empty Action Dup', 'pan_number' => 'EAD222222B']);

            $this->artisan('transporter-masters:merge', [
                'keep-id' => $keep->id,
                '--merge-ids' => (string) $duplicate->id,
            ])
                ->assertSuccessful()
                ->expectsOutput('Nothing to do — duplicate masters have no linked data.');
        } finally {
            $refProp->setValue(null, $orig);
        }
    }

    // ==========================================
    // 5. ResetTruckOwnerDeclarations
    // ==========================================

    public function test_reset_truck_owner_declarations_blocked_without_force(): void
    {
        Carbon::setTestNow('2026-06-15');

        $this->artisan('truck-owners:reset-declarations')
            ->expectsOutputToContain('This command should only be run on April 1st')
            ->assertFailed();

        Carbon::setTestNow();
    }

    public function test_reset_truck_owner_declarations_cancelled_by_user(): void
    {
        Carbon::setTestNow('2026-06-15');

        $this->artisan('truck-owners:reset-declarations', ['--force' => true, '--apply' => true])
            ->expectsConfirmation('⚠️  WARNING: This will reset all truck owner declarations and update TDS! Do you want to continue?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    public function test_reset_truck_owner_declarations_on_april_first_without_force_and_skips_when_no_declaration(): void
    {
        Carbon::setTestNow('2026-04-01');
        [$company] = $this->makeCompanyUser();

        $master = TransporterMaster::create([
            'name' => 'Owner April Master',
            'pan_number' => 'APR112233D',
        ]);

        // Owner already has declaration_given = false
        $ownerNoDecl = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'name' => 'Owner Without Declaration',
            'is_owner' => true,
            'declaration_given' => false,
            'tds_percentage' => 1,
            'pan_number' => 'APR112233D',
        ]);

        $this->artisan('truck-owners:reset-declarations', ['--apply' => true])
            ->expectsConfirmation('⚠️  WARNING: This will reset all truck owner declarations and update TDS! Do you want to continue?', 'yes')
            ->assertSuccessful()
            ->expectsOutputToContain('Skipped (already not given): 1');

        Carbon::setTestNow();
    }

    public function test_reset_truck_owner_declarations_with_force_and_apply(): void
    {
        Carbon::setTestNow('2026-06-15');
        [$company] = $this->makeCompanyUser();

        $master = TransporterMaster::create([
            'name' => 'Owner Master',
            'pan_number' => 'AABBC1122D',
            'mobile_number' => '9888877777',
        ]);

        $owner = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'name' => 'Truck Owner',
            'is_owner' => true,
            'declaration_given' => true,
            'declaration_date' => '2026-01-01',
            'tds_percentage' => 0,
            'total_trucks' => 2,
            'mobile_number' => '9888877777',
            'pan_number' => 'AABBC1122D',
        ]);

        // Dry run with force
        $this->artisan('truck-owners:reset-declarations', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Reset Truck Owner Declarations')
            ->expectsOutputToContain('DRY RUN');

        $this->assertTrue($owner->fresh()->declaration_given);

        // Live run with force and apply
        $this->artisan('truck-owners:reset-declarations', [
            '--force' => true,
            '--apply' => true,
        ])
            ->expectsConfirmation('⚠️  WARNING: This will reset all truck owner declarations and update TDS! Do you want to continue?', 'yes')
            ->assertSuccessful()
            ->expectsOutputToContain('Changes have been applied to the database.');

        $fresh = $owner->fresh();
        $this->assertFalse($fresh->declaration_given);

        Carbon::setTestNow();
    }

    public function test_reset_truck_owner_declarations_handles_calculator_exception(): void
    {
        Carbon::setTestNow('2026-06-15');
        [$company] = $this->makeCompanyUser('Calc Err Co');
        $master = TransporterMaster::create([
            'name' => 'Err Master',
            'pan_number' => 'ERR112233M',
        ]);
        Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'name' => 'Err Owner',
            'is_owner' => true,
            'declaration_given' => true,
        ]);
        $mockCalc = Mockery::mock(TransporterTdsCalculator::class);
        $mockCalc->shouldReceive('calculate')->andThrow(new \RuntimeException('TDS calculation failed'));
        $this->app->instance(TransporterTdsCalculator::class, $mockCalc);

        $this->artisan('truck-owners:reset-declarations', ['--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('ERROR for truck owner')
            ->expectsOutputToContain('Errors: 1');
        Carbon::setTestNow();
    }

    public function test_reset_truck_owner_declarations_fatal_error(): void
    {
        Carbon::setTestNow('2026-06-15');
        \Illuminate\Support\Facades\Schema::dropIfExists('transporters');
        try {
            $this->artisan('truck-owners:reset-declarations', ['--force' => true])
                ->assertFailed()
                ->expectsOutputToContain('FATAL ERROR:');
        } finally {
            $this->artisan('migrate');
            Carbon::setTestNow();
        }
    }

    // ==========================================
    // 6. SendCompanyCreditLowReminders
    // ==========================================

    public function test_send_company_credit_low_reminders(): void
    {
        [$company] = $this->makeCompanyUser('Low Credit Co');
        $company->forceFill(['lr_credits' => 15])->save();

        $mockNotification = Mockery::mock(CompanyLifecycleNotificationService::class);
        $mockNotification->shouldReceive('notifyCreditLow')
            ->once()
            ->with(Mockery::on(fn ($c) => (int) $c->id === (int) $company->id), 25, 15);
        $this->app->instance(CompanyLifecycleNotificationService::class, $mockNotification);

        $this->artisan('company:send-credit-low-reminders', ['--thresholds' => '25'])
            ->expectsOutput('Company credit low reminders sent: 1')
            ->assertSuccessful();

        $this->assertTrue(CompanyReminderLog::query()
            ->where('company_id', $company->id)
            ->where('reminder_key', 'credit_low_lt_25')
            ->exists());

        // Second run on same day should skip due to alreadySent
        $this->artisan('company:send-credit-low-reminders', ['--thresholds' => '25'])
            ->expectsOutput('Company credit low reminders sent: 0')
            ->assertSuccessful();
    }

    public function test_send_company_credit_low_reminders_default_thresholds_when_empty(): void
    {
        [$company] = $this->makeCompanyUser('Empty Thresholds Co');
        $company->forceFill(['lr_credits' => 20])->save();

        $mockNotification = Mockery::mock(CompanyLifecycleNotificationService::class);
        $mockNotification->shouldReceive('notifyCreditLow')
            ->atLeast()->once();
        $this->app->instance(CompanyLifecycleNotificationService::class, $mockNotification);

        // Passing empty/non-positive threshold triggers fallback to default [100, 50, 25]
        $this->artisan('company:send-credit-low-reminders', ['--thresholds' => '0'])
            ->assertSuccessful();
    }

    // ==========================================
    // 7. SendCompanyPlanLifecycleReminders
    // ==========================================

    public function test_send_company_plan_lifecycle_reminders(): void
    {
        Carbon::setTestNow('2026-06-01');

        [$coExpiring] = $this->makeCompanyUser('Expiring Co');
        $coExpiring->forceFill(['plan_end_date' => '2026-06-16'])->save(); // 15 days left

        [$coExpired] = $this->makeCompanyUser('Expired Co');
        $coExpired->forceFill(['plan_end_date' => '2026-05-25'])->save(); // Expired in past

        $mockNotification = Mockery::mock(CompanyLifecycleNotificationService::class);
        $mockNotification->shouldReceive('notifyPlanExpiringSoon')
            ->once()
            ->with(Mockery::on(fn ($c) => (int) $c->id === (int) $coExpiring->id), 15);
        $mockNotification->shouldReceive('notifyPlanExpired')
            ->once()
            ->with(Mockery::on(fn ($c) => (int) $c->id === (int) $coExpired->id));
        $this->app->instance(CompanyLifecycleNotificationService::class, $mockNotification);

        $this->artisan('company:send-plan-lifecycle-reminders', ['--expiring-days' => '15'])
            ->expectsOutput('Company lifecycle reminders sent: 2')
            ->assertSuccessful();

        $this->assertTrue(CompanyReminderLog::query()
            ->where('company_id', $coExpiring->id)
            ->where('reminder_key', 'plan_expiring_15d')
            ->exists());

        $this->assertTrue(CompanyReminderLog::query()
            ->where('company_id', $coExpired->id)
            ->where('reminder_key', 'plan_expired')
            ->exists());

        // Second run on same day should skip due to alreadySent
        $this->artisan('company:send-plan-lifecycle-reminders', ['--expiring-days' => '15'])
            ->expectsOutput('Company lifecycle reminders sent: 0')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    public function test_send_company_plan_lifecycle_reminders_default_offsets_when_empty(): void
    {
        Carbon::setTestNow('2026-06-01');
        [$company] = $this->makeCompanyUser('Default Days Co');
        $company->forceFill(['plan_end_date' => '2026-07-01'])->save(); // 30 days left

        $mockNotification = Mockery::mock(CompanyLifecycleNotificationService::class);
        $mockNotification->shouldReceive('notifyPlanExpiringSoon')
            ->atLeast()->once();
        $this->app->instance(CompanyLifecycleNotificationService::class, $mockNotification);

        // Passing empty/zero offsets falls back to [30, 15, 10]
        $this->artisan('company:send-plan-lifecycle-reminders', ['--expiring-days' => '0'])
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    // ==========================================
    // 8. SendEwayExpiryReminders
    // ==========================================

    public function test_send_eway_expiry_reminders(): void
    {
        Carbon::setTestNow('2026-06-01');
        [$company] = $this->makeCompanyUser('Eway Co');

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Eway Customer',
        ]);
        $transporter = Transporter::factory()->forCompany($company->id)->create();
        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB2222',
            'truck_type' => 'Flatbed',
        ]);
        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-EW-1',
            'delivery_status' => 'Pending',
            'invoice_generated' => 0,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Goods',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => '2026-06-01',
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 80,
            'tr_amount' => 800,
            'advance_amount' => 0,
            'balance_amount' => 800,
            'tds_amount' => 0,
            'company_id' => $company->id,
            'voucher_number' => 7771,
        ]);
        $booking->forceFill([
            'eway_bill_number' => 'EWAY-1234567890',
            'eway_bill_expiry' => '2026-06-03', // 2 days from today
            'is_delivered' => 0,
            'is_cancelled' => 0,
        ])->save();

        $mockNotification = Mockery::mock(EwayBillNotificationService::class);
        $mockNotification->shouldReceive('notifyExpiry')
            ->once()
            ->with(Mockery::on(fn ($b) => (int) $b->id === (int) $booking->id), 2);
        $this->app->instance(EwayBillNotificationService::class, $mockNotification);

        $this->artisan('eway:send-expiry-reminders', ['--days' => 2])
            ->expectsOutput('E-Way expiry reminders sent: 1')
            ->assertSuccessful();

        $this->assertTrue(EwayBillReminderLog::query()
            ->where('freight_booking_id', $booking->id)
            ->where('reminder_key', 'expiry_in_2_days')
            ->exists());

        // Second run on same day should skip due to alreadySent
        $this->artisan('eway:send-expiry-reminders', ['--days' => 2])
            ->expectsOutput('E-Way expiry reminders sent: 0')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    // ==========================================
    // 9. SendInvoiceDueReminders
    // ==========================================

    public function test_send_invoice_due_reminders(): void
    {
        Carbon::setTestNow('2026-06-10');
        [$company] = $this->makeCompanyUser('Invoice Due Co');

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Due Customer',
        ]);

        // generated_date + 7 days = due date
        // For due_in_3_days reminder on 2026-06-10: targetDueDate = 2026-06-13
        // So targetGeneratedDate = 2026-06-13 - 7 days = 2026-06-06
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DUE-1',
            'invoice_type' => 'Forward',
            'generated_date' => '2026-06-06',
            'total_amount' => 5000,
            'net_amount' => 5000,
            'payment_status' => 'pending',
        ]);

        $mockNotification = Mockery::mock(InvoiceNotificationService::class);
        $mockNotification->shouldReceive('notifyDueReminder')
            ->once()
            ->with(Mockery::on(fn ($inv) => (int) $inv->id === (int) $invoice->id), 3);
        $this->app->instance(InvoiceNotificationService::class, $mockNotification);

        $this->artisan('invoice:send-due-reminders', ['--days' => '3', '--due-days' => 7])
            ->expectsOutput('Invoice due reminders sent: 1')
            ->assertSuccessful();

        $this->assertTrue(InvoiceReminderLog::query()
            ->where('invoice_id', $invoice->id)
            ->where('reminder_key', 'due_in_3_days')
            ->exists());

        // Second run on same day should skip due to alreadySent
        $this->artisan('invoice:send-due-reminders', ['--days' => '3', '--due-days' => 7])
            ->expectsOutput('Invoice due reminders sent: 0')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    public function test_send_invoice_due_reminders_default_due_days_and_skips_zero_remaining(): void
    {
        Carbon::setTestNow('2026-06-10');
        [$company] = $this->makeCompanyUser('Zero Remaining Due Co');

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Zero Due Customer',
        ]);

        // Fully paid invoice (remaining_amount <= 0.01)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DUE-PAID',
            'invoice_type' => 'Forward',
            'generated_date' => '2026-06-06',
            'total_amount' => 0,
            'net_amount' => 0,
            'payment_status' => 'pending',
        ]);

        $mockNotification = Mockery::mock(InvoiceNotificationService::class);
        $mockNotification->shouldNotReceive('notifyDueReminder');
        $this->app->instance(InvoiceNotificationService::class, $mockNotification);

        // --due-days=0 falls back to 7, empty days falls back to [3, 1]
        $this->artisan('invoice:send-due-reminders', ['--due-days' => 0])
            ->expectsOutput('Invoice due reminders sent: 0')
            ->assertSuccessful();

        Carbon::setTestNow();
    }
}

class TestEmptyActionsCollection extends \Illuminate\Database\Eloquent\Collection
{
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([]);
    }
}

