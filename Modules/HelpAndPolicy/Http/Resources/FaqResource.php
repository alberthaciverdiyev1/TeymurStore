<?php

namespace Modules\HelpAndPolicy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return parent::toArray($request);
    }
}
