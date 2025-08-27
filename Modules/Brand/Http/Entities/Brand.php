<?php

namespace Modules\Brand\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Brand\Database\Factories\BrandFactory;

class Brand extends Model
{
    use HasFactory,SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brands';

    protected $fillable = ['name','image','is_active','sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    public static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }
}
