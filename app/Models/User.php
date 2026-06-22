<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Maps to the legacy `backend_user` table.
 *
 * Identity (username/password input) lives on the related `Employee`
 * (`hr_employee`) row. This model only carries the auth state
 * (password_hash, remember_token) and Spatie role/permission assignments.
 */
class User extends Authenticatable
{
    use HasRoles, Notifiable;

    protected $table = 'backend_user';

    public $timestamps = false;

    protected $fillable = [
        'employee',
        'status',
        'sale',
        'admin',
        'packing',
        'root',
        'redirect_url',
        'last_login',
        'last_ip',
        'token',
        'password_hash',
        'remember_token',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'token',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'status'     => 'integer',
        'sale'       => 'integer',
        'admin'      => 'integer',
        'packing'    => 'integer',
        'root'       => 'integer',
        'redirect_url' => 'integer',
    ];

    /**
     * Tell Laravel which column stores the bcrypt hash.
     * Default is `password` — we override because legacy uses `password_hash`.
     */
    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function employeeRecord(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee', 'id');
    }

    /**
     * Convenience accessor: the SMA ID is the primary permission key
     * across the legacy app. Pulled from the linked Employee row.
     */
    public function getSmaIdAttribute(): ?int
    {
        return $this->employeeRecord?->sma_user;
    }

    public function getDisplayNameAttribute(): string
    {
        $e = $this->employeeRecord;
        if (! $e) return 'Unknown';
        return trim(($e->employee_fnme ?: $e->employee_fnmt) . ' ' . ($e->employee_lnme ?: $e->employee_lnmt));
    }
}
