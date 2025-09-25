<?php

namespace Modules\User\Http;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request):array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            "surname"=> $this->surname,
            'email' => $this->email,
            "phone"=>  $this->phone,
            'total_balance' => $this->total_balance,
        ];
    }
}
