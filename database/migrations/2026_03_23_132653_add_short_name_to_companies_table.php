<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'short_name')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('short_name')->nullable()->after('name');
            });
        }

        $this->backfillUniqueCompanyShortNames();

        if (! $this->companiesShortNameUniqueIndexExists()) {
            Schema::table('companies', function (Blueprint $table) {
                $table->unique('short_name');
            });
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('short_name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['short_name']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }

    private function backfillUniqueCompanyShortNames(): void
    {
        $used = [];

        Company::query()->orderBy('id')->chunkById(100, function ($companies) use (&$used): void {
            foreach ($companies as $company) {
                $raw = trim((string) ($company->short_name ?? ''));
                $base = $raw !== ''
                    ? strtoupper($raw)
                    : Company::generateShortNameFromCompanyName($company->name);

                if ($base === '') {
                    $base = 'C'.$company->id;
                }

                $candidate = $base;
                $suffix = 1;
                while (isset($used[$candidate])) {
                    $candidate = $base.$suffix;
                    $suffix++;
                }

                $used[$candidate] = true;

                if ($company->short_name !== $candidate) {
                    $company->update(['short_name' => $candidate]);
                }
            }
        });
    }

    private function companiesShortNameUniqueIndexExists(): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            return collect(DB::select('SHOW INDEX FROM companies WHERE Key_name = ?', ['companies_short_name_unique']))->isNotEmpty();
        }

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA index_list(companies)') as $index) {
                if (($index->name ?? '') === 'companies_short_name_unique') {
                    return true;
                }
            }
        }

        return false;
    }
};
