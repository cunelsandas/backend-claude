<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Maps to the legacy `hr_employee` table.
 *
 * Carries identity fields (username, plain-text password, position, names, photo)
 * and the all-important `sma_user` ID used by the legacy permission system.
 */
class Employee extends Model
{
    protected $table = 'hr_employee';

    public $timestamps = false;

    protected $fillable = [
        'sma_user',
        'employee_username',
        'employee_password',
        'employee_position',
        'employee_team',
        'employee_type',
        'employee_fnmt',
        'employee_lnmt',
        'employee_fnme',
        'employee_lnme',
        'employee_photo',
        'employee_title',
        'status',
    ];

    protected $hidden = [
        'employee_password',
    ];

    protected $casts = [
        'sma_user'          => 'integer',
        'employee_position' => 'integer',
        'employee_team'     => 'integer',
        'status'            => 'integer',
    ];

    public function backendUser(): HasOne
    {
        return $this->hasOne(User::class, 'employee', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
