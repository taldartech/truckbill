<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_role_id',
        'company_id',
        'branch_id',
        'transporter_master_id',
        'session_token',
        'referral_code',
        'is_blocked',
        'block_reason',
        'blocked_by',
    ];

    public function transporterMaster()
    {
        return $this->belongsTo(TransporterMaster::class, 'transporter_master_id');
    }

    public function consignees(): BelongsToMany
    {
        return $this->belongsToMany(Consignee::class)->withTimestamps();
    }

    public function allowedConsigneeIds(): array
    {
        return $this->consignees()->pluck('consignees.id')->all();
    }

    public function portalConsignee(): ?Consignee
    {
        $consigneeId = session('portal_consignee_id');
        if ($consigneeId === null) {
            return null;
        }

        return $this->consignees()->whereKey($consigneeId)->first();
    }

    /**
     * Effective company_id: for super_admin with null company_id, use session-selected company.
     */
    public function getCompanyIdAttribute($value): ?int
    {
        $raw = $this->attributes['company_id'] ?? null;
        if ($raw === null && session()->has('super_admin_company_id')) {
            $roleName = $this->userRole?->name
                ?? \Illuminate\Support\Facades\DB::table('user_roles')->where('id', $this->attributes['user_role_id'] ?? 0)->value('name');
            if ($roleName === 'super_admin') {
                return (int) session('super_admin_company_id');
            }
        }

        return $raw !== null ? (int) $raw : null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function ownedCompanies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Company::class, 'user_id');
    }

    /**
     * Whether the user can access the company panel (operational menus: LR, FB, consignees, etc.).
     * True for admin, moderator, or super_admin when a company is selected (session).
     */
    public function canAccessCompanyPanel(): bool
    {
        $name = $this->userRole?->name;
        if (in_array($name, ['admin', 'moderator'])) {
            return true;
        }
        if ($name === 'super_admin' && session()->has('super_admin_company_id')) {
            return true;
        }

        return false;
    }

    /**
     * Whether the user can act as admin in the current context (edit/delete/action buttons).
     * True for role admin, or for super_admin when a company is selected (session).
     */
    public function canActAsAdmin(): bool
    {
        $name = $this->userRole?->name;
        if ($name === 'admin') {
            return true;
        }
        if ($name === 'super_admin' && session()->has('super_admin_company_id')) {
            return true;
        }
        if ($name === 'master_admin') {
            return true;
        }

        return false;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function userRole()
    {
        return $this->belongsTo(UserRole::class, 'user_role_id');
    }

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }
}
