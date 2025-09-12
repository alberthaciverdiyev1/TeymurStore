<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Color\Http\Transformers\ColorResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Size\Http\Transformers\SizeResource;
use Modules\User\Http\Resources\AddressResource;
use Modules\User\Http\UserResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'statuses' => $this->whenLoaded('statuses', function () {
                return $this->statuses->map(function ($status) {
                    return [
                        'id'         => $status->id,
                        'status'     => $status->status->label(), // enum label
                        'created_at' => $status->created_at,
                    ];
                });
            }),
        ]);
    }
}
