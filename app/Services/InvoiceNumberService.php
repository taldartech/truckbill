<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceNumberService
{
    public function generate(Company $company, string $generatedDate): string
    {
        $date = Carbon::parse($generatedDate);
        $year = $date->format('Y');
        $code = $this->resolveCompanyCode($company);
        $prefix = sprintf('%s/%s/', $code, $year);

        // Lock company row to serialize sequence generation per company.
        Company::query()
            ->whereKey($company->id)
            ->lockForUpdate()
            ->first();

        $lastInvoice = Invoice::query()
            ->where('company_id', $company->id)
            ->whereYear('generated_date', $year)
            ->whereNotNull('invoice_number')
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $nextSequence = 1;
        if ($lastInvoice && preg_match('/\/(\d{4,})$/', (string) $lastInvoice->invoice_number, $matches) === 1) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextSequence);
    }

    private function resolveCompanyCode(Company $company): string
    {
        $shortName = strtoupper((string) ($company->short_name ?? ''));
        $shortName = preg_replace('/[^A-Z0-9]/', '', $shortName) ?? '';

        if ($shortName !== '') {
            return $shortName;
        }

        $name = strtoupper((string) ($company->name ?? ''));
        $fallback = preg_replace('/[^A-Z0-9]/', '', $name) ?? '';

        return $fallback !== '' ? substr($fallback, 0, 5) : 'COMP';
    }
}
