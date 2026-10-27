<?php

namespace App\Console\Commands;

use App\Models\AdvancePayment;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MergeTransporterMasters extends Command
{
    protected $signature = 'transporter-masters:merge
                            {keep-id : ID of the transporter master to keep (correct PAN)}
                            {--merge-ids= : Comma-separated duplicate master IDs to merge into keep-id}
                            {--apply : Apply changes (default is dry run)}';

    protected $description = 'Merge duplicate transporter master records into the canonical record and remove incorrect duplicates';

    public function handle(): int
    {
        $keepId = (int) $this->argument('keep-id');
        $apply = (bool) $this->option('apply');

        $keepMaster = TransporterMaster::query()->find($keepId);

        if ($keepMaster === null) {
            $this->error("Transporter master #{$keepId} not found.");

            return self::FAILURE;
        }

        $duplicateMasters = $this->resolveDuplicateMasters($keepMaster);

        if ($duplicateMasters->isEmpty()) {
            $this->warn('No duplicate transporter masters found to merge.');
            $this->line("Kept record: #{$keepMaster->id} — {$keepMaster->name} (PAN: {$keepMaster->pan_number})");

            return self::SUCCESS;
        }

        $this->info('Merge plan (keeping master #'.$keepMaster->id.'):');
        $this->table(
            ['Role', 'ID', 'Name', 'PAN', 'Email', 'Transporters'],
            collect([$keepMaster])->merge($duplicateMasters)->map(function (TransporterMaster $master) use ($keepMaster): array {
                return [
                    $master->id === $keepMaster->id ? 'KEEP' : 'MERGE',
                    $master->id,
                    $master->name,
                    $master->pan_number,
                    $master->email ?? '-',
                    $master->transporters()->count(),
                ];
            })->all()
        );

        $actions = $this->buildMergeActions($keepMaster, $duplicateMasters);

        if ($actions === []) {
            $this->info('Nothing to do — duplicate masters have no linked data.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->comment('Planned actions:');
        foreach ($actions as $action) {
            $this->line('  '.$action);
        }

        if (! $apply) {
            $this->newLine();
            $this->warn('Dry run only. Re-run with --apply to execute the merge.');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! $this->confirm('Apply this merge?', false)) {
            $this->warn('Merge cancelled.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($keepMaster, $duplicateMasters): void {
                foreach ($duplicateMasters as $duplicateMaster) {
                    $this->mergeMaster($keepMaster, $duplicateMaster);
                }
            });
        } catch (\Throwable $exception) {
            $this->error('Merge failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Merge completed successfully.');
        $this->line("Canonical master: #{$keepMaster->id} — {$keepMaster->name} (PAN: {$keepMaster->pan_number})");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, TransporterMaster>
     */
    private function resolveDuplicateMasters(TransporterMaster $keepMaster): Collection
    {
        $mergeIdsOption = $this->option('merge-ids');

        if (is_string($mergeIdsOption) && trim($mergeIdsOption) !== '') {
            $mergeIds = collect(explode(',', $mergeIdsOption))
                ->map(fn (string $id): int => (int) trim($id))
                ->filter(fn (int $id): bool => $id > 0 && $id !== $keepMaster->id)
                ->unique()
                ->values();

            return TransporterMaster::query()
                ->whereIn('id', $mergeIds)
                ->orderBy('id')
                ->get();
        }

        return TransporterMaster::query()
            ->where('id', '!=', $keepMaster->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($keepMaster->name))])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, TransporterMaster>  $duplicateMasters
     * @return list<string>
     */
    private function buildMergeActions(TransporterMaster $keepMaster, Collection $duplicateMasters): array
    {
        $actions = [];

        foreach ($duplicateMasters as $duplicateMaster) {
            foreach ($duplicateMaster->transporters()->with('company:id,name')->get() as $duplicateTransporter) {
                $existingTransporter = $this->findExistingTransporter(
                    $keepMaster,
                    (int) $duplicateTransporter->company_id,
                    (bool) $duplicateTransporter->is_owner
                );

                $companyName = $duplicateTransporter->company?->name ?? 'Unknown company';
                $role = $duplicateTransporter->is_owner ? 'truck owner' : 'transporter';

                if ($existingTransporter !== null) {
                    $actions[] = "Reassign records from transporter #{$duplicateTransporter->id} to #{$existingTransporter->id} ({$companyName}, {$role}), then permanently delete transporter #{$duplicateTransporter->id}";
                } else {
                    $actions[] = "Move transporter #{$duplicateTransporter->id} ({$companyName}, {$role}) to master #{$keepMaster->id}";
                }
            }

            $actions[] = "Permanently delete transporter master #{$duplicateMaster->id} (PAN: {$duplicateMaster->pan_number})";
        }

        return $actions;
    }

    private function mergeMaster(TransporterMaster $keepMaster, TransporterMaster $duplicateMaster): void
    {
        $duplicateMaster = TransporterMaster::query()->lockForUpdate()->findOrFail($duplicateMaster->id);

        foreach ($duplicateMaster->transporters()->get() as $duplicateTransporter) {
            $duplicateTransporter = Transporter::query()->lockForUpdate()->findOrFail($duplicateTransporter->id);

            $existingTransporter = $this->findExistingTransporter(
                $keepMaster,
                (int) $duplicateTransporter->company_id,
                (bool) $duplicateTransporter->is_owner
            );

            if ($existingTransporter !== null) {
                $this->reassignTransporterRecords($duplicateTransporter, $existingTransporter);
                $duplicateTransporter->forceDelete();
                $this->line("  Permanently deleted duplicate transporter #{$duplicateTransporter->id} (merged into #{$existingTransporter->id})");

                continue;
            }

            $duplicateTransporter->transporter_master_id = $keepMaster->id;
            $duplicateTransporter->save();
            $this->line("  Moved transporter #{$duplicateTransporter->id} to master #{$keepMaster->id}");
        }

        $duplicateMaster->forceDelete();
        $this->line("  Permanently deleted duplicate master #{$duplicateMaster->id}");
    }

    private function findExistingTransporter(
        TransporterMaster $keepMaster,
        int $companyId,
        bool $isOwner
    ): ?Transporter {
        return Transporter::query()
            ->where('transporter_master_id', $keepMaster->id)
            ->where('company_id', $companyId)
            ->where('is_owner', $isOwner)
            ->first();
    }

    private function reassignTransporterRecords(Transporter $from, Transporter $to): void
    {
        LorryReceipt::query()
            ->where('transporter_id', $from->id)
            ->update(['transporter_id' => $to->id]);

        Truck::query()
            ->where('transporter_id', $from->id)
            ->update(['transporter_id' => $to->id]);

        FreightBooking::query()
            ->where('transporter_id', $from->id)
            ->update(['transporter_id' => $to->id]);

        AdvancePayment::query()
            ->where('transporter_id', $from->id)
            ->update(['transporter_id' => $to->id]);
    }
}
