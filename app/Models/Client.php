<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'sma_companies';

    public $timestamps = false;

    protected $casts = [
        'vip'              => 'integer',
        'vvip'             => 'integer',
        'fade_delete'      => 'integer',
        'award_points'     => 'float',
        'sum_total'        => 'float',
        'last_buy_grand_total' => 'float',
        'buy_total_this_month' => 'float',
        'follow_up_tracking'   => 'integer',
        'dealing'          => 'integer',
    ];

    public function scopeCustomers($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('group_name')->orWhere('group_name', '!=', 'supplier');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('fade_delete', '!=', 1);
    }
}
