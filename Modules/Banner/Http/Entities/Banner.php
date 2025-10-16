<?php

namespace Modules\Banner\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Banner\Database\Factories\BannerFactory;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'image',
        'type',
        'is_active'
    ];

    protected static function newFactory(): BannerFactory
    {
        return BannerFactory::new();
    }
}
