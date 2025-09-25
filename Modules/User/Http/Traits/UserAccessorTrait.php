<?php

namespace Modules\User\Http\Traits;

use Illuminate\Support\Facades\DB;

trait UserAccessorTrait
{
    public function getTotalBalanceAttribute(): float
    {
        return $this->balance()
            ->sum(DB::raw("
            CASE
                WHEN type IN ('deposit','refund','bonus') THEN amount
                ELSE -amount
            END
        "));
    }
}
