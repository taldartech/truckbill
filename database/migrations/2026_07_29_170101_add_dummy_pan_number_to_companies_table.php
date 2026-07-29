<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('dummy_pan_number', 10)->nullable()->after('pan_number');
        });

        $this->backfillUniqueDummyPanNumbers();

        Schema::table('companies', function (Blueprint $table): void {
            $table->unique('dummy_pan_number');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropUnique(['dummy_pan_number']);
            $table->dropColumn('dummy_pan_number');
        });
    }

    private function backfillUniqueDummyPanNumbers(): void
    {
        $used = [];

        Company::withTrashed()->orderBy('id')->chunkById(100, function ($companies) use (&$used): void {
            foreach ($companies as $company) {
                $candidate = $this->legacyDeterministicDummyPan((int) $company->id);

                if (isset($used[$candidate])) {
                    $candidate = $this->generateUnusedDummyPan($used);
                }

                $used[$candidate] = true;

                $company->forceFill(['dummy_pan_number' => $candidate])->saveQuietly();
            }
        });
    }

    /**
     * Preserve the previous hash-based value so already-created dummy transporter masters keep matching.
     */
    private function legacyDeterministicDummyPan(int $companyId): string
    {
        return '0'.strtoupper(substr(sha1('truckbill_dummy_pan_'.$companyId), 0, 9));
    }

    /**
     * @param  array<string, true>  $used
     */
    private function generateUnusedDummyPan(array &$used): string
    {
        do {
            $candidate = '0'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 9));
        } while (isset($used[$candidate]));

        return $candidate;
    }
};
