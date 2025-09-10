<?php

namespace Modules\User\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $table = 'user_addresses';
    protected $guarded = [];

}
