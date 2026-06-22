<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'sma_warehouses';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'name_second',
        'price_group_id',
    ];
}
