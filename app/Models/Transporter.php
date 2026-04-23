<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transporter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transporter_master_id',
        'company_id',
        'is_owner',
        'contact_person_name',
        'mobile_number',
        'declaration_given',
        'total_trucks',
        'advance_amount',
        'short_name',
        'beneficiary_id',
        'tds_percentage',
        'tds_adjustment_remark',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'declaration_given' => 'boolean',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->name ?? '');
    }

    protected function gstNumber(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->gst_number);
    }

    protected function panNumber(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->pan_number);
    }

    protected function panActive(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->pan_active ?? false);
    }

    protected function address(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->address);
    }

    protected function cityId(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->city_id);
    }

    protected function stateId(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->state_id);
    }

    protected function countryId(): Attribute
    {
        return Attribute::get(fn () => $this->transporterMaster?->country_id);
    }

    public function getCityAttribute()
    {
        return $this->transporterMaster?->city;
    }

    public function getStateAttribute()
    {
        return $this->transporterMaster?->state;
    }

    public function getCountryAttribute()
    {
        return $this->transporterMaster?->country;
    }

    public function transporterMaster(): BelongsTo
    {
        return $this->belongsTo(TransporterMaster::class, 'transporter_master_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lorryReceipts(): HasMany
    {
        return $this->hasMany(LorryReceipt::class, 'transporter_id');
    }

    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class, 'transporter_id');
    }
}
