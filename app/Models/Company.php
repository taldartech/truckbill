<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use SoftDeletes;

    public const DUMMY_PAN_TDS_ADJUSTMENT_REMARK = 'PAN not provided. A dummy PAN is currently used. The responsibility for collecting the correct PAN details and ensuring correct TDS compliance rests solely with the company/user entering the data.';

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
        'bank',
        'account_number',
        'ifsc',
        'user_id',
        'is_blocked',
        'company_type',
        'company_logo',
        'gst_slab',
    ];

    protected $dates = ['deleted_at'];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (empty($company->short_name) && ! empty($company->name)) {
                $base = self::generateShortNameFromCompanyName($company->name);
                $company->short_name = self::ensureUniqueShortName($base);
            }

            if (empty($company->dummy_pan_number)) {
                $company->dummy_pan_number = self::generateUniqueDummyPanNumber();
            }
        });

        static::created(function (Company $company): void {
            $company->setting()->firstOrCreate(
                ['company_id' => $company->id],
                CompanySetting::defaultsFor($company)
            );
        });
    }

    /**
     * Generate a short company code from the provided name.
     * Multiple words: first alphabetic letter of each word, up to 5 characters.
     * Single word: first 5 letters of the name (letters only).
     */
    public static function generateShortNameFromCompanyName(?string $companyName): string
    {
        $name = trim((string) $companyName);
        if ($name === '') {
            return '';
        }

        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lettersOnly = preg_replace('/[^a-zA-Z]/', '', $name) ?: '';

        if (count($words) <= 1) {
            return strtoupper(substr($lettersOnly, 0, 5));
        }

        $initials = '';
        foreach ($words as $word) {
            if (strlen($initials) >= 5) {
                break;
            }

            if (preg_match('/[a-zA-Z]/', $word, $matches) === 1) {
                $initials .= strtoupper($matches[0]);
            }
        }

        if ($initials !== '') {
            return $initials;
        }

        return strtoupper(substr($lettersOnly, 0, 5));
    }

    /**
     * Resolve a globally unique company short code: try $base, then $base01, $base02, and so on.
     */
    public static function ensureUniqueShortName(string $base, ?int $exceptCompanyId = null): string
    {
        if ($base === '') {
            return '';
        }

        $candidate = $base;
        $attempt = 0;
        while (self::shortNameIsTaken($candidate, $exceptCompanyId)) {
            $attempt++;
            $suffix = strlen((string) $attempt) < 3
                ? str_pad((string) $attempt, 2, '0', STR_PAD_LEFT)
                : (string) $attempt;
            $candidate = $base.$suffix;
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

    public function setting(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function subscription()
    {
        return $this->hasOne(CompanySubscription::class);
    }

    public function pendingUpcomingRenewals()
    {
        return $this->hasMany(UpcomingSubscription::class)->where('status', 'pending');
    }

    public function hasPendingUpcomingRenewal(): bool
    {
        return UpcomingSubscription::query()
            ->where('company_id', $this->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function isPlanActive(): bool
    {
        return $this->subscription?->isActive() ?? false;
    }

    public function subscriptionAccessPayload(): array
    {
        return app(\App\Services\SubscriptionAccessService::class)->accessPayloadForCompany($this);
    }

    public function subscriptionCanWrite(): bool
    {
        return $this->subscriptionAccessPayload()['can_write'] ?? false;
    }

    public function subscriptionCanExport(): bool
    {
        return $this->subscriptionAccessPayload()['can_export'] ?? false;
    }

    public function subscriptionCanPrint(): bool
    {
        return $this->subscriptionAccessPayload()['can_print'] ?? false;
    }

    public function planEndsOn(): ?\Illuminate\Support\Carbon
    {
        return $this->subscription?->ends_on;
    }

    public function lrCreditsRemaining(): int
    {
        return (int) ($this->subscription?->lr_credits_remaining ?? 0);
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

    /**
     * Stored company-scoped dummy PAN for transporter/truck-owner records without a real PAN.
     *
     * Requirements:
     * - 10 characters to satisfy existing `pan_number` column sizing.
     * - Intentionally fails the real PAN regex (first char is a digit).
     * - Unique across companies (DB unique index on companies.dummy_pan_number).
     */
    public static function dummyTransporterTruckOwnerPanNumber(int $companyId): string
    {
        $company = self::withTrashed()->find($companyId);
        if (! $company) {
            throw new \InvalidArgumentException("Company [{$companyId}] was not found.");
        }

        if (blank($company->dummy_pan_number)) {
            $company->forceFill([
                'dummy_pan_number' => self::generateUniqueDummyPanNumber(),
            ])->saveQuietly();
        }

        return strtoupper((string) $company->dummy_pan_number);
    }

    /**
     * Generate a unique 10-char dummy PAN that cannot match real PAN format.
     */
    public static function generateUniqueDummyPanNumber(?int $exceptCompanyId = null): string
    {
        do {
            $candidate = '0'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 9));
        } while (self::dummyPanNumberIsTaken($candidate, $exceptCompanyId));

        return $candidate;
    }

    private static function dummyPanNumberIsTaken(string $dummyPanNumber, ?int $exceptCompanyId = null): bool
    {
        return self::withTrashed()
            ->where('dummy_pan_number', $dummyPanNumber)
            ->when($exceptCompanyId !== null, fn ($query) => $query->where('id', '!=', $exceptCompanyId))
            ->exists();
    }
}
