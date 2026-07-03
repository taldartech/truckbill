<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_id',
        'template_name',
        'template_address',
        'template_header',
        'template_note',
        'template_jurisdictional_boundary',
        'invoice_template',
        'lr_copy_template',
        'freight_challan_template',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFor(Company $company): array
    {
        return [
            'company_id' => $company->id,
            'template_name' => $company->name,
            'template_address' => $company->address,
            'template_header' => null,
            'template_note' => null,
            'template_jurisdictional_boundary' => null,
            'invoice_template' => 'classic',
            'lr_copy_template' => 'classic',
            'freight_challan_template' => 'classic',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Ensure voucher header fields have fallbacks for PDF preview/export (in-memory only).
     */
    public static function applyPreviewDefaults(Company $company): void
    {
        $company->loadMissing('setting');

        if (! $company->setting) {
            $company->setRelation('setting', new CompanySetting(self::defaultsFor($company)));
        }

        if (blank($company->setting->template_name)) {
            $company->setting->template_name = $company->name;
        }

        if (blank($company->setting->template_address)) {
            $company->setting->template_address = $company->address ?? 'Sample registered office address';
        }
    }
}
