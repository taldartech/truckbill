<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TruckOwner extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'address',
        'pan_number',
        'gst_number',
        'gst_type',
        'is_owner',
        'declaration_given',
        'total_trucks',
        'advance_amount',
        'pan_active',
    ];

    protected $casts = [
        'is_owner' => 'boolean',
        'declaration_given' => 'boolean',
        'pan_active' => 'boolean',
    ];

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(TruckOwnerBankAccount::class);
    }

    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    public function scopeFilter($query, $filters)
    {
        return $query
            ->when($filters['name'] ?? false, fn($query, $name) => 
                $query->where('name', 'like', '%' . $name . '%'))
            ->when($filters['gst_number'] ?? false, fn($query, $gst_number) => 
                $query->where('gst_number', 'like', '%' . $gst_number . '%'))
            ->when($filters['gst_type'] ?? false, fn($query, $gst_type) => 
                $query->where('gst_type', $gst_type))
            ->when($filters['pan_number'] ?? false, fn($query, $pan_number) => 
                $query->where('pan_number', 'like', '%' . $pan_number . '%'))
            ->when($filters['is_owner'] ?? false, fn($query, $is_owner) => 
                $query->where('is_owner', $is_owner))
            ->when($filters['company_id'] ?? false, fn($query, $company_id) => 
                $query->where('company_id', $company_id));
    }
}
