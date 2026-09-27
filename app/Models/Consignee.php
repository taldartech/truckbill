<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consignee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'city_id',
        'state_id',
        'country_id',
        'contact_number',
        'gst_number',
        'pan_number',
        'email',
        'deleted_by',
        'deleted_remark',
    ];

    protected $casts = [
        'city_name' => 'string',
        'state_name' => 'string',
        'country_name' => 'string',
    ];

    protected $dates = ['deleted_at'];

    protected $table = 'consignees';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function displayNameWithCity(): string
    {
        $name = trim((string) ($this->name ?: 'Not Set'));
        $city = trim((string) ($this->city_name ?? $this->city?->name ?? ''));

        if ($city === '') {
            return $name;
        }

        return $name.' ('.$city.')';
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function lorryReceipts()
    {
        return $this->hasMany(LorryReceipt::class, 'customer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function portalUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
