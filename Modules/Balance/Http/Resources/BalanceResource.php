<?php

namespace Modules\Balance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return parent::toArray($request);
    }

}
