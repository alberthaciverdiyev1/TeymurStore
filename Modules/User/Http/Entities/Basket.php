<?php

namespace Modules\User\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Http\Entities\Product;

class Basket extends Model
{
    protected $table = 'baskets';
    protected $guarded = [];

    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
