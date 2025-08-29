<?php

namespace Modules\Product\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use SoftDeletes;

    protected $table = 'product_image';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
