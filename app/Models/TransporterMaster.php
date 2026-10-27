<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransporterMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'pan_number',
        'gst_number',
        'gst_type',
        'is_msme',
        'msme_reg_no',
        'pan_active',
        'address',
        'city_id',
        'state_id',
        'country_id',
    ];

    protected function casts(): array
    {
        return [
            'pan_active' => 'boolean',
            'is_msme' => 'boolean',
        ];
    }

    public function transporters(): HasMany
    {
        return $this->hasMany(Transporter::class, 'transporter_master_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $trimmed = trim((string) $email);

        return $trimmed !== '' ? $trimmed : null;
    }

    public function hasNotificationEmail(): bool
    {
        return filled($this->email);
    }
}
