<?php

namespace App\Console\Commands;

use App\Models\Transporter;
use App\Services\TransporterTdsCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetTruckOwnerDeclarations extends Command
{
    public function __construct(
        private readonly TransporterTdsCalculator $tdsCalculator
    ) {
        parent::__construct();
    }

    protected $signature = 'truck-owners:reset-declarations
                            {--apply : Apply changes to database (default is dry run)}
                            {--force : Force execution even if not April 1st}';

    protected $description = 'Reset all truck owner declarations to "not given" at the start of financial year (April 1st) and recalculate TDS';

    public function handle(): int
    {
        $dryRun = ! $this->option('apply');
        $force = $this->option('force');

        $today = Carbon::now();
        $isAprilFirst = ($today->month === 4 && $today->day === 1);

        if (! $isAprilFirst && ! $force) {
            $this->warn('⚠️  This command should only be run on April 1st (start of financial year).');
            $this->warn('   Current date: '.$today->format('Y-m-d'));
            $this->warn('   Use --force flag to run anyway.');

            return self::FAILURE;
        }

        $this->info('========================================');
        $this->info('Reset Truck Owner Declarations');
        $this->info('========================================');
        $this->info('Mode: '.($dryRun ? 'DRY RUN (no changes will be made)' : 'LIVE (changes will be saved)'));
        $this->info('Date: '.$today->format('Y-m-d'));
        $this->info('========================================');
        $this->newLine();

        if (! $dryRun && $this->input->isInteractive()) {
            if (! $this->confirm('⚠️  WARNING: This will reset all truck owner declarations and update TDS! Do you want to continue?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            $truckOwners = Transporter::where('is_owner', true)->get();
            $totalCount = $truckOwners->count();

            $this->info("Total truck owners to process: {$totalCount}");
            $this->newLine();

            $updated = 0;
            $skipped = 0;
            $errors = 0;

            $bar = $this->output->createProgressBar($totalCount);
            $bar->start();

            foreach ($truckOwners as $truckOwner) {
                try {
                    if (! $truckOwner->declaration_given) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $newTdsPercentage = $this->calculateTdsPercentage($truckOwner);

                    if (! $dryRun) {
                        $truckOwner->declaration_given = false;
                        $truckOwner->tds_percentage = $newTdsPercentage;
                        $truckOwner->tds_adjustment_remark = null;
                        $truckOwner->save();
                    }
                    $updated++;

                    $bar->advance();
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('Error resetting declaration for truck owner', [
                        'truck_owner_id' => $truckOwner->id,
                        'truck_owner_name' => $truckOwner->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->error("ERROR for truck owner ID {$truckOwner->id} ({$truckOwner->name}): ".$e->getMessage());
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('========================================');
            $this->info('Summary:');
            $this->info('========================================');
            $this->info("Total truck owners processed: {$totalCount}");
            $this->info("Updated (declaration reset + TDS): {$updated}");
            $this->info("Skipped (already not given): {$skipped}");
            $this->info("Errors: {$errors}");
            $this->info('========================================');
            $this->newLine();

            if ($dryRun) {
                $this->warn('⚠️  This was a DRY RUN. No changes were made.');
                $this->info('To apply changes, run: php artisan truck-owners:reset-declarations --apply');
            } else {
                $this->info('✅ Changes have been applied to the database.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Fatal error in resetTruckOwnerDeclarations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('❌ FATAL ERROR: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function calculateTdsPercentage(Transporter $truckOwner): float
    {
        return $this->tdsCalculator->calculate(
            false,
            (string) ($truckOwner->pan_number ?? ''),
            (bool) $truckOwner->pan_active
        );
    }
}
