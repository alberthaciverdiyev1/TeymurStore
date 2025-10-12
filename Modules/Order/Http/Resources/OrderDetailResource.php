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
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'address_id'   => $this->address_id,
            'transaction_id' => $this->transaction_id,
            'total_price'  => $this->total_price,
            'discount_price' => $this->discount_price,
            'shipping_price' => $this->shipping_price,
            'paid_at'      => $this->paid_at,
            'note'         => $this->note,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'deleted_at'   => $this->deleted_at,

            'statuses' => $this->whenLoaded('statuses', function () {
                return $this->statuses->map(function ($status) {
                    return [
                        'id'         => $status->id,
                        'status'     => $status->status->label(),
                        'created_at' => $status->created_at,
                    ];
                });
            }),

            'address' => new AddressResource($this->whenLoaded('address')),
            'user'    => new UserResource($this->whenLoaded('user')),

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

//            'items' => $this->whenLoaded('items', function () {
//                return $this->items->map(function ($item) {
//                    $product = $item->product;
//
//                    if ($product) {
//                        $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
//                        $product->rate_count = $product->reviews_count;
//                        $product->is_favorite = auth()->check()
//                            ? $product->favoritedBy()->where('user_id', auth()->id())->exists()
//                            : false;
//                    }
//
//                    return [
//                        'id'         => $item->id,
//                        'order_id'   => $item->order_id,
//                        'product_id' => $item->product_id,
//                        'color_id'   => $item->color_id,
//                        'size_id'    => $item->size_id,
//                        'quantity'   => $item->quantity,
//                        'unit_price' => $item->unit_price,
//                        'total_price'=> $item->total_price,
//                        'created_at' => $item->created_at,
//                        'updated_at' => $item->updated_at,
//                        'deleted_at' => $item->deleted_at,
//                        'product'    => $product ? new ProductResource($product) : null,
//                        'color'      => $item->color ? new ColorResource($item->color) : null,
//                        'size'       => $item->size ? new SizeResource($item->size) : null,
//                    ];
//                });
//            }),

        ];
    }
}
