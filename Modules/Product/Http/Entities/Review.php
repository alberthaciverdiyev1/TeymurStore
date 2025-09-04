<?php

namespace Modules\Product\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Database\Factories\ReviewFactory;
use Modules\User\Http\Entities\User;

class Review extends Model
{
    use HasFactory;

    protected $table = 'product_reviews';
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }
}
