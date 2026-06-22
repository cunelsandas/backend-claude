<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'sma_products';

    public $timestamps = false;

    protected $casts = [
        'price'            => 'float',
        'cost'             => 'float',
        'status'           => 'integer',
        'tester'           => 'integer',
        'product_promotion'=> 'integer',
        'product_reward'   => 'integer',
        'category_id'      => 'integer',
        'subcategory_id'   => 'integer',
        'brand'            => 'integer',
    ];

    public function brandModel(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'subcategory_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit');
    }

    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'sale_unit');
    }
}
