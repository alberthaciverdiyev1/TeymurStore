<?php

namespace Modules\User\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;

class BasketResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'product_id' => $this->product_id,
            'quantity'   => $this->quantity,
            'color_id'   => $this->color_id,
            'size_id'    => $this->size_id,
            'gender'     => $this->gender,
            'selected'   => $this->selected,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            'product' => $this->whenLoaded('product') ? new ProductResource($this->product) : null,
        ];
    }
}
