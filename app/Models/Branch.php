<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    protected $fillable = [
        'company_id',
        'branch_name',
        'branch_address',
        'branch_contact_number',
        'head_office',
    ];

    /**
     * Relationship: Branch belongs to a Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
