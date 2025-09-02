<?php

namespace Modules\User\Http;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request):array
    {
        return parent::toArray($request);
    }
}
