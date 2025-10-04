<?php

namespace Modules\User\Http\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Balance\Http\Entities\Balance;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\Review;
use Modules\PromoCode\Http\Entities\PromoCode;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Entities\Basket;

trait UserRelationTrait
{
    public function favorites()
    {
        return $this->belongsToMany(
            Product::class,
            'user_favorites',
            'user_id',
            'product_id'
        )->withTimestamps();
    }

    public function cartItems()
    {
        return $this->belongsToMany(
            Product::class,
            'user_carts',
            'user_id',
            'product_id'
        )
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function basket(): HasMany
    {
        return $this->hasMany(Basket::class);
    }

    public function balance(): HasMany
    {
        return $this->hasMany(Balance::class, 'user_id', 'id');
    }
    public function usedPromoCodes()
    {
        return $this->belongsToMany(
            PromoCode::class,
            'used_promo_codes',
            'user_id',
            'promo_code_id'
        )->withTimestamps()->withPivot('id');
    }

}
