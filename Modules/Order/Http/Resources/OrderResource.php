<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\ProductResource;
use Modules\User\Http\Resources\AddressResource;
use Modules\User\Http\UserResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'transaction_id'  => $this->transaction_id,
            'total_price'     => $this->total_price,
            'discount_price'  => $this->discount_price,
            'shipping_price'  => $this->shipping_price,
            'paid_at'         => $this->paid_at,
            'note'            => $this->note,
            'created_at'      => $this->created_at,
            'user'            => UserResource::make($this->whenLoaded('user')),
            'address'         => AddressResource::make($this->whenLoaded('address')),
            'products'        => ProductResource::collection(
                $this->whenLoaded('items')->pluck('product')->filter()->values()
            ),
            'latest_status'   => $this->latestStatus ? [
                'id'         => $this->latestStatus->id,
                'status'     => $this->latestStatus->status->label(),
                'created_at' => $this->latestStatus->created_at,
            ] : null,
        ];
    }

}
