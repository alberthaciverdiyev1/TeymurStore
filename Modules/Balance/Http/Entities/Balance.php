<?php

namespace Modules\Balance\Http\Entities;

use App\Enums\BalanceType;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Http\Entities\User;

class Balance extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'balances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 'amount', 'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => BalanceType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
