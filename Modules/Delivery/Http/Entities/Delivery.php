<?php

namespace Modules\Delivery\Http\Entities;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [

    ];
}
