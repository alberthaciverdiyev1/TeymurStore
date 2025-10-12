<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Modules\Color\Http\Transformers\ColorResource;
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

            'latest_status'   => $this->latestStatus ? [
                'id'         => $this->latestStatus->id,
                'status'     => $this->latestStatus->status->label(),
                'created_at' => $this->latestStatus->created_at,
            ] : null,

            'user'    => UserResource::make($this->whenLoaded('user')),
            'address' => AddressResource::make($this->whenLoaded('address')),

            'items'   => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id'           => $item->id,
                        'quantity'     => $item->quantity,
                        'unit_price'   => $item->unit_price,
                        'total_price'  => $item->total_price,
                        'color' => $item->color ? ColorResource::make($item->color) : null,
                        'size'         => $item->size ? [
                            'id'    => $item->size->id,
                            'name'  => $item->size->name,
                        ] : null,
                        'product'      => $item->product
                            ? ProductResource::make($item->product)
                            : null,
                    ];
                });
            }),
        ];
    }

}
