<?php

namespace Modules\Category\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Database\Factories\CategoryFactory;

class Category extends Model
{
    use HasFactory,SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [

    ];

    public function children()
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public static function newFactory(): CategoryFactory{
        return CategoryFactory::new();
    }
}
