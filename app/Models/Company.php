<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'short_name',
        'gst_number',
        'pan_number',
        'gst_scheme',
        'gst_type',
        'address',
        'contact_person',
        'contact_number',
        'country_id',
        'state_id',
        'city_id',
        'pin_code',
        'template_jurisdictional_boundary',
        'template_header',
        'template_name',
        'template_address',
        'template_note',
        'bank',
        'account_number',
        'ifsc',
        'user_id',
        'is_blocked',
        'company_type',
        'company_logo',
        'invoice_template',
        'lr_copy_template',
        'gst_slab',
        'plan_end_date',
        'lr_credits',
        'truck_credits',
    ];

    protected $dates = ['deleted_at'];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (empty($company->short_name) && ! empty($company->name)) {
                $base = self::generateShortNameFromCompanyName($company->name);
                $company->short_name = self::ensureUniqueShortName($base);
            }
        });
    }

    /**
     * Generate a short company code from the provided name.
     * Uses initials from the first two words (alphabetic), otherwise falls back to first 5 letters.
     */
    public static function generateShortNameFromCompanyName(?string $companyName): string
    {
        $name = trim((string) $companyName);
        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = [];

        foreach ($words as $word) {
            if (count($initials) >= 2) {
                break;
            }

            if (preg_match('/[a-zA-Z]/', $word, $matches) === 1) {
                $initials[] = strtoupper($matches[0]);
            }
        }

        if (count($initials) >= 2) {
            return implode('', $initials);
        }

        $lettersOnly = preg_replace('/[^a-zA-Z]/', '', $name) ?: '';

        return strtoupper(substr($lettersOnly, 0, 5));
    }

    private static function ensureUniqueShortName(string $base, ?int $exceptCompanyId = null): string
    {
        if ($base === '') {
            return '';
        }

        $candidate = $base;
        $suffix = 1;
        while (self::shortNameIsTaken($candidate, $exceptCompanyId)) {
            $candidate = $base.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private static function shortNameIsTaken(string $shortName, ?int $exceptCompanyId = null): bool
    {
        return Company::withTrashed()
            ->where('short_name', $shortName)
            ->when($exceptCompanyId !== null, fn ($query) => $query->where('id', '!=', $exceptCompanyId))
            ->exists();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Public URL for the uploaded logo (file lives on the public disk under storage/app/public).
     */
    public function logoPublicUrl(): ?string
    {
        if (empty($this->company_logo)) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->company_logo)) {
            return null;
        }

        return Storage::disk('public')->url($this->company_logo);
    }

    /**
     * Absolute path for DomPDF / local image embedding (does not rely on the public/storage symlink).
     */
    public function logoFilesystemPathForPdf(): ?string
    {
        if (empty($this->company_logo)) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->company_logo)) {
            return null;
        }

        return Storage::disk('public')->path($this->company_logo);
    }
}
