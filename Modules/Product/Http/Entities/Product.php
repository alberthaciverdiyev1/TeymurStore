<?php

namespace Modules\Product\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Brand\Http\Entities\Brand;
use Modules\Category\Http\Entities\Category;
use Modules\Color\Http\Entities\Color;
use Modules\Product\Database\Factories\ProductFactory;
use Modules\Size\Http\Entities\Size;
use Modules\User\Http\Entities\User;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations, HasFactory, SoftDeletes;

    protected $table = 'products';

    public array $translatable = ['title', 'description'];

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
    ];

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class, 'color_product');
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_size');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
    public function getFallbackLocale() : string
    {
        return config('product.fallback_locale', 'az');
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(Model::class, 'product_id', 'id')
            ->setTable('product_reviews');
    }
}
