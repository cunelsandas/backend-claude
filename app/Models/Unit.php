<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $table = 'sma_units';

    public $timestamps = false;

    protected $casts = [
        'base_unit'        => 'integer',
        'unit_value'       => 'float',
        'operation_value'  => 'float',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit');
    }

    public function scopeBaseUnits($query)
    {
        return $query->whereNull('base_unit');
    }
}
